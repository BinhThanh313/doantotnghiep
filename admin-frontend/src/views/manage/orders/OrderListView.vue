<script setup>
import { ref, onMounted, computed } from 'vue'
import {
  mdiCartOutline, mdiEye, mdiTrashCan, mdiRefresh, mdiMagnify,
  mdiDownload, mdiCheckAll, mdiSort, mdiSortAscending, mdiSortDescending,
  mdiCashRefund, mdiClose, mdiCalendarRange, mdiFilter, mdiFilterOff,
  mdiChevronLeft, mdiChevronRight, mdiChevronDoubleLeft, mdiChevronDoubleRight,
} from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import FormControl from '@/components/FormControl.vue'
import FormField from '@/components/FormField.vue'
import api from '@/services/api'
import { showToast } from '@/composables/useToast'
const orders      = ref([])
const importing = ref(false)
const importInput = ref(null)

const isDeleteModalActive = ref(false)
const itemToDelete = ref(null)
const isBulkModalActive = ref(false)

const confirmDelete = (id) => {
  itemToDelete.value = id
  isDeleteModalActive.value = true
}

const currentPage = ref(1)
const lastPage    = ref(1)
const totalItems  = ref(0)
const loading     = ref(false)

// Filters
const filterStatus        = ref('')
const filterPaymentStatus = ref('')
const filterSearch        = ref('')
const filterDateFrom      = ref('')
const filterDateTo        = ref('')
const filterPaymentMethod = ref('')

// Sorting
const sortBy    = ref('created_at')
const sortOrder = ref('desc')

// Bulk select
const selectedIds    = ref([])
const selectAll      = ref(false)
const bulkAction     = ref('')
const bulkStatus     = ref('')
const bulkLoading    = ref(false)
const showBulkPanel  = ref(false)

// Modals
const isDetailModalActive = ref(false)
const isRefundModalActive = ref(false)
const isExportModalActive = ref(false)
const selectedOrder       = ref(null)

// Refund form
const refundReason = ref('')
const refundAmount = ref(0)
const refundLoading = ref(false)

// Export form
const exportFormat      = ref('csv')
const exportStatus      = ref('')
const exportDateFrom    = ref('')
const exportDateTo      = ref('')

// ── Constants ───────────────────────────────────────────────────
const statusOptions = [
  { id: '', label: 'Tất cả trạng thái' },
  { id: 'pending',       label: 'Chờ xử lý' },
  { id: 'processing',    label: 'Đang chuẩn bị' },
  { id: 'ready_to_ship', label: 'Sẵn sàng giao' },
  { id: 'shipped',       label: 'Đang vận chuyển' },
  { id: 'delivered',     label: 'Đã giao hàng' },
  { id: 'completed',     label: 'Hoàn thành' },
  { id: 'cancelled',     label: 'Đã hủy' },
]

const paymentStatusOptions = [
  { id: '', label: 'Tất cả thanh toán' },
  { id: 'unpaid',   label: 'Chưa thanh toán' },
  { id: 'paid',     label: 'Đã thanh toán' },
  { id: 'refunded', label: 'Hoàn tiền' },
]

const paymentMethodOptions = [
  { id: '', label: 'Tất cả PTTT' },
  { id: 'cod',   label: 'COD' },
  { id: 'bank',  label: 'Chuyển khoản' },
]

const statusColors = {
  pending:       'bg-yellow-100 text-yellow-800',
  processing:    'bg-blue-100 text-blue-800',
  ready_to_ship: 'bg-purple-100 text-purple-800',
  shipped:       'bg-indigo-100 text-indigo-800',
  delivered:     'bg-teal-100 text-teal-800',
  completed:     'bg-emerald-100 text-emerald-800',
  cancelled:     'bg-red-100 text-red-800',
}

const statusLabels = {
  pending:       'Chờ xử lý',
  processing:    'Đang chuẩn bị',
  ready_to_ship: 'Sẵn sàng giao',
  shipped:       'Đang vận chuyển',
  delivered:     'Đã giao hàng',
  completed:     'Hoàn thành',
  cancelled:     'Đã hủy',
}

const paymentStatusLabels = {
  unpaid:   'Chưa thanh toán',
  paid:     'Đã thanh toán',
  refunded: 'Hoàn tiền',
}

const paymentStatusColors = {
  unpaid:   'bg-red-100 text-red-700',
  paid:     'bg-emerald-100 text-emerald-700',
  refunded: 'bg-gray-100 text-gray-600',
}

const sortableColumns = [
  { key: 'created_at',    label: 'Ngày tạo' },
  { key: 'total_amount',  label: 'Giá trị' },
  { key: 'customer_name', label: 'Khách hàng' },
  { key: 'status',        label: 'Trạng thái' },
]

