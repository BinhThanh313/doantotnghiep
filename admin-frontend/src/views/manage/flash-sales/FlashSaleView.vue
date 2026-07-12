<!-- admin-frontend/src/views/manage/flash-sales/FlashSaleView.vue -->
<script setup>
import { ref, onMounted, computed } from 'vue'
import {
  mdiBolt, mdiPlus, mdiPencil, mdiTrashCan, mdiEye,
  mdiClose, mdiRefresh, mdiCheckCircle, mdiClockOutline,
  mdiStopCircle, mdiPackageVariant, mdiChevronLeft, mdiChevronRight,
} from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import api from '@/services/api'

// ── State ────────────────────────────────────────────────────
const sales       = ref([])
const loading     = ref(false)
const currentPage = ref(1)
const lastPage    = ref(1)
const totalItems  = ref(0)
const filterStatus = ref('')

// Modals
const isSaleFormModal  = ref(false)
const isDetailModal    = ref(false)
const isAddItemModal   = ref(false)
const isEditItemModal  = ref(false)
const isDeleteModal    = ref(false)

const selectedSale       = ref(null)
const selectedItem       = ref(null)
const deletingId         = ref(null)
const availableProducts  = ref([])
const saleItems          = ref([])
const detailLoading      = ref(false)

const toast = ref({ show: false, message: '', type: 'success' })

// Forms
const saleForm = ref({
  name: '', description: '', starts_at: '', ends_at: '', is_active: true,
})
const isEditSale = ref(false)
const editingSaleId = ref(null)

const addItemForm = ref({
  product_id: '', sale_price: '', qty_limit: '', is_active: true,
})

const editItemForm = ref({
  sale_price: '', qty_limit: '', is_active: true,
})

// ── Options ──────────────────────────────────────────────────
const statusFilterOptions = [
  { id: '', label: 'Tất cả' },
  { id: 'running',  label: '🔴 Đang chạy' },
  { id: 'upcoming', label: '⏰ Sắp diễn ra' },
  { id: 'ended',    label: '✓ Đã kết thúc' },
  { id: 'disabled', label: '○ Tắt' },
]

const statusColors = {
  running:  'bg-red-100 text-red-700',
  upcoming: 'bg-yellow-100 text-yellow-800',
  ended:    'bg-gray-100 text-gray-600',
  disabled: 'bg-slate-100 text-slate-500',
}

const statusLabels = {
  running:  '🔴 Đang chạy',
  upcoming: '⏰ Sắp diễn ra',
  ended:    '✓ Đã kết thúc',
  disabled: '○ Đã tắt',
}

// ── Helpers ───────────────────────────────────────────────────
const showToast = (msg, type = 'success') => {
  toast.value = { show: true, message: msg, type }
  setTimeout(() => { toast.value.show = false }, 3500)
}

const formatPrice = v =>
  new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v)

const formatDate = d =>
  d ? new Date(d).toLocaleString('vi-VN', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
      })
    : '—'

const formatDateInput = d => {
  if (!d) return ''
  const dt = new Date(d)
  const pad = n => String(n).padStart(2, '0')
  return `${dt.getFullYear()}-${pad(dt.getMonth()+1)}-${pad(dt.getDate())}T${pad(dt.getHours())}:${pad(dt.getMinutes())}`
}

const discountPercent = (original, sale) => {
  if (!original || original <= sale) return 0
  return Math.round((1 - sale / original) * 100)
}

// Đếm ngược
const formatCountdown = (seconds) => {
  if (!seconds || seconds <= 0) return '00:00:00'
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  const s = seconds % 60
  const pad = n => String(n).padStart(2, '0')
  return `${pad(h)}:${pad(m)}:${pad(s)}`
}

// ── API calls ────────────────────────────────────────────────
const fetchSales = async (page = 1) => {
  loading.value = true
  try {
    const params = { page }
    if (filterStatus.value) params.status = filterStatus.value
    const res = await api.get('/api/admin/flash-sales', { params })
    sales.value      = res.data.data
    currentPage.value = res.data.current_page
    lastPage.value    = res.data.last_page
    totalItems.value  = res.data.total
  } catch {
    showToast('Lỗi tải danh sách Flash Sale', 'error')
  } finally {
    loading.value = false
  }
}

// ── CRUD Flash Sale ──────────────────────────────────────────
const openCreateSale = () => {
  isEditSale.value    = false
  editingSaleId.value = null
  saleForm.value = {
    name: '', description: '', starts_at: '', ends_at: '', is_active: true,
  }
  isSaleFormModal.value = true
}

