<script setup>
import { ref, onMounted, computed } from 'vue'
import { mdiCartOutline, mdiEye, mdiTrashCan, mdiRefresh, mdiMagnify } from '@mdi/js'
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

const orders = ref([])
const currentPage = ref(1)
const lastPage = ref(1)
const loading = ref(false)

// Filters
const filterStatus = ref('')
const filterSearch = ref('')

// Modal xem chi tiết
const isDetailModalActive = ref(false)
const selectedOrder = ref(null)

const statusOptions = [
  { id: '', label: 'Tất cả trạng thái' },
  { id: 'pending', label: 'Chờ xử lý' },
  { id: 'processing', label: 'Đang chuẩn bị' },
  { id: 'ready_to_ship', label: 'Sẵn sàng giao' },
  { id: 'shipped', label: 'Đang vận chuyển' },
  { id: 'delivered', label: 'Đã giao hàng' },
  { id: 'completed', label: 'Hoàn thành' },
  { id: 'cancelled', label: 'Đã hủy' },
]

const statusColors = {
  pending: 'bg-yellow-100 text-yellow-800',
  processing: 'bg-blue-100 text-blue-800',
  ready_to_ship: 'bg-purple-100 text-purple-800',
  shipped: 'bg-indigo-100 text-indigo-800',
  delivered: 'bg-teal-100 text-teal-800',
  completed: 'bg-emerald-100 text-emerald-800',
  cancelled: 'bg-red-100 text-red-800',
}

const statusLabels = {
  pending: 'Chờ xử lý',
  processing: 'Đang chuẩn bị',
  ready_to_ship: 'Sẵn sàng giao',
  shipped: 'Đang vận chuyển',
  delivered: 'Đã giao hàng',
  completed: 'Hoàn thành',
  cancelled: 'Đã hủy',
}

const paymentStatusLabels = {
  unpaid: 'Chưa thanh toán',
  paid: 'Đã thanh toán',
  refunded: 'Hoàn tiền',
}

const paymentStatusColors = {
  unpaid: 'bg-red-100 text-red-700',
  paid: 'bg-emerald-100 text-emerald-700',
  refunded: 'bg-gray-100 text-gray-700',
}

const fetchOrders = async (page = 1) => {
  loading.value = true
  try {
    const params = { page }
    if (filterStatus.value) params.status = filterStatus.value
    if (filterSearch.value) params.search = filterSearch.value

    const res = await api.get('/api/admin/orders', { params })
    orders.value = res.data.data
    currentPage.value = res.data.current_page
    lastPage.value = res.data.last_page
  } catch (e) {
    console.error('Lỗi tải đơn hàng:', e)
  } finally {
    loading.value = false
  }
}

const viewOrder = async (id) => {
  try {
    const res = await api.get(`/api/admin/orders/${id}`)
    selectedOrder.value = res.data
    isDetailModalActive.value = true
  } catch (e) {
    console.error(e)
  }
}

const updateStatus = async (orderId, newStatus) => {
  try {
    await api.put(`/api/admin/orders/${orderId}`, { status: newStatus })
    await fetchOrders(currentPage.value)
    if (selectedOrder.value?.id === orderId) {
      selectedOrder.value.status = newStatus
    }
    alert('Cập nhật trạng thái thành công!')
  } catch (e) {
    alert('Lỗi cập nhật trạng thái!')
  }
}

const deleteOrder = async (id) => {
  if (!confirm('Xóa đơn hàng này?')) return
  try {
    await api.delete(`/api/admin/orders/${id}`)
    fetchOrders(currentPage.value)
  } catch (e) {
    alert('Lỗi xóa đơn hàng!')
  }
}

const formatPrice = (v) =>
  new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v)

const formatDate = (d) =>
  new Date(d).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })

const grandTotal = computed(() => {
  if (!selectedOrder.value) return 0
  const o = selectedOrder.value
  return (o.total_amount || 0) + (o.shipping_fee || 0) - (o.discount_amount || 0)
})