// ── Computed ────────────────────────────────────────────────────
const grandTotal = computed(() => {
  if (!selectedOrder.value) return 0
  const o = selectedOrder.value
  return (o.total_amount || 0) + (o.shipping_fee || 0) - (o.discount_amount || 0)
})

const hasActiveFilters = computed(() =>
  filterStatus.value || filterPaymentStatus.value || filterSearch.value ||
  filterDateFrom.value || filterDateTo.value || filterPaymentMethod.value
)

const isAllSelected = computed(() =>
  orders.value.length > 0 && selectedIds.value.length === orders.value.length
)

// ── Methods ─────────────────────────────────────────────────────
const fetchOrders = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      sort_by:    sortBy.value,
      sort_order: sortOrder.value,
    }
    if (filterStatus.value)        params.status         = filterStatus.value
    if (filterPaymentStatus.value) params.payment_status = filterPaymentStatus.value
    if (filterSearch.value)        params.search         = filterSearch.value
    if (filterDateFrom.value)      params.date_from      = filterDateFrom.value
    if (filterDateTo.value)        params.date_to        = filterDateTo.value
    if (filterPaymentMethod.value) params.payment_method = filterPaymentMethod.value

    const res = await api.get('/api/admin/orders', { params })
    orders.value      = res.data.data
    currentPage.value = res.data.current_page
    lastPage.value    = res.data.last_page
    totalItems.value  = res.data.total
    selectedIds.value = []
  } catch (e) {
    showToast('Lỗi tải danh sách đơn hàng!', 'error')
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filterStatus.value        = ''
  filterPaymentStatus.value = ''
  filterSearch.value        = ''
  filterDateFrom.value      = ''
  filterDateTo.value        = ''
  filterPaymentMethod.value = ''
  sortBy.value    = 'created_at'
  sortOrder.value = 'desc'
  fetchOrders(1)
}

// Sorting
const toggleSort = (column) => {
  if (sortBy.value === column) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value    = column
    sortOrder.value = 'desc'
  }
  fetchOrders(1)
}

const getSortIcon = (column) => {
  if (sortBy.value !== column) return mdiSort
  return sortOrder.value === 'asc' ? mdiSortAscending : mdiSortDescending
}

// Bulk select
const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = orders.value.map(o => o.id)
  }
}

const toggleSelect = (id) => {
  const idx = selectedIds.value.indexOf(id)
  if (idx === -1) selectedIds.value.push(id)
  else selectedIds.value.splice(idx, 1)
}

const executeBulkAction = async () => {
  const actionStr = typeof bulkAction.value === 'object' && bulkAction.value !== null ? bulkAction.value.id : bulkAction.value
  if (!actionStr) return showToast('Vui lòng chọn hành động!', 'error')
  if (!selectedIds.value.length) return showToast('Vui lòng chọn ít nhất 1 đơn hàng!', 'error')

  isBulkModalActive.value = true
}

const performBulkAction = async () => {
  bulkLoading.value = true
  try {
    const actionStr = typeof bulkAction.value === 'object' && bulkAction.value !== null ? bulkAction.value.id : bulkAction.value
    let statusStr = typeof bulkStatus.value === 'object' && bulkStatus.value !== null ? bulkStatus.value.id : bulkStatus.value
    
    const payload = {
      ids:    selectedIds.value,
      action: actionStr,
    }
    if (actionStr === 'update_status') {
      if (!statusStr) statusStr = statusOptions[1].id
      payload.status = statusStr
    }
    if (actionStr === 'update_payment_status') {
      if (!statusStr) statusStr = paymentStatusOptions[1].id
      payload.payment_status = statusStr
    }

    const res = await api.post('/api/admin/orders/bulk', payload)
    showToast(res.data.message)
    selectedIds.value = []
    bulkAction.value  = ''
    bulkStatus.value  = ''
    showBulkPanel.value = false
    fetchOrders(currentPage.value)
  } catch (e) {
    showToast(e.response?.data?.message || 'Lỗi thực hiện bulk action!', 'error')
  } finally {
    bulkLoading.value = false
  }
}

// View / Update
const viewOrder = async (id) => {
  try {
    const res = await api.get(`/api/admin/orders/${id}`)
    selectedOrder.value = res.data
    isDetailModalActive.value = true
  } catch (e) {
    showToast('Lỗi tải chi tiết đơn hàng!', 'error')
  }
}

const updateStatus = async (orderId, newStatus) => {
  try {
    await api.post(`/api/admin/orders/${orderId}`, { _method: 'PUT', status: newStatus })
    await fetchOrders(currentPage.value)
    if (selectedOrder.value?.id === orderId) {
      selectedOrder.value.status = newStatus
    }
    showToast('Cập nhật trạng thái thành công!')
  } catch (e) {
    showToast('Lỗi cập nhật trạng thái!', 'error')
  }
}

