<script setup>
import { ref, onMounted, computed } from 'vue'
import { mdiCreditCardOutline, mdiRefresh, mdiEye, mdiCheckCircle, mdiCloseCircle, mdiCashRefund, mdiFilterOff, mdiFilter, mdiChevronLeft, mdiChevronRight } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import FormControl from '@/components/FormControl.vue'
import FormField from '@/components/FormField.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import api from '@/services/api'

// ── State ───────────────────────────────────────────────────
const payments     = ref([])
const loading      = ref(false)
const currentPage  = ref(1)
const lastPage     = ref(1)
const totalItems   = ref(0)

const filterStatus  = ref('')
const filterMethod  = ref('')
const filterSearch  = ref('')

const selectedPayment      = ref(null)
const isBankVerifyModal    = ref(false)
const isDetailModal        = ref(false)
const bankVerifyLoading    = ref(false)

const verifyForm = ref({
  transaction_id:   '',
  confirmed_amount: 0,
  note:             '',
})

const toast = ref({ show: false, message: '', type: 'success' })

// ── Options ─────────────────────────────────────────────────
const statusOptions = [
  { id: '', label: 'Tất cả trạng thái' },
  { id: 'pending',    label: 'Chờ xử lý' },
  { id: 'processing', label: 'Đang xử lý' },
  { id: 'success',    label: 'Thành công' },
  { id: 'failed',     label: 'Thất bại' },
  { id: 'refunding',  label: 'Đang hoàn tiền' },
  { id: 'refunded',   label: 'Đã hoàn tiền' },
]

const methodOptions = [
  { id: '', label: 'Tất cả PTTT' },
  { id: 'COD',   label: 'COD' },
  { id: 'BANK',  label: 'Chuyển khoản' },
]

const statusColors = {
  pending:    'bg-yellow-100 text-yellow-800',
  processing: 'bg-blue-100 text-blue-800',
  success:    'bg-emerald-100 text-emerald-800',
  failed:     'bg-red-100 text-red-800',
  refunding:  'bg-orange-100 text-orange-800',
  refunded:   'bg-gray-100 text-gray-600',
}

const statusLabels = {
  pending:    'Chờ xử lý',
  processing: 'Đang xử lý',
  success:    'Thành công',
  failed:     'Thất bại',
  refunding:  'Đang hoàn tiền',
  refunded:   'Đã hoàn tiền',
}

// ── Computed ────────────────────────────────────────────────
const hasFilters = computed(() => filterStatus.value || filterMethod.value || filterSearch.value)

const isBankPending = computed(() =>
  selectedPayment.value?.payment_method?.toLowerCase() === 'bank' &&
  ['pending', 'processing'].includes(selectedPayment.value?.status)
)

// ── Methods ─────────────────────────────────────────────────
const showToast = (msg, type = 'success') => {
  toast.value = { show: true, message: msg, type }
  setTimeout(() => { toast.value.show = false }, 3500)
}

const formatPrice = v => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v)
const formatDate  = d => d ? new Date(d).toLocaleString('vi-VN') : '—'