onMounted(() => fetchOrders())
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiCartOutline" title="Quản lý Đơn hàng" main>
        <BaseButton :icon="mdiRefresh" color="info" small @click="fetchOrders(currentPage)" />
      </SectionTitleLineWithButton>

      <!-- Filters -->
      <CardBox class="mb-4">
        <div class="flex flex-wrap gap-4 items-end p-2">
          <FormField label="Trạng thái" class="mb-0 flex-1 min-w-40">
            <FormControl v-model="filterStatus" :options="statusOptions" />
          </FormField>
          <FormField label="Tìm kiếm" class="mb-0 flex-1 min-w-60">
            <FormControl v-model="filterSearch" placeholder="Tên KH, SĐT, mã đơn..." :icon="mdiMagnify" />
          </FormField>
          <BaseButton color="info" label="Lọc" @click="fetchOrders(1)" />
          <BaseButton color="whiteDark" outline label="Xóa lọc" @click="() => { filterStatus = ''; filterSearch = ''; fetchOrders(1) }" />
        </div>
      </CardBox>

      <!-- Table -->
      <CardBox has-table>
        <div v-if="loading" class="p-8 text-center text-gray-500">Đang tải...</div>
        <table v-else>
          <thead>
            <tr>
              <th>Mã đơn</th>
              <th>Khách hàng</th>
              <th>Giá trị</th>
              <th>Trạng thái</th>
              <th>Thanh toán</th>
              <th>Ngày tạo</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in orders" :key="order.id">
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
                  {{ formatPrice((order.total_amount || 0) + (order.shipping_fee || 0) - (order.discount_amount || 0)) }}
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
                  <BaseButton color="danger" :icon="mdiTrashCan" small @click="deleteOrder(order.id)" title="Xóa" />
                </BaseButtons>
              </td>
            </tr>
            <tr v-if="orders.length === 0">
              <td colspan="7" class="text-center py-8 text-gray-500">Không có đơn hàng nào.</td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="p-3 lg:px-6 border-t border-gray-100 dark:border-slate-800 flex justify-between items-center">
          <BaseButtons>
            <BaseButton
              v-for="page in lastPage" :key="page"
              :active="page === currentPage"
              :label="page"
              :color="page === currentPage ? 'lightDark' : 'whiteDark'"
              small
              @click="fetchOrders(page)"
            />
          </BaseButtons>
          <small class="text-gray-500">Trang {{ currentPage }}/{{ lastPage }}</small>
        </div>
      </CardBox>

      <!-- Detail Modal -->
      <CardBoxModal
        v-model="isDetailModalActive"
        :title="`Chi tiết đơn hàng ${selectedOrder?.tracking_number || ''}`"
        button-label="Đóng"
        :has-cancel="false"
        has-custom-layout
      >
        <div v-if="selectedOrder" class="p-6 space-y-4 overflow-y-auto max-h-[70vh]">
          <!-- Thông tin khách -->
          <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-slate-800 rounded-lg">
            <div>
              <p class="text-xs text-gray-500 mb-1">Khách hàng</p>
              <p class="font-bold">{{ selectedOrder.customer_name }}</p>
              <p class="text-sm">{{ selectedOrder.customer_phone }}</p>
              <p class="text-sm">{{ selectedOrder.customer_email }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 mb-1">Địa chỉ giao hàng</p>
              <p class="text-sm">{{ selectedOrder.address }}</p>
              <p class="text-sm">{{ selectedOrder.province }}</p>
            </div>
          </div>

          <!-- Cập nhật trạng thái -->
          <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <p class="text-xs text-gray-500 mb-2 font-semibold">CẬP NHẬT TRẠNG THÁI</p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="opt in statusOptions.slice(1)" :key="opt.id"
                @click="updateStatus(selectedOrder.id, opt.id)"
                class="px-3 py-1 rounded-full text-xs font-medium border transition-colors"
                :class="selectedOrder.status === opt.id
                  ? 'bg-blue-600 text-white border-blue-600'
                  : 'bg-white text-gray-700 border-gray-300 hover:border-blue-400'"
              >
                {{ opt.label }}
              </button>
            </div>
          </div>

          <!-- Danh sách sản phẩm -->
          <div>
            <p class="text-xs text-gray-500 mb-2 font-semibold">SẢN PHẨM</p>
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b">
                  <th class="text-left pb-2">Sản phẩm</th>
                  <th class="text-center pb-2">SL</th>
                  <th class="text-right pb-2">Đơn giá</th>
                  <th class="text-right pb-2">Tổng</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in selectedOrder.items" :key="item.id" class="border-b last:border-0">
                  <td class="py-2">{{ item.product_name }}</td>
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
            <div v-if="selectedOrder.discount_amount > 0" class="flex justify-between text-sm text-emerald-600">
              <span>Giảm giá:</span>
              <span>-{{ formatPrice(selectedOrder.discount_amount) }}</span>
            </div>
            <div class="flex justify-between font-bold text-base border-t pt-2">
              <span>TỔNG CỘNG:</span>
              <span class="text-blue-600">{{ formatPrice(grandTotal) }}</span>
            </div>
          </div>

          <!-- Phương thức thanh toán -->
          <div class="flex gap-4 text-sm text-gray-600">
            <span>Thanh toán: <strong>{{ selectedOrder.payment_method?.toUpperCase() }}</strong></span>
            <span>|</span>
            <span class="font-medium" :class="paymentStatusColors[selectedOrder.payment_status]?.replace('bg-', 'text-').split(' ')[0]">
              {{ paymentStatusLabels[selectedOrder.payment_status] }}
            </span>
          </div>

          <!-- Ghi chú -->
          <div v-if="selectedOrder.notes" class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded text-sm">
            <span class="font-semibold">Ghi chú:</span> {{ selectedOrder.notes }}
          </div>
        </div>

        <!-- Footer buttons -->
        <div class="px-6 pb-6 flex justify-end gap-2">
          <BaseButton color="info" outline label="Đóng" @click="isDetailModalActive = false" />
        </div>
      </CardBoxModal>

    </SectionMain>
  </LayoutAuthenticated>
</template>