const paymentStatusUpdating = ref(false)
const updatePaymentStatus = async (orderId, newPaymentStatus) => {
  if (paymentStatusUpdating.value) return
  paymentStatusUpdating.value = true
  try {
    await api.post(`/api/admin/orders/${orderId}`, { _method: 'PUT', payment_status: newPaymentStatus })
    await fetchOrders(currentPage.value)
    if (selectedOrder.value?.id === orderId) {
      selectedOrder.value.payment_status = newPaymentStatus
    }
    showToast('Cập nhật trạng thái thanh toán thành công!')
  } catch (e) {
    showToast(e?.response?.data?.message || 'Lỗi cập nhật trạng thái thanh toán!', 'error')
  } finally {
    paymentStatusUpdating.value = false
  }
}

const deleteOrder = async () => {
  if (!itemToDelete.value) return
  try {
    await api.delete(`/api/admin/orders/${itemToDelete.value}`)
    showToast('Đã xóa đơn hàng')
    fetchOrders(currentPage.value)
  } catch (e) {
    showToast('Lỗi xóa đơn hàng!', 'error')
  } finally {
    itemToDelete.value = null
  }
}

// Refund
const openRefundModal = (order) => {
  selectedOrder.value  = order
  refundReason.value   = ''
  refundAmount.value   = (order.total_amount || 0) + (order.shipping_fee || 0) - (order.discount_amount || 0)
  isDetailModalActive.value = false
  isRefundModalActive.value = true
}

const submitRefund = async () => {
  if (!refundReason.value.trim()) return showToast('Vui lòng nhập lý do hoàn tiền!', 'error')
  if (!refundAmount.value || refundAmount.value <= 0) return showToast('Số tiền hoàn không hợp lệ!', 'error')

  refundLoading.value = true
  try {
    const res = await api.post(`/api/admin/orders/${selectedOrder.value.id}/refund`, {
      reason:        refundReason.value,
      refund_amount: refundAmount.value,
    })
    showToast('Đã xử lý hoàn tiền thành công!')
    isRefundModalActive.value = false
    fetchOrders(currentPage.value)
  } catch (e) {
    showToast(e.response?.data?.message || 'Lỗi xử lý hoàn tiền!', 'error')
  } finally {
    refundLoading.value = false
  }
}

