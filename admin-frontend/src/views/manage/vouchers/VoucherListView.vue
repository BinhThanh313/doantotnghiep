<script setup>
import { ref, onMounted } from 'vue'
import { mdiTicketPercent, mdiPlus, mdiPencil, mdiTrashCan, mdiCheckCircle, mdiCloseCircle, mdiFileExcel } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import BaseIcon from '@/components/BaseIcon.vue'
import api from '@/services/api'
import { showToast } from '@/composables/useToast'

const vouchers = ref([])
const isModalActive = ref(false)
const isEditMode = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)

const form = ref({
  code: '',
  name: '',
  discount_type: 'percent',
  discount_value: '',
  min_amount: 0,
  max_discount: '',
  max_uses: '',
  max_uses_per_user: 1,
  start_date: '',
  end_date: '',
  is_active: true,
})

const editingId = ref(null)

const discountTypeOptions = [
  { id: 'percent', label: 'Phần trăm (%)' },
  { id: 'fixed', label: 'Số tiền cố định (đ)' },
]

const fetchVouchers = async (page = 1) => {
  try {
    const res = await api.get(`/api/admin/vouchers?page=${page}`)
    vouchers.value = res.data.data
    currentPage.value = res.data.current_page
    lastPage.value = res.data.last_page
  } catch (e) {
    console.error(e)
  }
}

const triggerImport = () => importInput.value?.click()

const handleImportFile = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return

  const formData = new FormData()
  formData.append('file', file)

  importing.value = true
  try {
    const res = await api.post('/api/admin/vouchers/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    let msg = res.data.message
    if (res.data.errors?.length) {
      msg += '\n' + res.data.errors.join('\n')
    }
    showToast(msg, res.data.errors?.length ? 'warning' : 'success')
    fetchVouchers(currentPage.value)
  } catch (err) {
    showToast(err.response?.data?.message || 'Lỗi nhập file Excel', 'error')
  } finally {
    importing.value = false
    e.target.value = ''
  }
}

const openCreate = () => {
  isEditMode.value = false
  editingId.value = null
  form.value = {
    code: '', name: '', discount_type: 'percent', discount_value: '',
    min_amount: 0, max_discount: '', max_uses: '', max_uses_per_user: 1,
    start_date: '', end_date: '', is_active: true,
  }
  isModalActive.value = true
}

const openEdit = async (id) => {
  try {
    const res = await api.get(`/api/admin/vouchers/${id}`)
    const v = res.data
    form.value = {
      code: v.code,
      name: v.name || '',
      discount_type: v.discount_type,
      discount_value: v.discount_value,
      min_amount: v.min_amount || 0,
      max_discount: v.max_discount || '',
      max_uses: v.max_uses || '',
      max_uses_per_user: v.max_uses_per_user || 1,
      start_date: v.start_date ? v.start_date.substring(0, 16) : '',
      end_date: v.end_date ? v.end_date.substring(0, 16) : '',
      is_active: Boolean(v.is_active),
    }
    editingId.value = id
    isEditMode.value = true
    isModalActive.value = true
  } catch (e) {
    console.error(e)
  }
}

const submit = async () => {
  try {
    const payload = { ...form.value }
    // Chuyển discount_type từ object sang string nếu cần
    if (payload.discount_type?.id) payload.discount_type = payload.discount_type.id

    if (isEditMode.value) {
      await api.put(`/api/admin/vouchers/${editingId.value}`, payload)
      showToast('Cập nhật voucher thành công!')
    } else {
      await api.post('/api/admin/vouchers', payload)
      showToast('Tạo voucher thành công!')
    }
    isModalActive.value = false
    fetchVouchers(currentPage.value)
  } catch (e) {
    const errors = e.response?.data?.errors
    if (errors) {
      showToast(Object.values(errors).flat().join(' | '), 'error')
    } else {
      showToast('Có lỗi xảy ra!', 'error')
    }
  }
}

const toggleActive = async (id) => {
  try {
    await api.patch(`/api/admin/vouchers/${id}/toggle`)
    fetchVouchers(currentPage.value)
  } catch (e) {
    console.error(e)
  }
}