const openEditSale = (sale) => {
  isEditSale.value    = true
  editingSaleId.value = sale.id
  saleForm.value = {
    name:        sale.name,
    description: sale.description || '',
    starts_at:   formatDateInput(sale.starts_at),
    ends_at:     formatDateInput(sale.ends_at),
    is_active:   sale.is_active,
  }
  isSaleFormModal.value = true
}

const saveSale = async () => {
  try {
    if (isEditSale.value) {
      await api.put(`/api/admin/flash-sales/${editingSaleId.value}`, saleForm.value)
      showToast('Đã cập nhật Flash Sale')
    } else {
      await api.post('/api/admin/flash-sales', saleForm.value)
      showToast('Đã tạo Flash Sale mới')
    }
    isSaleFormModal.value = false
    fetchSales(currentPage.value)
  } catch (e) {
    const msg = Object.values(e.response?.data?.errors || {}).flat().join(' | ')
             || e.response?.data?.message || 'Lỗi lưu Flash Sale'
    showToast(msg, 'error')
  }
}

const confirmDelete = (id) => {
  deletingId.value  = id
  isDeleteModal.value = true
}

const deleteSale = async () => {
  try {
    await api.delete(`/api/admin/flash-sales/${deletingId.value}`)
    showToast('Đã xóa Flash Sale')
    isDeleteModal.value = false
    fetchSales(currentPage.value)
  } catch {
    showToast('Lỗi xóa Flash Sale', 'error')
  }
}

// ── Detail & Items ───────────────────────────────────────────
const openDetail = async (sale) => {
  selectedSale.value = sale
  isDetailModal.value = true
  detailLoading.value = true
  try {
    const res = await api.get(`/api/admin/flash-sales/${sale.id}`)
    selectedSale.value = res.data
    saleItems.value    = res.data.items || []
  } catch {
    showToast('Lỗi tải chi tiết', 'error')
  } finally {
    detailLoading.value = false
  }
}

const openAddItem = async () => {
  addItemForm.value = { product_id: '', sale_price: '', qty_limit: '', is_active: true }
  try {
    const res = await api.get(`/api/admin/flash-sales/${selectedSale.value.id}/available-products`)
    availableProducts.value = res.data.map(p => ({
      id: p.id,
      label: `${p.name} — ${formatPrice(p.price)} (kho: ${p.stock})`,
      original_price: p.price,
    }))
  } catch {
    showToast('Lỗi tải danh sách sản phẩm', 'error')
  }
  isAddItemModal.value = true
}

// Tự động điền giá khi chọn sản phẩm
const onProductSelected = (opt) => {
  const found = availableProducts.value.find(p => p.id == (opt?.id ?? opt))
  if (found) addItemForm.value.sale_price = found.original_price
}

const addItem = async () => {
  try {
    const productId = addItemForm.value.product_id?.id ?? addItemForm.value.product_id
    await api.post(`/api/admin/flash-sales/${selectedSale.value.id}/items`, {
      ...addItemForm.value,
      product_id: productId,
    })
    showToast('Đã thêm sản phẩm vào Flash Sale')
    isAddItemModal.value = false
    openDetail(selectedSale.value)
  } catch (e) {
    showToast(e.response?.data?.message || 'Lỗi thêm sản phẩm', 'error')
  }
}

const openEditItem = (item) => {
  selectedItem.value = item
  editItemForm.value = {
    sale_price: item.sale_price,
    qty_limit:  item.qty_limit || '',
    is_active:  item.is_active,
  }
  isEditItemModal.value = true
}

const saveItem = async () => {
  try {
    await api.put(
      `/api/admin/flash-sales/${selectedSale.value.id}/items/${selectedItem.value.id}`,
      editItemForm.value,
    )
    showToast('Đã cập nhật sản phẩm')
    isEditItemModal.value = false
    openDetail(selectedSale.value)
  } catch {
    showToast('Lỗi cập nhật', 'error')
  }
}

const removeItem = async (itemId) => {
  if (!confirm('Xóa sản phẩm này khỏi Flash Sale?')) return
  try {
    await api.delete(`/api/admin/flash-sales/${selectedSale.value.id}/items/${itemId}`)
    showToast('Đã xóa sản phẩm')
    openDetail(selectedSale.value)
  } catch {
    showToast('Lỗi xóa sản phẩm', 'error')
  }
}

const toggleSaleActive = async (sale) => {
  try {
    await api.put(`/api/admin/flash-sales/${sale.id}`, { is_active: !sale.is_active })
    showToast(sale.is_active ? 'Đã tắt Flash Sale' : 'Đã bật Flash Sale')
    fetchSales(currentPage.value)
  } catch {
    showToast('Lỗi cập nhật', 'error')
  }
}

