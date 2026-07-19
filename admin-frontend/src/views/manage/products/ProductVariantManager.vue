<script setup>
import { ref, watch, computed } from 'vue'
import {
  mdiPlus, mdiPencil, mdiTrashCan, mdiTuneVertical,
  mdiHistory, mdiChevronDown, mdiChevronUp, mdiClose,
} from '@mdi/js'
import CardBox from '@/components/CardBox.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import FormFilePicker from '@/components/FormFilePicker.vue'
import api from '@/services/api'
import { showToast } from '@/composables/useToast'
const props = defineProps({
  productId: { type: [Number, String], required: true },
})

const storageBase = computed(() => `${api.defaults.baseURL}/storage/`)

// ── State ────────────────────────────────────────────────────
const variants       = ref([])
const loading        = ref(false)
const isExpanded     = ref(false)

// Modals
const isFormModal    = ref(false)
const isAdjustModal  = ref(false)
const isLogsModal    = ref(false)
const isEditMode     = ref(false)
const editingId      = ref(null)

const form = ref({
  sku: '', name: '', attributes: '', price: '', original_price: '',
  stock: 0, image: '', is_active: true,
})
const variantImageFile = ref(null) // File thật được chọn để upload (khác form.image là path cũ khi sửa)
const removeExistingImage = ref(false)

const adjustForm = ref({ quantity_change: 0, reason: 'restock', notes: '' })
const logs       = ref([])
const selectedVariant = ref(null)

// ── Options ──────────────────────────────────────────────────
const reasonOptions = [
  { id: 'restock',    label: '📦 Nhập kho' },
  { id: 'adjustment', label: '✏️ Điều chỉnh' },
  { id: 'return',     label: '↩️ Khách trả hàng' },
  { id: 'damage',     label: '💥 Hàng hỏng' },
]

const reasonLabels = {
  restock: 'Nhập kho', adjustment: 'Điều chỉnh',
  return: 'Trả hàng', damage: 'Hàng hỏng',
  purchase: 'Bán hàng', cancel: 'Hủy đơn',
}

// ── Computed ─────────────────────────────────────────────────
const totalStock = computed(() =>
  variants.value.reduce((s, v) => s + (v.stock || 0), 0)
)

// ── API helpers ──────────────────────────────────────────────
const fetchVariants = async () => {
  loading.value = true
  try {
    const res = await api.get(`/api/admin/products/${props.productId}/variants`)
    variants.value = res.data
  } catch {
    showToast('Lỗi tải biến thể', 'error')
  } finally {
    loading.value = false
  }
}

watch(() => props.productId, fetchVariants, { immediate: true })
watch(isExpanded, (v) => { if (v) fetchVariants() })

// ── CRUD ─────────────────────────────────────────────────────
const openCreate = () => {
  isEditMode.value = false
  editingId.value  = null
  form.value = { sku: '', name: '', attributes: '', price: '', original_price: '', stock: 0, image: '', is_active: true }
  variantImageFile.value = null
  removeExistingImage.value = false
  isFormModal.value = true
}

const openEdit = (v) => {
  isEditMode.value = true
  editingId.value  = v.id
  form.value = { ...v }
  variantImageFile.value = null
  removeExistingImage.value = false
  isFormModal.value = true
}