// Export
const doExport = async () => {
  try {
    const params = new URLSearchParams({ format: exportFormat.value })
    if (exportStatus.value)   params.append('status', exportStatus.value)
    if (exportDateFrom.value) params.append('date_from', exportDateFrom.value)
    if (exportDateTo.value)   params.append('date_to', exportDateTo.value)

    const res = await api.get(`/api/admin/orders/export?${params}`, { responseType: 'blob' })
    const url  = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href  = url
    link.setAttribute('download', `orders_${new Date().toISOString().slice(0,10)}.${exportFormat.value}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    isExportModalActive.value = false
    showToast('Xuất file thành công!')
  } catch (e) {
    showToast('Lỗi xuất file!', 'error')
  }
}

// Print shipping label
const printLabel = (order) => {
  const w = window.open('', '_blank', 'width=400,height=500')
  w.document.write(`
    <html><head><title>Phiếu giao hàng</title>
    <style>
      body { font-family: Arial, sans-serif; padding: 20px; }
      .label { border: 2px solid #000; padding: 20px; max-width: 380px; }
      h2 { text-align: center; margin: 0 0 15px; }
      .barcode { font-family: monospace; font-size: 28px; text-align: center; letter-spacing: 4px; margin: 10px 0; border: 1px dashed #ccc; padding: 8px; }
      .row { display: flex; justify-content: space-between; margin: 5px 0; font-size: 13px; }
      .label-section { margin-top: 10px; padding-top: 10px; border-top: 1px solid #ccc; }
    </style></head><body>
    <div class="label">
      <h2>🏪 PHIẾU GIAO HÀNG</h2>
      <div class="barcode">${order.tracking_number || '#' + order.id}</div>
      <div class="label-section">
        <strong>NGƯỜI NHẬN:</strong>
        <div class="row"><span>${order.customer_name}</span></div>
        <div class="row"><span>📞 ${order.customer_phone}</span></div>
        <div class="row"><span>📍 ${order.address}</span></div>
        <div class="row"><span>${order.province || ''}</span></div>
      </div>
      <div class="label-section">
        <div class="row"><span>PTTT:</span><span><strong>${(order.payment_method || '').toUpperCase()}</strong></span></div>
        <div class="row"><span>COD:</span><span><strong>${order.payment_method === 'cod' ? formatPrice((order.total_amount||0)+(order.shipping_fee||0)-(order.discount_amount||0)) : '0đ'}</strong></span></div>
      </div>
      <div style="text-align:center; margin-top:10px; font-size:11px; color:#999">In lúc ${new Date().toLocaleString('vi-VN')}</div>
    </div>
    <script>window.print(); window.close();<\/script>
    </body></html>
  `)
  w.document.close()
}

// Formatters
const formatPrice = (v) =>
  new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v)

const formatDate = (d) =>
  new Date(d).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })

const orderGrandTotal = (order) =>
  (order.total_amount || 0) + (order.shipping_fee || 0) - (order.discount_amount || 0)

// Pagination
const visiblePages = computed(() => {
  const range = []
  const delta = 2
  for (let i = Math.max(1, currentPage.value - delta); i <= Math.min(lastPage.value, currentPage.value + delta); i++) {
    range.push(i)
  }
  return range
})

onMounted(() => fetchOrders())
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiCartOutline" title="Quản lý Đơn hàng" main>
        <div class="flex gap-2">
          <BaseButton :icon="mdiDownload" color="success" small rounded-full label="Xuất file" @click="isExportModalActive = true" />
          <BaseButton :icon="mdiRefresh" color="info" small rounded-full @click="fetchOrders(currentPage)" />
        </div>
      </SectionTitleLineWithButton>

      <!-- Filters -->
      <CardBox class="mb-4">
        <div class="p-3">
          <div class="flex flex-wrap gap-3 items-end">
            <FormField label="Trạng thái" class="mb-0 flex-1 min-w-36">
              <FormControl v-model="filterStatus" :options="statusOptions" />
            </FormField>
            <FormField label="Thanh toán" class="mb-0 flex-1 min-w-36">
              <FormControl v-model="filterPaymentStatus" :options="paymentStatusOptions" />
            </FormField>
            <FormField label="PTTT" class="mb-0 flex-1 min-w-32">
              <FormControl v-model="filterPaymentMethod" :options="paymentMethodOptions" />
            </FormField>
            <FormField label="Tìm kiếm" class="mb-0 flex-2 min-w-52">
              <FormControl v-model="filterSearch" placeholder="Tên, SĐT, mã đơn..." :icon="mdiMagnify"
                @keyup.enter="fetchOrders(1)" />
            </FormField>
          </div>
          <div class="flex flex-wrap gap-3 items-end mt-3">
            <FormField label="Từ ngày" class="mb-0 flex-1 min-w-36">
              <FormControl v-model="filterDateFrom" type="date" />
            </FormField>
            <FormField label="Đến ngày" class="mb-0 flex-1 min-w-36">
              <FormControl v-model="filterDateTo" type="date" />
            </FormField>
            <FormField label="Sắp xếp theo" class="mb-0 flex-1 min-w-40">
              <FormControl v-model="sortBy"
                :options="sortableColumns.map(c => ({ id: c.key, label: c.label }))" />
            </FormField>
            <FormField label="Thứ tự" class="mb-0">
              <FormControl v-model="sortOrder" :options="[{ id: 'desc', label: 'Mới nhất' }, { id: 'asc', label: 'Cũ nhất' }]" />
            </FormField>
            <div class="flex gap-2 mb-0">
              <BaseButton color="info" :icon="mdiFilter" label="Lọc" rounded-full @click="fetchOrders(1)" />
              <BaseButton v-if="hasActiveFilters" color="warning" :icon="mdiFilterOff" outline rounded-full label="Xóa lọc" @click="clearFilters" />
            </div>
          </div>
        </div>
      </CardBox>

      <!-- Bulk Actions Bar -->
      <div v-if="selectedIds.length > 0"
           class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-200 flex flex-wrap items-center gap-3">
        <span class="text-blue-700 dark:text-blue-300 font-medium text-sm">
          Đã chọn {{ selectedIds.length }} đơn hàng
        </span>
        <FormControl v-model="bulkAction" class="w-48"
          :options="[
            { id: '', label: 'Chọn hành động...' },
            { id: 'update_status', label: 'Cập nhật trạng thái' },
            { id: 'update_payment_status', label: 'Cập nhật thanh toán' },
            { id: 'delete', label: '🗑 Xóa đã chọn' },
          ]" />
        <FormControl v-if="(bulkAction?.id || bulkAction) === 'update_status'" v-model="bulkStatus" class="w-44"
          :options="statusOptions.slice(1)" />
        <FormControl v-if="(bulkAction?.id || bulkAction) === 'update_payment_status'" v-model="bulkStatus" class="w-44"
          :options="paymentStatusOptions.slice(1)" />
        <BaseButton color="info" small label="Thực hiện" :disabled="bulkLoading" @click="executeBulkAction" />
        <BaseButton color="whiteDark" outline small :icon="mdiClose" @click="selectedIds = []" title="Bỏ chọn" />
      </div>

      <!-- Table -->
      <CardBox has-table>
        <div v-if="loading" class="p-8 text-center text-gray-500">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
          <p class="mt-2">Đang tải...</p>
        </div>
        <table v-else>
          <thead>
            <tr>
              <th class="w-10">
                <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll"
                       class="rounded border-gray-300 cursor-pointer" />
              </th>
              <th class="cursor-pointer select-none" @click="toggleSort('tracking_number')">
                <span class="flex items-center gap-1">Mã đơn</span>
              </th>
              <th class="cursor-pointer select-none" @click="toggleSort('customer_name')">
                <span class="flex items-center gap-1">
                  Khách hàng
                  <component :is="'svg'" v-if="sortBy === 'customer_name'" class="w-3 h-3 opacity-60" viewBox="0 0 24 24">
                    <path :d="getSortIcon('customer_name')" fill="currentColor" />
                  </component>
                </span>
              </th>
              <th class="cursor-pointer select-none" @click="toggleSort('total_amount')">
                <span class="flex items-center gap-1">
                  Giá trị
                  <component :is="'svg'" v-if="sortBy === 'total_amount'" class="w-3 h-3 opacity-60" viewBox="0 0 24 24">
                    <path :d="getSortIcon('total_amount')" fill="currentColor" />
                  </component>
                </span>
              </th>
              <th>Trạng thái</th>
              <th>Thanh toán</th>
              <th class="cursor-pointer select-none" @click="toggleSort('created_at')">
                <span class="flex items-center gap-1">
                  Ngày tạo
                  <component :is="'svg'" v-if="sortBy === 'created_at'" class="w-3 h-3 opacity-60" viewBox="0 0 24 24">
                    <path :d="getSortIcon('created_at')" fill="currentColor" />
                  </component>
                </span>
              </th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in orders" :key="order.id"
                :class="selectedIds.includes(order.id) ? 'bg-blue-50 dark:bg-blue-900/20' : ''">
              <td>
                <input type="checkbox" :checked="selectedIds.includes(order.id)"
                       @change="toggleSelect(order.id)"
                       class="rounded border-gray-300 cursor-pointer" />
              </td>
              <td data-label="Mã đơn">
                <span class="font-mono text-sm font-bold text-blue-600">
                  {{ order.tracking_number || '#' + order.id }}
                </span>
              </td>
              <td data-label="Khách hàng">
                <div>
                  <p class="font-semibold mb-0">{{ order.customer_name }}</p>
                  <p class="text-xs text-gray-500 mb-0">{{ order.customer_phone }}</p>
                </div>
              </td>
              <td data-label="Giá trị">
                <span class="font-bold text-emerald-600">
                  {{ formatPrice(orderGrandTotal(order)) }}
                </span>
              </td>
              <td data-label="Trạng thái">
                <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusColors[order.status]">
                  {{ statusLabels[order.status] || order.status }}
                </span>
              </td>
              <td data-label="Thanh toán">
                <span class="px-2 py-1 rounded-full text-xs font-medium" :class="paymentStatusColors[order.payment_status]">
                  {{ paymentStatusLabels[order.payment_status] || order.payment_status }}
                </span>
              </td>
              <td data-label="Ngày tạo">
                <small class="text-gray-500">{{ formatDate(order.created_at) }}</small>
              </td>
              <td class="before:hidden lg:w-1 whitespace-nowrap">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="info" :icon="mdiEye" small @click="viewOrder(order.id)" title="Xem chi tiết" />
                  <BaseButton v-if="['delivered','completed'].includes(order.status) && order.payment_status === 'paid'"
                    color="warning" :icon="mdiCashRefund" small @click="openRefundModal(order)" title="Hoàn tiền" />
                  <BaseButton color="danger" :icon="mdiTrashCan" small @click="confirmDelete(order.id)" title="Xóa" />
                </BaseButtons>
              </td>
            </tr>
            <tr v-if="orders.length === 0">
              <td colspan="8" class="text-center py-10 text-gray-400">
                <div class="text-4xl mb-2">📦</div>
                Không có đơn hàng nào.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="p-3 lg:px-6 border-t border-gray-100 dark:border-slate-800 flex justify-between items-center flex-wrap gap-2">
          <small class="text-gray-500">Tổng {{ totalItems }} đơn hàng | Trang {{ currentPage }}/{{ lastPage }}</small>
          <BaseButtons no-wrap>
            <BaseButton v-if="currentPage > 1" :icon="mdiChevronDoubleLeft" color="whiteDark" small @click="fetchOrders(1)" />
            <BaseButton v-if="currentPage > 1" :icon="mdiChevronLeft" color="whiteDark" small @click="fetchOrders(currentPage - 1)" />
            <BaseButton v-for="page in visiblePages" :key="page"
                        :active="page === currentPage" :label="page"
                        :color="page === currentPage ? 'lightDark' : 'whiteDark'"
                        small @click="fetchOrders(page)" />
            <BaseButton v-if="currentPage < lastPage" :icon="mdiChevronRight" color="whiteDark" small @click="fetchOrders(currentPage + 1)" />
            <BaseButton v-if="currentPage < lastPage" :icon="mdiChevronDoubleRight" color="whiteDark" small @click="fetchOrders(lastPage)" />
          </BaseButtons>
        </div>
      </CardBox>

      <!-- ══ DETAIL MODAL ══════════════════════════════════════════ -->
      <CardBoxModal
        v-model="isDetailModalActive"
        :title="`Chi tiết đơn hàng ${selectedOrder?.tracking_number || ''}`"
        button-label="Đóng"
        :has-cancel="false"
        has-custom-layout
      >
        <div v-if="selectedOrder" class="flex flex-col flex-1 min-h-0">
        <div class="p-6 space-y-4 overflow-y-auto min-h-0 flex-1">
          <!-- Thông tin khách -->
          <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-slate-800 rounded-lg">
            <div>
              <p class="text-xs text-gray-500 mb-1 font-semibold uppercase">Khách hàng</p>
              <p class="font-bold">{{ selectedOrder.customer_name }}</p>
              <p class="text-sm">{{ selectedOrder.customer_phone }}</p>
              <p class="text-sm text-gray-500">{{ selectedOrder.customer_email }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 mb-1 font-semibold uppercase">Địa chỉ giao hàng</p>
              <p class="text-sm">{{ selectedOrder.address }}</p>
              <p class="text-sm font-medium">{{ selectedOrder.province }}</p>
            </div>
          </div>

          <!-- Cập nhật trạng thái -->
          <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <p class="text-xs text-gray-500 mb-2 font-semibold uppercase">Cập nhật trạng thái</p>
            <div class="flex flex-wrap gap-2">
              <BaseButton
                v-for="opt in statusOptions.slice(1)" :key="opt.id"
                :label="opt.label"
                :active="selectedOrder.status === opt.id"
                :color="selectedOrder.status === opt.id ? 'info' : 'whiteDark'"
                rounded-full small
                @click="updateStatus(selectedOrder.id, opt.id)"
              />
            </div>
          </div>

          <!-- Cập nhật thanh toán -->
          <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
            <p class="text-xs text-gray-500 mb-2 font-semibold uppercase">Cập nhật thanh toán</p>
            <div class="flex flex-wrap gap-2">
              <BaseButton
                v-for="opt in paymentStatusOptions.slice(1)" :key="opt.id"
                :label="opt.label"
                :active="selectedOrder.payment_status === opt.id"
                :color="selectedOrder.payment_status === opt.id ? 'success' : 'whiteDark'"
                :disabled="paymentStatusUpdating"
                rounded-full small
                @click="updatePaymentStatus(selectedOrder.id, opt.id)"
              />
            </div>
          </div>

          <!-- Sản phẩm -->
          <div>
            <p class="text-xs text-gray-500 mb-2 font-semibold uppercase">Sản phẩm</p>
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="text-left pb-2">Sản phẩm</th>
                  <th class="text-center pb-2 w-12">SL</th>
                  <th class="text-right pb-2">Đơn giá</th>
                  <th class="text-right pb-2">Tổng</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in selectedOrder.items" :key="item.id" class="border-b last:border-0">
                  <td class="py-2">
                    <p class="font-medium mb-0">{{ item.product_name }}</p>
                    <p v-if="item.variant_name" class="text-xs text-gray-500 mb-0">{{ item.variant_name }}</p>
                  </td>
                  <td class="text-center py-2">{{ item.quantity }}</td>
                  <td class="text-right py-2">{{ formatPrice(item.price) }}</td>
                  <td class="text-right py-2 font-bold">{{ formatPrice(item.price * item.quantity) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Tổng tiền -->
          <div class="p-4 bg-gray-50 dark:bg-slate-800 rounded-lg space-y-2">
            <div class="flex justify-between text-sm">
              <span>Tạm tính:</span>
              <span>{{ formatPrice(selectedOrder.total_amount) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span>Phí vận chuyển:</span>
              <span>{{ formatPrice(selectedOrder.shipping_fee || 0) }}</span>
            </div>
            <div v-if="(selectedOrder.discount_amount || 0) > 0" class="flex justify-between text-sm text-emerald-600">
              <span>Giảm giá:</span>
              <span>-{{ formatPrice(selectedOrder.discount_amount) }}</span>
            </div>
            <div class="flex justify-between font-bold text-base border-t pt-2">
              <span>TỔNG CỘNG:</span>
              <span class="text-blue-600">{{ formatPrice(grandTotal) }}</span>
            </div>
          </div>

<!-- Payment Info -->
<div class="p-4 bg-gray-50 dark:bg-slate-800 rounded-lg">
  <p class="text-xs text-gray-500 mb-2 font-semibold uppercase">Thông tin thanh toán</p>
  <div v-if="selectedOrder.payment" class="space-y-2">
    <div class="flex justify-between text-sm">
      <span class="text-gray-500">Phương thức:</span>
      <span class="font-bold uppercase">{{ selectedOrder.payment.payment_method }}</span>
    </div>
    <div class="flex justify-between text-sm">
      <span class="text-gray-500">Trạng thái:</span>
      <span class="px-2 py-0.5 rounded-full text-xs font-medium"
        :class="{
          'bg-emerald-100 text-emerald-700': selectedOrder.payment.status === 'success',
          'bg-yellow-100 text-yellow-700':  selectedOrder.payment.status === 'pending',
          'bg-blue-100 text-blue-700':      selectedOrder.payment.status === 'processing',
          'bg-red-100 text-red-700':        selectedOrder.payment.status === 'failed',
          'bg-gray-100 text-gray-600':      selectedOrder.payment.status === 'refunded',
          'bg-orange-100 text-orange-700':  selectedOrder.payment.status === 'refunding',
        }">
        {{ {
          pending:    'Chờ thanh toán',
          processing: 'Đang xử lý',
          success:    'Thành công',
          failed:     'Thất bại',
          refunding:  'Đang hoàn tiền',
          refunded:   'Đã hoàn tiền',
        }[selectedOrder.payment.status] || selectedOrder.payment.status }}
      </span>
    </div>
    <div v-if="selectedOrder.payment.transaction_id" class="flex justify-between text-sm">
      <span class="text-gray-500">Mã giao dịch:</span>
      <code class="text-xs bg-gray-100 dark:bg-slate-700 px-2 py-0.5 rounded">
        {{ selectedOrder.payment.transaction_id }}
      </code>
    </div>
    <div v-if="selectedOrder.payment.paid_at" class="flex justify-between text-sm">
      <span class="text-gray-500">Thanh toán lúc:</span>
      <span>{{ formatDate(selectedOrder.payment.paid_at) }}</span>
    </div>
    <div class="flex justify-between text-sm">
      <span class="text-gray-500">Số tiền:</span>
      <span class="font-bold text-emerald-600">
        {{ formatPrice(selectedOrder.payment.amount) }}
      </span>
    </div>
  </div>
  <div v-else class="text-sm text-gray-400">Chưa có thông tin thanh toán</div>
</div>

<!-- Shipment Info -->
<div class="p-4 bg-gray-50 dark:bg-slate-800 rounded-lg">
  <p class="text-xs text-gray-500 mb-2 font-semibold uppercase">Thông tin vận chuyển</p>
  <div v-if="selectedOrder.shipment" class="space-y-2">
    <div class="flex justify-between text-sm">
      <span class="text-gray-500">Đơn vị VC:</span>
      <span class="font-medium">
        {{ selectedOrder.shipment.carrier?.name ?? '—' }}
      </span>
    </div>
    <div class="flex justify-between text-sm">
      <span class="text-gray-500">Mã vận đơn:</span>
      <code class="text-xs bg-gray-100 dark:bg-slate-700 px-2 py-0.5 rounded">
        {{ selectedOrder.shipment.tracking_number ?? '—' }}
      </code>
    </div>
    <div class="flex justify-between text-sm">
      <span class="text-gray-500">Trạng thái:</span>
      <span class="px-2 py-0.5 rounded-full text-xs font-medium"
        :class="{
          'bg-yellow-100 text-yellow-700': selectedOrder.shipment.status === 'pending',
          'bg-blue-100 text-blue-700':     selectedOrder.shipment.status === 'in_transit',
          'bg-emerald-100 text-emerald-700': selectedOrder.shipment.status === 'delivered',
          'bg-red-100 text-red-700':       selectedOrder.shipment.status === 'failed',
          'bg-gray-100 text-gray-600':     selectedOrder.shipment.status === 'returned',
        }">
        {{ {
          pending:    'Chờ lấy hàng',
          in_transit: 'Đang vận chuyển',
          delivered:  'Đã giao',
          failed:     'Giao thất bại',
          returned:   'Hoàn hàng',
        }[selectedOrder.shipment.status] || selectedOrder.shipment.status }}
      </span>
    </div>
    <div v-if="selectedOrder.shipment.estimated_delivery" class="flex justify-between text-sm">
      <span class="text-gray-500">Dự kiến giao:</span>
      <span>{{ formatDate(selectedOrder.shipment.estimated_delivery) }}</span>
    </div>
    <div v-if="selectedOrder.shipment.shipping_fee" class="flex justify-between text-sm">
      <span class="text-gray-500">Phí ship:</span>
      <span class="font-medium">{{ formatPrice(selectedOrder.shipment.shipping_fee) }}</span>
    </div>
  </div>
  <div v-else class="text-sm text-gray-400">Chưa có thông tin vận chuyển</div>
</div>

<!-- Vouchers -->
<div v-if="selectedOrder.vouchers?.length" class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
  <p class="text-xs text-gray-500 mb-2 font-semibold uppercase">Voucher đã dùng</p>
  <div v-for="v in selectedOrder.vouchers" :key="v.id" class="flex justify-between text-sm">
    <code class="font-bold text-emerald-600">{{ v.code }}</code>
    <span class="text-emerald-600">-{{ formatPrice(v.pivot.discount_amount) }}</span>
  </div>
</div>

<!-- Notes -->
<div v-if="selectedOrder.notes"
     class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded text-sm">
  <span class="font-semibold">Ghi chú:</span> {{ selectedOrder.notes }}
</div>
        </div>
        <div class="px-6 py-4 flex-none flex justify-end gap-2 border-t border-gray-200 dark:border-slate-700">
          <BaseButton
            v-if="selectedOrder && ['delivered','completed'].includes(selectedOrder.status) && selectedOrder.payment_status === 'paid'"
            color="warning" outline :icon="mdiCashRefund" label="Hoàn tiền"
            @click="openRefundModal(selectedOrder)" />
          <BaseButton color="whiteDark" outline label="In phiếu giao"
            @click="printLabel(selectedOrder)" />
          <BaseButton color="info" outline label="Đóng" @click="isDetailModalActive = false" />
        </div>
        </div>
      </CardBoxModal>
        
      <!-- ══ REFUND MODAL ══════════════════════════════════════════ -->
      <CardBoxModal
        v-model="isRefundModalActive"
        title="Xử lý hoàn tiền"
        button-label="Xác nhận hoàn tiền"
        has-cancel
        @confirm="submitRefund"
      >
        <div v-if="selectedOrder" class="p-6 space-y-4">
          <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
            <p class="font-semibold text-orange-700">Đơn hàng: {{ selectedOrder.tracking_number }}</p>
            <p class="text-sm text-gray-600">Khách hàng: {{ selectedOrder.customer_name }}</p>
            <p class="text-sm font-bold text-emerald-600">
              Tổng đơn: {{ formatPrice(orderGrandTotal(selectedOrder)) }}
            </p>
          </div>
          <FormField label="Số tiền hoàn trả (đ)">
            <FormControl v-model="refundAmount" type="number" :min="1" :max="orderGrandTotal(selectedOrder)" />
          </FormField>
          <FormField label="Lý do hoàn tiền *">
            <textarea v-model="refundReason" rows="3"
                      class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="Nhập lý do hoàn tiền..."></textarea>
          </FormField>
          <p class="text-xs text-gray-500">⚠️ Sau khi xác nhận, email thông báo sẽ được gửi đến khách hàng.</p>
        </div>
      </CardBoxModal>

      <!-- ══ EXPORT MODAL ══════════════════════════════════════════ -->
      <CardBoxModal
        v-model="isExportModalActive"
        title="Xuất danh sách đơn hàng"
        button-label="Xuất file"
        has-cancel
        @confirm="doExport"
      >
        <div class="p-6 space-y-4">
          <FormField label="Định dạng">
            <FormControl v-model="exportFormat" :options="[{ id: 'csv', label: 'CSV' }, { id: 'xlsx', label: 'Excel (.xlsx)' }]" />
          </FormField>
          <FormField label="Trạng thái đơn hàng">
            <FormControl v-model="exportStatus" :options="statusOptions" />
          </FormField>
          <div class="grid grid-cols-2 gap-4">
            <FormField label="Từ ngày">
              <FormControl v-model="exportDateFrom" type="date" />
            </FormField>
            <FormField label="Đến ngày">
              <FormControl v-model="exportDateTo" type="date" />
            </FormField>
          </div>
          <p class="text-xs text-gray-500">CSV và Excel (.xlsx) đều mở được bằng Excel, Google Sheets.</p>
        </div>
      </CardBoxModal>

      <!-- ══ CONFIRM DELETE MODAL ══ -->
      <CardBoxModal
        v-model="isDeleteModalActive"
        title="Xác nhận xóa"
        button-label="Xóa"
        has-cancel
        @confirm="deleteOrder"
      >
        <p>Xóa đơn hàng này?</p>
      </CardBoxModal>

      <!-- ══ CONFIRM BULK MODAL ══ -->
      <CardBoxModal
        v-model="isBulkModalActive"
        title="Xác nhận thao tác hàng loạt"
        button-label="Thực hiện"
        has-cancel
        @confirm="performBulkAction"
      >
        <p>Bạn có chắc chắn muốn thực hiện thao tác này cho các đơn hàng đã chọn?</p>
      </CardBoxModal>

    </SectionMain>
  </LayoutAuthenticated>
</template>