// Pagination
const visiblePages = computed(() => {
  const range = []
  for (let i = Math.max(1, currentPage.value - 2); i <= Math.min(lastPage.value, currentPage.value + 2); i++) {
    range.push(i)
  }
  return range
})

onMounted(() => fetchSales())
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiBolt" title="Quản lý Flash Sale" main>
        <BaseButton :icon="mdiPlus" color="success" label="Tạo Flash Sale" @click="openCreateSale" />
      </SectionTitleLineWithButton>

      <!-- Toast -->
      <Transition name="slide-fade">
        <div v-if="toast.show"
             class="fixed top-4 right-4 z-50 px-5 py-3 rounded-lg shadow-lg text-white text-sm font-medium"
             :class="toast.type === 'success' ? 'bg-emerald-500' : 'bg-red-500'">
          {{ toast.message }}
        </div>
      </Transition>

      <!-- Filter -->
      <CardBox class="mb-4">
        <div class="flex flex-wrap gap-3 items-end p-3">
          <FormField label="Trạng thái" class="mb-0 flex-1 min-w-40">
            <FormControl v-model="filterStatus" :options="statusFilterOptions" />
          </FormField>
          <BaseButton color="info" label="Lọc" @click="fetchSales(1)" />
          <BaseButton color="info" :icon="mdiRefresh" small @click="fetchSales(currentPage)" />
        </div>
      </CardBox>

      <!-- Table -->
      <CardBox has-table>
        <div v-if="loading" class="p-10 text-center text-gray-400">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-red-500 border-t-transparent"></div>
          <p class="mt-2">Đang tải...</p>
        </div>

        <table v-else>
          <thead>
            <tr>
              <th>Tên chương trình</th>
              <th>Thời gian</th>
              <th>Trạng thái</th>
              <th>Đếm ngược</th>
              <th>Sản phẩm</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="sale in sales" :key="sale.id">
              <td data-label="Tên">
                <p class="font-bold mb-0">{{ sale.name }}</p>
                <p v-if="sale.description" class="text-xs text-gray-400 mb-0 line-clamp-1">{{ sale.description }}</p>
              </td>
              <td data-label="Thời gian">
                <p class="text-xs text-gray-500 mb-0">🟢 {{ formatDate(sale.starts_at) }}</p>
                <p class="text-xs text-gray-500 mb-0">🔴 {{ formatDate(sale.ends_at) }}</p>
              </td>
              <td data-label="Trạng thái">
                <span class="px-2 py-1 rounded-full text-xs font-medium"
                      :class="statusColors[sale.status] || 'bg-gray-100'">
                  {{ statusLabels[sale.status] || sale.status }}
                </span>
              </td>
              <td data-label="Đếm ngược">
                <span v-if="sale.status === 'running'"
                      class="font-mono font-bold text-red-600 text-sm">
                  ⏱ {{ formatCountdown(sale.seconds_remaining) }}
                </span>
                <span v-else class="text-gray-400 text-xs">—</span>
              </td>
              <td data-label="Sản phẩm">
                <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">
                  {{ sale.items_count ?? 0 }} sản phẩm
                </span>
              </td>
              <td class="before:hidden lg:w-1 whitespace-nowrap">
                <BaseButtons no-wrap>
                  <BaseButton color="info" :icon="mdiEye" small @click="openDetail(sale)" title="Chi tiết" />
                  <BaseButton color="warning" :icon="mdiPencil" small @click="openEditSale(sale)" title="Sửa" />
                  <BaseButton
                    :color="sale.is_active ? 'danger' : 'success'"
                    :icon="sale.is_active ? mdiStopCircle : mdiCheckCircle"
                    small
                    :title="sale.is_active ? 'Tắt' : 'Bật'"
                    @click="toggleSaleActive(sale)"
                  />
                  <BaseButton color="danger" :icon="mdiTrashCan" small @click="confirmDelete(sale.id)" />
                </BaseButtons>
              </td>
            </tr>
            <tr v-if="!sales.length">
              <td colspan="6" class="text-center py-10 text-gray-400">
                <div class="text-4xl mb-2">⚡</div>
                Chưa có Flash Sale nào.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="p-3 border-t border-gray-100 dark:border-slate-800 flex justify-between items-center flex-wrap gap-2">
          <small class="text-gray-500">Tổng {{ totalItems }} Flash Sale | Trang {{ currentPage }}/{{ lastPage }}</small>
          <BaseButtons no-wrap>
            <BaseButton v-if="currentPage > 1" :icon="mdiChevronLeft" color="whiteDark" small @click="fetchSales(currentPage-1)" />
            <BaseButton v-for="pg in visiblePages" :key="pg"
                        :active="pg === currentPage" :label="pg"
                        :color="pg === currentPage ? 'lightDark' : 'whiteDark'"
                        small @click="fetchSales(pg)" />
            <BaseButton v-if="currentPage < lastPage" :icon="mdiChevronRight" color="whiteDark" small @click="fetchSales(currentPage+1)" />
          </BaseButtons>
        </div>
      </CardBox>

      <!-- ══ CREATE / EDIT SALE MODAL ══ -->
      <CardBoxModal
        v-model="isSaleFormModal"
        :title="isEditSale ? 'Cập nhật Flash Sale' : 'Tạo Flash Sale mới'"
        button-label="Lưu"
        has-cancel
        @confirm="saveSale"
      >
        <div class="p-4 space-y-4">
          <FormField label="Tên chương trình *">
            <FormControl v-model="saleForm.name" placeholder="VD: Flash Sale Thứ 6" />
          </FormField>
          <FormField label="Mô tả">
            <FormControl v-model="saleForm.description" type="textarea" placeholder="Mô tả ngắn..." />
          </FormField>
          <div class="grid grid-cols-2 gap-4">
            <FormField label="Bắt đầu *">
              <FormControl v-model="saleForm.starts_at" type="datetime-local" />
            </FormField>
            <FormField label="Kết thúc *">
              <FormControl v-model="saleForm.ends_at" type="datetime-local" />
            </FormField>
          </div>
          <div class="flex items-center gap-2">
            <input type="checkbox" v-model="saleForm.is_active" id="sale_active" class="mr-2">
            <label for="sale_active">Kích hoạt ngay</label>
          </div>
        </div>
      </CardBoxModal>

      <!-- ══ CONFIRM DELETE MODAL ══ -->
      <CardBoxModal
        v-model="isDeleteModal"
        title="Xác nhận xóa Flash Sale"
        button="danger"
        button-label="Xóa"
        has-cancel
        @confirm="deleteSale"
      >
        <p class="p-4">Bạn có chắc chắn muốn xóa Flash Sale này? Tất cả sản phẩm trong chương trình cũng sẽ bị xóa.</p>
      </CardBoxModal>

      <!-- ══ DETAIL MODAL ══ -->
      <CardBoxModal
        v-model="isDetailModal"
        :title="selectedSale ? `Chi tiết: ${selectedSale.name}` : 'Chi tiết Flash Sale'"
        button-label="Đóng"
        :has-cancel="false"
        has-custom-layout
      >
        <div v-if="selectedSale" class="overflow-y-auto max-h-[70vh]">

          <!-- Header banner -->
          <div class="p-5 bg-gradient-to-r from-red-500 to-orange-400 text-white">
            <div class="flex justify-between items-center">
              <div>
                <h3 class="text-xl font-bold">{{ selectedSale.name }}</h3>
                <p v-if="selectedSale.description" class="text-red-100 text-sm mt-1">{{ selectedSale.description }}</p>
              </div>
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/20 border border-white/30">
                {{ statusLabels[selectedSale.status] || selectedSale.status }}
              </span>
            </div>
            <div class="flex gap-6 mt-4 text-sm text-red-100">
              <span>🟢 {{ formatDate(selectedSale.starts_at) }}</span>
              <span>🔴 {{ formatDate(selectedSale.ends_at) }}</span>
            </div>
            <div v-if="selectedSale.status === 'running'" class="mt-3 text-center">
              <p class="text-red-100 text-xs">Còn lại</p>
              <p class="text-3xl font-mono font-bold">{{ formatCountdown(selectedSale.seconds_remaining) }}</p>
            </div>
          </div>

          <!-- Items list -->
          <div class="p-4">
            <div class="flex justify-between items-center mb-4">
              <h4 class="font-bold">Sản phẩm trong Flash Sale ({{ saleItems.length }})</h4>
              <BaseButton :icon="mdiPlus" color="success" small label="Thêm SP" @click="openAddItem" />
            </div>

            <div v-if="detailLoading" class="text-center py-8 text-gray-400">Đang tải...</div>

            <div v-else-if="saleItems.length" class="space-y-3">
              <div v-for="item in saleItems" :key="item.id"
                   class="flex items-center gap-3 p-3 border rounded-lg"
                   :class="item.is_active ? 'border-gray-200' : 'border-gray-100 opacity-60'">

                <!-- Product image -->
                <div class="w-14 h-14 rounded-lg overflow-hidden bg-gray-100 shrink-0">
                  <img v-if="item.product?.image"
                       :src="`http://localhost/doantotnghiep/public/storage/${item.product.image}`"
                       class="w-full h-full object-cover" />
                  <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xl">📦</div>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <p class="font-semibold text-sm mb-0 truncate">{{ item.product?.name }}</p>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="text-red-600 font-bold text-sm">{{ formatPrice(item.sale_price) }}</span>
                    <del v-if="item.product?.price" class="text-xs text-gray-400">
                      {{ formatPrice(item.product.price) }}
                    </del>
                    <span v-if="item.product?.price && discountPercent(item.product.price, item.sale_price) > 0"
                          class="bg-red-100 text-red-600 text-xs font-bold px-1.5 py-0.5 rounded">
                      -{{ discountPercent(item.product.price, item.sale_price) }}%
                    </span>
                  </div>

                  <!-- Qty progress -->
                  <div v-if="item.qty_limit" class="mt-2">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                      <span>Đã bán: {{ item.qty_sold }}/{{ item.qty_limit }}</span>
                      <span>{{ item.sold_percent ?? 0 }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                      <div class="bg-red-500 h-1.5 rounded-full transition-all"
                           :style="{ width: (item.sold_percent ?? 0) + '%' }"></div>
                    </div>
                  </div>
                  <p v-else class="text-xs text-gray-400 mt-1">Không giới hạn số lượng</p>
                </div>

                <!-- Actions -->
                <div class="flex gap-1 shrink-0">
                  <BaseButton :icon="mdiPencil" color="info" small @click="openEditItem(item)" />
                  <BaseButton :icon="mdiTrashCan" color="danger" small @click="removeItem(item.id)" />
                </div>
              </div>
            </div>

            <div v-else class="text-center py-8 text-gray-400">
              <div class="text-3xl mb-2">📦</div>
              Chưa có sản phẩm nào. Nhấn "Thêm SP" để bắt đầu.
            </div>
          </div>
        </div>

        <div class="px-5 pb-5 flex justify-end">
          <BaseButton color="whiteDark" outline label="Đóng" @click="isDetailModal = false" />
        </div>
      </CardBoxModal>

      <!-- ══ ADD ITEM MODAL ══ -->
      <CardBoxModal
        v-model="isAddItemModal"
        title="Thêm sản phẩm vào Flash Sale"
        button-label="Thêm"
        has-cancel
        @confirm="addItem"
      >
        <div class="p-4 space-y-4">
          <FormField label="Chọn sản phẩm *">
            <FormControl
              v-model="addItemForm.product_id"
              :options="availableProducts"
              @change="onProductSelected(addItemForm.product_id)"
            />
          </FormField>
          <FormField label="Giá Flash Sale (đ) *">
            <FormControl v-model="addItemForm.sale_price" type="number" :min="0" />
          </FormField>
          <FormField label="Số lượng giới hạn" help="Để trống = không giới hạn">
            <FormControl v-model="addItemForm.qty_limit" type="number" :min="1" placeholder="Không giới hạn" />
          </FormField>
          <div class="flex items-center gap-2">
            <input type="checkbox" v-model="addItemForm.is_active" id="item_active" class="mr-2">
            <label for="item_active">Kích hoạt sản phẩm này</label>
          </div>
        </div>
      </CardBoxModal>

      <!-- ══ EDIT ITEM MODAL ══ -->
      <CardBoxModal
        v-model="isEditItemModal"
        :title="`Cập nhật: ${selectedItem?.product?.name || ''}`"
        button-label="Lưu"
        has-cancel
        @confirm="saveItem"
      >
        <div class="p-4 space-y-4">
          <FormField label="Giá Flash Sale (đ) *">
            <FormControl v-model="editItemForm.sale_price" type="number" :min="0" />
          </FormField>
          <FormField label="Số lượng giới hạn" help="Để trống = không giới hạn">
            <FormControl v-model="editItemForm.qty_limit" type="number" :min="1" placeholder="Không giới hạn" />
          </FormField>
          <div class="flex items-center gap-2">
            <input type="checkbox" v-model="editItemForm.is_active" id="edit_item_active" class="mr-2">
            <label for="edit_item_active">Kích hoạt sản phẩm này</label>
          </div>
        </div>
      </CardBoxModal>

    </SectionMain>
  </LayoutAuthenticated>
</template>

<style scoped>
.slide-fade-enter-active, .slide-fade-leave-active { transition: all .3s ease; }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateX(20px); opacity: 0; }
</style>