const saveVariant = async () => {
  const formData = new FormData()
  formData.append('name', form.value.name)
  if (form.value.sku)             formData.append('sku', form.value.sku)
  if (form.value.attributes)      formData.append('attributes', form.value.attributes)
  if (form.value.price !== '' && form.value.price !== null)             formData.append('price', form.value.price)
  if (form.value.original_price !== '' && form.value.original_price !== null) formData.append('original_price', form.value.original_price)
  formData.append('stock', form.value.stock)
  formData.append('is_active', form.value.is_active ? 1 : 0)
  if (variantImageFile.value) formData.append('image', variantImageFile.value)
  if (removeExistingImage.value) formData.append('remove_image', 1)

  const config = { headers: { 'Content-Type': 'multipart/form-data' } }

  try {
    if (isEditMode.value) {
      formData.append('_method', 'PUT')
      await api.post(`/api/admin/products/${props.productId}/variants/${editingId.value}`, formData, config)
      showToast('Đã cập nhật biến thể')
    } else {
      await api.post(`/api/admin/products/${props.productId}/variants`, formData, config)
      showToast('Đã tạo biến thể mới')
    }
    isFormModal.value = false
    fetchVariants()
  } catch (e) {
    const errors = e.response?.data?.errors
    if (errors) showToast(Object.values(errors).flat().join(' | '), 'error')
    else showToast(e.response?.data?.message || 'Lỗi lưu biến thể', 'error')
  }
}

const deleteVariant = async (id) => {
  if (!confirm('Xóa biến thể này?')) return
  try {
    await api.delete(`/api/admin/products/${props.productId}/variants/${id}`)
    showToast('Đã xóa biến thể')
    fetchVariants()
  } catch (e) {
    showToast(e.response?.data?.message || 'Lỗi xóa', 'error')
  }
}

// ── Adjust stock ─────────────────────────────────────────────
const openAdjust = (v) => {
  selectedVariant.value = v
  adjustForm.value = { quantity_change: 0, reason: 'restock', notes: '' }
  isAdjustModal.value = true
}

const submitAdjust = async () => {
  try {
    const res = await api.post(
      `/api/admin/products/${props.productId}/variants/${selectedVariant.value.id}/adjust-stock`,
      adjustForm.value
    )
    showToast(res.data.message)
    isAdjustModal.value = false
    fetchVariants()
  } catch (e) {
    showToast(e.response?.data?.message || 'Lỗi điều chỉnh kho', 'error')
  }
}

// ── Inventory logs ───────────────────────────────────────────
const openLogs = async (v) => {
  selectedVariant.value = v
  logs.value = []
  isLogsModal.value = true
  try {
    const res = await api.get(`/api/admin/products/${props.productId}/variants/${v.id}/logs`)
    logs.value = res.data.data || res.data
  } catch {
    showToast('Lỗi tải lịch sử kho', 'error')
  }
}

const formatPrice = (v) =>
  v ? new Intl.NumberFormat('vi-VN').format(v) + 'đ' : '—'
const formatDate  = (d) => new Date(d).toLocaleString('vi-VN')
</script>