const fetchPayments = async (page = 1) => {
  loading.value = true
  try {
    const params = { page }
    if (filterStatus.value)  params.status = filterStatus.value
    if (filterMethod.value)  params.method = filterMethod.value
    if (filterSearch.value)  params.search = filterSearch.value

    const res = await api.get('/api/admin/payments', { params })
    payments.value    = res.data.data
    currentPage.value = res.data.current_page
    lastPage.value    = res.data.last_page
    totalItems.value  = res.data.total
  } catch {
    showToast('Lỗi tải danh sách thanh toán', 'error')
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filterStatus.value = ''
  filterMethod.value = ''
  filterSearch.value = ''
  fetchPayments(1)
}

const viewDetail = async (payment) => {
  selectedPayment.value = payment
  isDetailModal.value   = true
}

const openBankVerify = (payment) => {
  selectedPayment.value         = payment
  verifyForm.value.transaction_id   = ''
  verifyForm.value.confirmed_amount = payment.amount
  verifyForm.value.note             = ''
  isBankVerifyModal.value = true
}

const submitBankVerify = async () => {
  if (!verifyForm.value.transaction_id.trim()) {
    showToast('Vui lòng nhập mã giao dịch ngân hàng', 'error')
    return
  }
  bankVerifyLoading.value = true
  try {
    const res = await api.post(`/api/admin/payments/${selectedPayment.value.id}/verify-bank`, {
      transaction_id:   verifyForm.value.transaction_id,
      confirmed_amount: verifyForm.value.confirmed_amount,
      note:             verifyForm.value.note,
    })
    showToast(res.data.message || 'Xác nhận thành công!')
    isBankVerifyModal.value = false
    fetchPayments(currentPage.value)
  } catch (e) {
    showToast(e.response?.data?.message || 'Lỗi xác nhận', 'error')
  } finally {
    bankVerifyLoading.value = false
  }
}

const rejectPayment = async (payment) => {
  const reason = prompt('Lý do từ chối giao dịch:')
  if (reason === null) return
  try {
    await api.post(`/api/admin/payments/${payment.id}/reject-bank`, { reason })
    showToast('Đã từ chối giao dịch')
    fetchPayments(currentPage.value)
  } catch (e) {
    showToast(e.response?.data?.message || 'Lỗi từ chối', 'error')
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

onMounted(() => fetchPayments())
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiCreditCardOutline" title="Quản lý Thanh toán" main>
        <BaseButton :icon="mdiRefresh" color="info" small @click="fetchPayments(currentPage)" />
      </SectionTitleLineWithButton>

      <!-- Toast -->
      <Transition name="slide-fade">
        <div v-if="toast.show"
             class="fixed top-4 right-4 z-50 px-5 py-3 rounded-lg shadow-lg text-white text-sm font-medium"
             :class="toast.type === 'success' ? 'bg-emerald-500' : 'bg-red-500'">
          {{ toast.message }}
        </div>
      </Transition>

      <!-- Filters -->
      <CardBox class="mb-4">
        <div class="flex flex-wrap gap-3 items-end p-3">
          <FormField label="Trạng thái" class="mb-0 flex-1 min-w-36">
            <FormControl v-model="filterStatus" :options="statusOptions" />
          </FormField>
          <FormField label="Phương thức" class="mb-0 flex-1 min-w-36">
            <FormControl v-model="filterMethod" :options="methodOptions" />
          </FormField>
          <FormField label="Tìm kiếm" class="mb-0 flex-2 min-w-48">
            <FormControl v-model="filterSearch" placeholder="Mã GD, mã đơn hàng..."
              @keyup.enter="fetchPayments(1)" />
          </FormField>
          <BaseButton color="info" :icon="mdiFilter" label="Lọc" @click="fetchPayments(1)" />
          <BaseButton v-if="hasFilters" color="warning" :icon="mdiFilterOff" outline label="Xóa lọc" @click="clearFilters" />
        </div>
      </CardBox>

      <!-- Table -->
      <CardBox has-table>
        <div v-if="loading" class="p-10 text-center text-gray-400">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
          <p class="mt-2">Đang tải...</p>
        </div>

        <table v-else>
          <thead>
            <tr>
              <th>Mã thanh toán</th>
              <th>Đơn hàng</th>
              <th>Phương thức</th>
              <th>Số tiền</th>
              <th>Trạng thái</th>
              <th>Mã GD</th>
              <th>Thời gian</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in payments" :key="p.id">
              <td data-label="Mã TT">
                <span class="font-mono text-xs font-bold">#{{ p.id }}</span>
              </td>
              <td data-label="Đơn hàng">
                <span class="font-mono text-sm text-blue-600 font-bold">
                  {{ p.order?.tracking_number ?? '#' + p.order_id }}
                </span>
              </td>
              <td data-label="PTTT">
                <span class="px-2 py-1 rounded text-xs font-bold"
                  :class="{
                    'bg-yellow-100 text-yellow-800': p.payment_method === 'COD',
                    'bg-blue-100 text-blue-800':    p.payment_method === 'BANK',
                  }">
                  {{ p.payment_method }}
                </span>
              </td>
              <td data-label="Số tiền">
                <span class="font-bold text-emerald-600">{{ formatPrice(p.amount) }}</span>
              </td>
              <td data-label="Trạng thái">
                <span class="px-2 py-1 rounded-full text-xs font-medium"
                  :class="statusColors[p.status] || 'bg-gray-100'">
                  {{ statusLabels[p.status] || p.status }}
                </span>
              </td>
              <td data-label="Mã GD">
                <span class="font-mono text-xs text-gray-500">
                  {{ p.transaction_id ? p.transaction_id.substring(0, 16) + '...' : '—' }}
                </span>
              </td>
              <td data-label="Thời gian">
                <small class="text-gray-500">{{ formatDate(p.paid_at ?? p.created_at) }}</small>
              </td>
              <td class="before:hidden lg:w-1 whitespace-nowrap">
                <BaseButtons no-wrap>
                  <BaseButton color="info" :icon="mdiEye" small @click="viewDetail(p)" title="Xem chi tiết" />

                  <!-- Bank verify button -->
                  <BaseButton
                    v-if="p.payment_method?.toLowerCase() === 'bank' && ['pending','processing'].includes(p.status)"
                    color="success" :icon="mdiCheckCircle" small
                    @click="openBankVerify(p)" title="Xác nhận CK" />

                  <!-- Reject -->
                  <BaseButton
                    v-if="['pending','processing'].includes(p.status)"
                    color="danger" :icon="mdiCloseCircle" small
                    @click="rejectPayment(p)" title="Từ chối" />
                </BaseButtons>
              </td>
            </tr>
            <tr v-if="!payments.length">
              <td colspan="8" class="text-center py-10 text-gray-400">
                <div class="text-4xl mb-2">💳</div>
                Không có giao dịch nào.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="p-3 border-t border-gray-100 dark:border-slate-800 flex justify-between items-center flex-wrap gap-2">
          <small class="text-gray-500">Tổng {{ totalItems }} giao dịch | Trang {{ currentPage }}/{{ lastPage }}</small>
          <BaseButtons no-wrap>
            <BaseButton v-if="currentPage > 1" :icon="mdiChevronLeft" color="whiteDark" small @click="fetchPayments(currentPage-1)" />
            <BaseButton v-for="pg in visiblePages" :key="pg"
                        :active="pg === currentPage" :label="pg"
                        :color="pg === currentPage ? 'lightDark' : 'whiteDark'"
                        small @click="fetchPayments(pg)" />
            <BaseButton v-if="currentPage < lastPage" :icon="mdiChevronRight" color="whiteDark" small @click="fetchPayments(currentPage+1)" />
          </BaseButtons>
        </div>
      </CardBox>

      <!-- ══ DETAIL MODAL ══ -->
      <CardBoxModal v-model="isDetailModal" :title="`Chi tiết giao dịch #${selectedPayment?.id}`"
        button-label="Đóng" :has-cancel="false">
        <div v-if="selectedPayment" class="space-y-3 p-4">
          <div class="grid grid-cols-2 gap-3 p-4 bg-gray-50 dark:bg-slate-800 rounded-lg">
            <div><p class="text-xs text-gray-400 uppercase mb-1">Đơn hàng</p>
              <p class="font-bold font-mono">{{ selectedPayment.order?.tracking_number }}</p></div>
            <div><p class="text-xs text-gray-400 uppercase mb-1">Số tiền</p>
              <p class="font-bold text-emerald-600 text-xl">{{ formatPrice(selectedPayment.amount) }}</p></div>
            <div><p class="text-xs text-gray-400 uppercase mb-1">Phương thức</p>
              <p class="font-bold">{{ selectedPayment.payment_method }}</p></div>
            <div><p class="text-xs text-gray-400 uppercase mb-1">Trạng thái</p>
              <span class="px-2 py-1 rounded-full text-xs font-medium"
                :class="statusColors[selectedPayment.status]">
                {{ statusLabels[selectedPayment.status] }}</span></div>
            <div><p class="text-xs text-gray-400 uppercase mb-1">Mã giao dịch</p>
              <p class="font-mono text-sm">{{ selectedPayment.transaction_id || '—' }}</p></div>
            <div><p class="text-xs text-gray-400 uppercase mb-1">Thanh toán lúc</p>
              <p class="text-sm">{{ formatDate(selectedPayment.paid_at) }}</p></div>
          </div>

          <!-- Gateway response -->
          <div v-if="selectedPayment.gateway_response" class="p-3 bg-slate-900 rounded-lg">
            <p class="text-xs text-gray-400 uppercase mb-2">Gateway Response</p>
            <pre class="text-green-400 text-xs overflow-auto max-h-40">{{ JSON.stringify(selectedPayment.gateway_response, null, 2) }}</pre>
          </div>

          <!-- Bank verify action from detail -->
          <div v-if="isBankPending" class="pt-2">
            <BaseButton color="success" :icon="mdiCheckCircle" label="Xác nhận chuyển khoản"
              @click="() => { isDetailModal = false; openBankVerify(selectedPayment) }" />
          </div>
        </div>
      </CardBoxModal>

      <!-- ══ BANK VERIFY MODAL ══ -->
      <CardBoxModal
        v-model="isBankVerifyModal"
        title="Xác nhận chuyển khoản"
        button-label="Xác nhận"
        has-cancel
        @confirm="submitBankVerify"
      >
        <div v-if="selectedPayment" class="p-4 space-y-4">
          <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <p class="font-semibold text-blue-700">Đơn hàng: {{ selectedPayment.order?.tracking_number }}</p>
            <p class="text-sm">Khách hàng: {{ selectedPayment.order?.customer_name }}</p>
            <p class="text-sm font-bold text-emerald-600">Số tiền cần xác nhận: {{ formatPrice(selectedPayment.amount) }}</p>
          </div>

          <FormField label="Mã giao dịch ngân hàng *">
            <FormControl v-model="verifyForm.transaction_id" placeholder="VD: FT26155XXXXX" />
          </FormField>

          <FormField label="Số tiền thực tế nhận (đ)">
            <FormControl v-model="verifyForm.confirmed_amount" type="number" />
          </FormField>

          <FormField label="Ghi chú (tùy chọn)">
            <FormControl v-model="verifyForm.note" type="textarea" placeholder="Ghi chú thêm..." />
          </FormField>

          <p class="text-xs text-gray-400">
            ⚠️ Sau khi xác nhận, đơn hàng sẽ chuyển sang trạng thái <strong>Đã thanh toán</strong> và email thông báo sẽ được gửi đến khách hàng.
          </p>
        </div>
      </CardBoxModal>

    </SectionMain>
  </LayoutAuthenticated>
</template>

<style scoped>
.slide-fade-enter-active, .slide-fade-leave-active { transition: all .3s ease; }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateX(20px); opacity: 0; }
</style>