const deleteVoucher = async () => {
  if (!itemToDelete.value) return
  try {
    await api.delete(`/api/admin/vouchers/${itemToDelete.value}`)
    fetchVouchers(currentPage.value)
    showToast('Đã xóa voucher')
  } catch (e) {
    showToast(e.response?.data?.message || 'Không thể xóa voucher đã được sử dụng!', 'error')
  } finally {
    itemToDelete.value = null
    isDeleteModalActive.value = false
  }
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('vi-VN') : '—'
const formatPrice = (v) => v ? new Intl.NumberFormat('vi-VN').format(v) + 'đ' : '—'

onMounted(() => fetchVouchers())
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiTicketPercent" title="Quản lý Voucher / Mã giảm giá" main>
        <div class="flex items-center gap-2">
          <BaseButton :icon="mdiPlus" label="Tạo mới" color="success" rounded-full @click="openCreate" />
        </div>
      </SectionTitleLineWithButton>

      <CardBox has-table>
        <table>
          <thead>
            <tr>
              <th>Mã voucher</th>
              <th>Loại giảm</th>
              <th>Giá trị</th>
              <th>Đơn tối thiểu</th>
              <th>Đã dùng</th>
              <th>Hạn dùng</th>
              <th>Trạng thái</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="v in vouchers" :key="v.id">
              <td data-label="Mã">
                <span class="font-mono font-bold text-blue-600">{{ v.code }}</span>
                <p v-if="v.name" class="text-xs text-gray-500 mb-0">{{ v.name }}</p>
              </td>
              <td data-label="Loại">
                <span class="px-2 py-1 rounded text-xs"
                  :class="v.discount_type === 'percent' ? 'bg-purple-100 text-purple-700' : 'bg-orange-100 text-orange-700'">
                  {{ v.discount_type === 'percent' ? 'Phần trăm' : 'Cố định' }}
                </span>
              </td>
              <td data-label="Giá trị">
                <span class="font-bold text-emerald-600">
                  {{ v.discount_type === 'percent' ? v.discount_value + '%' : formatPrice(v.discount_value) }}
                </span>
                <p v-if="v.max_discount && v.discount_type === 'percent'" class="text-xs text-gray-400 mb-0">
                  Tối đa {{ formatPrice(v.max_discount) }}
                </p>
              </td>
              <td data-label="Đơn tối thiểu">{{ formatPrice(v.min_amount) }}</td>
              <td data-label="Đã dùng">
                {{ v.used_count }}
                <span v-if="v.max_uses" class="text-gray-400">/{{ v.max_uses }}</span>
              </td>
              <td data-label="Hạn dùng">
                <p class="mb-0 text-sm">{{ v.start_date ? formatDate(v.start_date) : '—' }}</p>
                <p class="mb-0 text-sm text-gray-400">→ {{ v.end_date ? formatDate(v.end_date) : '—' }}</p>
              </td>
              <td data-label="Trạng thái">
                <span class="px-2 py-1 rounded-full text-xs font-medium cursor-pointer select-none"
                  :class="v.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
                  @click="toggleActive(v.id)"
                  title="Click để bật/tắt">
                  {{ v.is_active ? 'Đang hoạt động' : 'Tắt' }}
                </span>
              </td>
              <td class="before:hidden lg:w-1 whitespace-nowrap">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="info" :icon="mdiPencil" small @click="openEdit(v.id)" />
                  <BaseButton color="danger" :icon="mdiTrashCan" small @click="confirmDelete(v.id)" />
                </BaseButtons>
              </td>
            </tr>
            <tr v-if="vouchers.length === 0">
              <td colspan="8" class="text-center py-8 text-gray-500">Chưa có voucher nào.</td>
            </tr>
          </tbody>
        </table>

        <div class="p-3 lg:px-6 border-t border-gray-100 dark:border-slate-800 flex justify-end items-center flex-wrap gap-2">
          <BaseButtons>
            <BaseButton v-for="page in lastPage" :key="page"
              :active="page === currentPage" :label="page"
              :color="page === currentPage ? 'lightDark' : 'whiteDark'"
              small @click="fetchVouchers(page)" />
          </BaseButtons>
        </div>
      </CardBox>

      <!-- Modal Tạo/Sửa -->
      <CardBoxModal
        v-model="isModalActive"
        :title="isEditMode ? 'Cập nhật Voucher' : 'Tạo Voucher mới'"
        button-label="Lưu"
        has-cancel
        is-form
        @confirm="submit"
      >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormField label="Mã voucher (*)">
            <FormControl v-model="form.code" placeholder="VD: SALE20" required />
          </FormField>
          <FormField label="Tên mô tả">
            <FormControl v-model="form.name" placeholder="VD: Giảm 20% mùa hè" />
          </FormField>

          <FormField label="Loại giảm giá (*)">
            <FormControl v-model="form.discount_type" :options="discountTypeOptions" />
          </FormField>
          <FormField label="Giá trị giảm (*)">
            <FormControl v-model="form.discount_value" type="number" required
              :placeholder="form.discount_type === 'percent' || form.discount_type?.id === 'percent' ? 'VD: 20 (%)' : 'VD: 50000 (đ)'" />
          </FormField>

          <FormField label="Giảm tối đa (đ, cho %)">
            <FormControl v-model="form.max_discount" type="number" placeholder="Để trống = không giới hạn" />
          </FormField>
          <FormField label="Đơn hàng tối thiểu (đ)">
            <FormControl v-model="form.min_amount" type="number" />
          </FormField>

          <FormField label="Giới hạn tổng lượt dùng">
            <FormControl v-model="form.max_uses" type="number" placeholder="Để trống = không giới hạn" />
          </FormField>
          <FormField label="Lượt dùng/người">
            <FormControl v-model="form.max_uses_per_user" type="number" />
          </FormField>

          <FormField label="Ngày bắt đầu">
            <FormControl v-model="form.start_date" type="datetime-local" />
          </FormField>
          <FormField label="Ngày kết thúc">
            <FormControl v-model="form.end_date" type="datetime-local" />
          </FormField>
        </div>

        <div class="flex items-center gap-2 mt-4">
          <input type="checkbox" v-model="form.is_active" id="v_active" class="mr-2">
          <label for="v_active">Kích hoạt voucher</label>
        </div>
      </CardBoxModal>

      <!-- ══ CONFIRM DELETE MODAL ══ -->
      <CardBoxModal
        v-model="isDeleteModalActive"
        title="Xác nhận xóa"
        button-label="Xóa"
        has-cancel
        @confirm="deleteVoucher"
      >
        <p>Bạn có chắc chắn muốn xóa voucher này không?</p>
      </CardBoxModal>

    </SectionMain>
  </LayoutAuthenticated>
</template>