<template>
  <CardBox class="mt-4">
    <!-- Header toggle -->
    <div class="flex items-center justify-between p-4 cursor-pointer select-none"
         @click="isExpanded = !isExpanded">
      <div class="flex items-center gap-3">
        <span class="font-bold text-base">Biến thể sản phẩm</span>
        <span v-if="variants.length"
              class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">
          {{ variants.length }} biến thể · Tổng kho: {{ totalStock }}
        </span>
      </div>
      <div class="flex items-center gap-2">
        <BaseButton v-if="isExpanded"
          :icon="mdiPlus" color="success" small label="Thêm biến thể"
          @click.stop="openCreate" />
        <component :is="'svg'" class="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
          <path :d="isExpanded ? mdiChevronUp : mdiChevronDown" />
        </component>
      </div>
    </div>

    <!-- Table -->
    <div v-if="isExpanded">
      <div v-if="loading" class="p-6 text-center text-gray-400">
        <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-blue-500 border-t-transparent"></div>
      </div>

      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-slate-800">
          <tr>
            <th class="px-4 py-2 text-left">Ảnh</th>
            <th class="px-4 py-2 text-left">Tên biến thể</th>
            <th class="px-4 py-2 text-left">SKU</th>
            <th class="px-4 py-2 text-left">Thuộc tính</th>
            <th class="px-4 py-2 text-right">Giá</th>
            <th class="px-4 py-2 text-center">Tồn kho</th>
            <th class="px-4 py-2 text-center">Trạng thái</th>
            <th class="px-4 py-2 text-center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="v in variants" :key="v.id"
              class="border-t border-gray-100 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800/50">
            <td class="px-4 py-2">
              <img v-if="v.image" :src="storageBase + v.image"
                   class="w-10 h-10 object-cover rounded border dark:border-slate-700" />
              <span v-else class="text-xs text-gray-400">—</span>
            </td>
            <td class="px-4 py-2 font-medium">{{ v.name }}</td>
            <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ v.sku || '—' }}</td>
            <td class="px-4 py-2 text-xs text-gray-500">{{ v.attributes || '—' }}</td>
            <td class="px-4 py-2 text-right text-emerald-600 font-semibold">
              {{ formatPrice(v.price) }}
            </td>
            <td class="px-4 py-2 text-center">
              <span class="font-bold text-lg"
                    :class="v.stock === 0 ? 'text-red-500' : v.stock <= 5 ? 'text-orange-500' : 'text-gray-700 dark:text-slate-300'">
                {{ v.stock }}
              </span>
            </td>
            <td class="px-4 py-2 text-center">
              <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="v.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'">
                {{ v.is_active ? 'Hoạt động' : 'Tắt' }}
              </span>
            </td>
            <td class="px-4 py-2">
              <BaseButtons no-wrap type="justify-center">
                <BaseButton :icon="mdiPencil" color="info" small @click="openEdit(v)" title="Sửa" />
                <BaseButton :icon="mdiTuneVertical" color="warning" small @click="openAdjust(v)" title="Điều chỉnh kho" />
                <BaseButton :icon="mdiHistory" color="whiteDark" small @click="openLogs(v)" title="Lịch sử kho" />
                <BaseButton :icon="mdiTrashCan" color="danger" small @click="deleteVariant(v.id)" title="Xóa" />
              </BaseButtons>
            </td>
          </tr>
          <tr v-if="!variants.length">
            <td colspan="8" class="text-center py-6 text-gray-400">Chưa có biến thể nào. Nhấn "Thêm biến thể" để bắt đầu.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </CardBox>

  <!-- ══ FORM MODAL ══ -->
  <CardBoxModal
    v-model="isFormModal"
    :title="isEditMode ? 'Cập nhật biến thể' : 'Thêm biến thể mới'"
    button-label="Lưu"
    has-cancel
    is-form
    @confirm="saveVariant"
  >
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
      <FormField label="Tên biến thể (*)">
        <FormControl v-model="form.name" placeholder="VD: Size M - Đỏ" required />
      </FormField>
      <FormField label="SKU">
        <FormControl v-model="form.sku" placeholder="VD: SP001-M-RED" />
      </FormField>
      <FormField label="Thuộc tính (JSON hoặc text)">
        <FormControl v-model="form.attributes" placeholder='VD: {"size":"M","color":"red"}' />
      </FormField>
      <FormField label="Tồn kho (*)">
        <FormControl v-model="form.stock" type="number" :min="0" required />
      </FormField>
      <FormField label="Giá bán (đ)">
        <FormControl v-model="form.price" type="number" :min="0" placeholder="Để trống = dùng giá sản phẩm" />
      </FormField>
      <FormField label="Giá gốc (đ)">
        <FormControl v-model="form.original_price" type="number" :min="0" />
      </FormField>
      <FormField label="Ảnh biến thể (tuỳ chọn)" class="md:col-span-2">
        <div class="flex items-center gap-3">
          <img v-if="!removeExistingImage && (variantImageFile || form.image)"
               :src="variantImageFile ? URL.createObjectURL(variantImageFile) : (storageBase + form.image)"
               class="w-14 h-14 object-cover rounded border dark:border-slate-700" />
          <FormFilePicker v-model="variantImageFile" label="Chọn ảnh" accept="image/*" />
        </div>
        <label v-if="isEditMode && form.image && !variantImageFile" class="flex items-center gap-2 mt-2 text-xs text-gray-500">
          <input type="checkbox" v-model="removeExistingImage">
          Xoá ảnh hiện tại (không thay ảnh mới)
        </label>
      </FormField>
      <div class="flex items-center gap-2 md:col-span-2">
        <input type="checkbox" v-model="form.is_active" id="var_active" class="mr-2">
        <label for="var_active">Kích hoạt biến thể này</label>
      </div>
    </div>
  </CardBoxModal>

  <!-- ══ ADJUST STOCK MODAL ══ -->
  <CardBoxModal
    v-model="isAdjustModal"
    :title="`Điều chỉnh kho — ${selectedVariant?.name}`"
    button-label="Xác nhận"
    has-cancel
    @confirm="submitAdjust"
  >
    <div class="p-4 space-y-4" v-if="selectedVariant">
      <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm">
        <span class="text-gray-500">Tồn kho hiện tại:</span>
        <span class="font-bold text-blue-700 ml-2 text-lg">{{ selectedVariant.stock }}</span>
      </div>
      <FormField label="Số lượng thay đổi (*)" help="Nhập số âm để trừ kho, số dương để nhập kho">
        <FormControl v-model="adjustForm.quantity_change" type="number" />
      </FormField>
      <FormField label="Lý do (*)">
        <FormControl v-model="adjustForm.reason" :options="reasonOptions" />
      </FormField>
      <FormField label="Ghi chú">
        <FormControl v-model="adjustForm.notes" type="textarea" placeholder="Thêm ghi chú..." />
      </FormField>
      <div v-if="adjustForm.quantity_change !== 0"
           class="p-3 bg-gray-50 dark:bg-slate-800 rounded-lg text-sm font-medium">
        Tồn kho sau điều chỉnh:
        <span :class="(selectedVariant.stock + Number(adjustForm.quantity_change)) < 0 ? 'text-red-500' : 'text-emerald-600'"
              class="ml-2 text-lg font-bold">
          {{ selectedVariant.stock + Number(adjustForm.quantity_change) }}
        </span>
      </div>
    </div>
  </CardBoxModal>

  <!-- ══ LOGS MODAL ══ -->
  <CardBoxModal
    v-model="isLogsModal"
    :title="`Lịch sử kho — ${selectedVariant?.name}`"
    button-label="Đóng"
    :has-cancel="false"
  >
    <div class="p-4 overflow-y-auto max-h-96">
      <table class="w-full text-xs">
        <thead>
          <tr class="border-b text-gray-500">
            <th class="text-left pb-2">Thời gian</th>
            <th class="text-center pb-2">Thay đổi</th>
            <th class="text-center pb-2">Trước → Sau</th>
            <th class="text-left pb-2">Lý do</th>
            <th class="text-left pb-2">Ghi chú</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs" :key="log.id" class="border-b last:border-0">
            <td class="py-2 text-gray-500">{{ formatDate(log.created_at) }}</td>
            <td class="py-2 text-center">
              <span :class="log.quantity_change > 0 ? 'text-emerald-600' : 'text-red-500'"
                    class="font-bold">
                {{ log.quantity_change > 0 ? '+' : '' }}{{ log.quantity_change }}
              </span>
            </td>
            <td class="py-2 text-center text-gray-600">
              {{ log.stock_before }} → {{ log.stock_after }}
            </td>
            <td class="py-2">
              <span class="px-1.5 py-0.5 bg-gray-100 dark:bg-slate-700 rounded text-xs">
                {{ reasonLabels[log.reason] || log.reason }}
              </span>
            </td>
            <td class="py-2 text-gray-500 max-w-xs truncate">{{ log.notes || '—' }}</td>
          </tr>
          <tr v-if="!logs.length">
            <td colspan="5" class="text-center py-6 text-gray-400">Chưa có lịch sử</td>
          </tr>
        </tbody>
      </table>
    </div>
  </CardBoxModal>
</template>