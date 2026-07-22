<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import {
  mdiChartTimelineVariant, mdiCartOutline, mdiAccountMultiple,
  mdiPackageVariant, mdiCashMultiple, mdiAlertCircle, mdiTrendingUp
} from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBoxWidget from '@/components/CardBoxWidget.vue'
import CardBox from '@/components/CardBox.vue'
import LineChart from '@/components/Charts/LineChart.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import api from '@/services/api'

// ── State ───────────────────────────────────────────────────────
const widgets = ref({
  total_revenue: 0,
  new_orders: 0,
  total_users: 0,
  active_products: 0,
  today_orders: 0,
  today_revenue: 0,
})

const recentOrders     = ref([])
const lowStockProducts = ref([])
const orderStats       = ref([])
const paymentStats     = ref([])
const chartData        = ref(null)
const revenueChartData = ref(null)

// Chart controls
const chartPeriod  = ref('7days') // '7days' | '30days' | 'monthly'
const loading      = ref(false)
const statsLoading = ref(false)
const exportLoading    = ref(false)
const exportPdfLoading = ref(false)

// ── Formatters ──────────────────────────────────────────────────
const formatPrice = (v) =>
  new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v)

const formatShortPrice = (v) => {
  if (v >= 1_000_000_000) return (v / 1_000_000_000).toFixed(1) + ' tỷ'
  if (v >= 1_000_000)     return (v / 1_000_000).toFixed(1) + ' triệu'
  if (v >= 1_000)         return (v / 1_000).toFixed(0) + 'k'
  return v.toLocaleString('vi-VN')
}

const getStatusLabel = (status) => ({
  pending:       'Chờ xử lý',
  processing:    'Đang chuẩn bị',
  ready_to_ship: 'Sẵn sàng giao',
  shipped:       'Đang vận chuyển',
  delivered:     'Đã giao hàng',
  completed:     'Hoàn thành',
  cancelled:     'Đã hủy',
}[status] || status)

const getStatusClass = (status) => ({
  pending:    'bg-yellow-100 text-yellow-800',
  processing: 'bg-blue-100 text-blue-800',
  completed:  'bg-emerald-100 text-emerald-800',
  cancelled:  'bg-red-100 text-red-800',
  shipped:    'bg-indigo-100 text-indigo-800',
  delivered:  'bg-teal-100 text-teal-800',
}[status] || 'bg-gray-100 text-gray-700')

// ── Status distribution (pie-like bar) ──────────────────────────
const statusDistribution = computed(() => {
  if (!orderStats.value.length) return []
  const total = orderStats.value.reduce((s, x) => s + x.count, 0)
  const colors = {
    pending:       '#f59e0b',
    processing:    '#3b82f6',
    ready_to_ship: '#8b5cf6',
    shipped:       '#6366f1',
    delivered:     '#14b8a6',
    completed:     '#10b981',
    cancelled:     '#ef4444',
  }
  return orderStats.value.map(s => ({
    status: s.status,
    count:  s.count,
    total:  s.total || 0,
    pct:    total > 0 ? Math.round((s.count / total) * 100) : 0,
    color:  colors[s.status] || '#6b7280',
    label:  getStatusLabel(s.status),
  }))
})

const paymentDistribution = computed(() => {
  if (!paymentStats.value.length) return []
  const total = paymentStats.value.reduce((s, x) => s + x.count, 0)
  const colors = { cod: '#f59e0b', bank: '#3b82f6' }
  const labels = { cod: 'COD', bank: 'Chuyển khoản' }
  return paymentStats.value.map(p => ({
    method: p.payment_method,
    count:  p.count,
    pct:    total > 0 ? Math.round((p.count / total) * 100) : 0,
    color:  colors[p.payment_method] || '#6b7280',
    label:  labels[p.payment_method] || p.payment_method,
  }))
})

// ── API calls ───────────────────────────────────────────────────
const fetchDashboardData = async () => {
  loading.value = true
  try {
    const res  = await api.get('/api/admin/dashboard')
    const data = res.data

    widgets.value = {
      ...data.widgets,
      today_orders:  data.today_orders  || 0,
      today_revenue: data.today_revenue || 0,
    }
    recentOrders.value     = data.recent_orders    || []
    lowStockProducts.value = data.low_stock        || []

    buildLineChart(data.chart)
  } catch (e) {
    console.error('Dashboard error:', e)
  } finally {
    loading.value = false
  }
}

const exportDashboard = async () => {
  exportLoading.value = true
  try {
    const res  = await api.get('/api/admin/dashboard/export', { responseType: 'blob' })
    const url  = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href  = url
    link.setAttribute('download', `dashboard_${new Date().toISOString().slice(0, 10)}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Dashboard export error:', e)
  } finally {
    exportLoading.value = false
  }
}

const exportDashboardPdf = async () => {
  exportPdfLoading.value = true
  try {
    const res  = await api.get('/api/admin/dashboard/export-pdf', { responseType: 'blob' })
    const url  = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href  = url
    link.setAttribute('download', `dashboard_${new Date().toISOString().slice(0, 10)}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Dashboard PDF export error:', e)
  } finally {
    exportPdfLoading.value = false
  }
}

const fetchOrderStats = async () => {
  statsLoading.value = true
  try {
    const res  = await api.get('/api/admin/orders/stats/summary')
    const data = res.data

    orderStats.value  = data.by_status      || []
    paymentStats.value = data.payment_methods || []

    buildRevenueChart(data.revenue_chart || [])
  } catch (e) {
    console.error('Stats error:', e)
  } finally {
    statsLoading.value = false
  }
}

const buildLineChart = (chart) => {
  if (!chart) return
  chartData.value = {
    labels: chart.labels || [],
    datasets: [{
      label: 'Doanh thu',
      fill: true,
      backgroundColor: 'rgba(16, 185, 129, 0.08)',
      borderColor: '#10B981',
      borderWidth: 2,
      pointBackgroundColor: '#10B981',
      pointBorderColor: '#fff',
      pointBorderWidth: 2,
      pointHoverRadius: 5,
      pointRadius: 4,
      data: chart.data || [],
      tension: 0.4,
    }],
  }
}

const buildRevenueChart = (chartRaw) => {
  if (!chartRaw.length) return
  revenueChartData.value = {
    labels: chartRaw.map(d => d.date || d.day || d.month),
    datasets: [
      {
        label: 'Doanh thu (đ)',
        fill: true,
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
        borderColor: '#6366f1',
        borderWidth: 2,
        pointBackgroundColor: '#6366f1',
        pointRadius: 3,
        data: chartRaw.map(d => d.revenue),
        tension: 0.4,
        yAxisID: 'y',
      },
      {
        label: 'Số đơn',
        fill: false,
        borderColor: '#f59e0b',
        borderWidth: 2,
        borderDash: [4, 4],
        pointBackgroundColor: '#f59e0b',
        pointRadius: 3,
        data: chartRaw.map(d => d.orders),
        tension: 0.4,
        yAxisID: 'y1',
      },
    ],
  }
}

onMounted(() => {
  fetchDashboardData()
  fetchOrderStats()
})
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiChartTimelineVariant" title="Tổng quan hệ thống" main>
        <button @click="exportDashboard" :disabled="exportLoading"
                class="text-sm text-blue-600 hover:underline flex items-center gap-1 mr-4 disabled:opacity-50">
          ⬇ {{ exportLoading ? 'Đang xuất...' : 'Xuất Excel' }}
        </button>
        <button @click="exportDashboardPdf" :disabled="exportPdfLoading"
                class="text-sm text-red-600 hover:underline flex items-center gap-1 mr-4 disabled:opacity-50">
          ⬇ {{ exportPdfLoading ? 'Đang xuất...' : 'Xuất báo cáo PDF' }}
        </button>
        <button @click="fetchDashboardData(); fetchOrderStats()"
                class="text-sm text-blue-600 hover:underline flex items-center gap-1">
          ↻ Làm mới
        </button>
      </SectionTitleLineWithButton>

      <!-- Widgets Row 1 -->
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-4">
        <CardBoxWidget trend-type="up" color="text-emerald-500" :icon="mdiCashMultiple"
          :number="widgets.total_revenue" :number-prefix="''" :number-suffix="'đ'"
          label="Tổng doanh thu" />
        <CardBoxWidget trend-type="alert" color="text-red-500" :icon="mdiCartOutline"
          :number="widgets.new_orders" label="Đơn chờ xử lý" />
        <CardBoxWidget trend-type="info" color="text-blue-500" :icon="mdiAccountMultiple"
          :number="widgets.total_users" label="Khách hàng" />
        <CardBoxWidget trend-type="up" color="text-yellow-500" :icon="mdiPackageVariant"
          :number="widgets.active_products" label="Sản phẩm hoạt động" />
      </div>

      <!-- Widgets Row 2 - Today Stats -->
      <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-4 text-white">
          <p class="text-blue-100 text-sm font-medium mb-1">📦 Đơn hàng hôm nay</p>
          <p class="text-3xl font-bold">{{ widgets.today_orders }}</p>
          <p class="text-blue-200 text-xs mt-1">đơn hàng mới</p>
        </div>
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-4 text-white">
          <p class="text-emerald-100 text-sm font-medium mb-1">💰 Doanh thu hôm nay</p>
          <p class="text-2xl font-bold">{{ formatShortPrice(widgets.today_revenue) }}</p>
          <p class="text-emerald-200 text-xs mt-1">từ đơn hoàn thành</p>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        <!-- Line Chart: Revenue trend -->
        <CardBox>
          <div class="flex items-center justify-between mb-4 px-1">
            <h4 class="text-lg font-bold">Doanh thu 7 ngày qua</h4>
          </div>
          <div v-if="chartData" class="h-64">
            <LineChart :data="chartData" class="h-full" />
          </div>
          <div v-else class="h-64 flex items-center justify-center text-gray-400 text-sm">
            <span>Đang tải biểu đồ...</span>
          </div>
        </CardBox>

        <!-- Order Status Distribution -->
        <CardBox>
          <div class="flex items-center justify-between mb-4 px-1">
            <h4 class="text-lg font-bold">Phân bổ trạng thái đơn hàng</h4>
          </div>
          <div v-if="statusDistribution.length" class="space-y-2 px-1">
            <div v-for="s in statusDistribution" :key="s.status" class="flex items-center gap-3">
              <span class="text-xs w-28 text-gray-600 shrink-0">{{ s.label }}</span>
              <div class="flex-1 bg-gray-100 rounded-full h-5 overflow-hidden">
                <div class="h-full rounded-full flex items-center justify-end pr-2 text-white text-xs font-medium transition-all duration-700"
                     :style="{ width: s.pct + '%', backgroundColor: s.color, minWidth: s.pct > 0 ? '28px' : '0' }">
                  {{ s.pct > 5 ? s.pct + '%' : '' }}
                </div>
              </div>
              <span class="text-xs text-gray-500 w-10 text-right shrink-0">{{ s.count }}</span>
            </div>
          </div>
          <div v-else class="h-48 flex items-center justify-center text-gray-400 text-sm">
            Đang tải...
          </div>
        </CardBox>

      </div>

      <!-- Revenue 30-day Chart & Payment Methods -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        <!-- Revenue 30 ngày -->
        <CardBox>
          <div class="flex items-center justify-between mb-4 px-1">
            <h4 class="text-lg font-bold">Xu hướng doanh thu 30 ngày</h4>
          </div>
          <div v-if="revenueChartData" class="h-64">
            <LineChart :data="revenueChartData" class="h-full" />
          </div>
          <div v-else class="h-64 flex items-center justify-center text-gray-400 text-sm">Đang tải...</div>
        </CardBox>

        <!-- Payment Method Comparison -->
        <CardBox>
          <div class="flex items-center justify-between mb-4 px-1">
            <h4 class="text-lg font-bold">Phương thức thanh toán</h4>
          </div>
          <div v-if="paymentDistribution.length" class="space-y-3 px-1">
            <div v-for="p in paymentDistribution" :key="p.method"
                 class="flex items-center gap-3">
              <div class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: p.color }"></div>
              <span class="text-sm font-medium w-28 shrink-0">{{ p.label }}</span>
              <div class="flex-1 bg-gray-100 rounded-full h-6 overflow-hidden">
                <div class="h-full rounded-full flex items-center px-2 text-white text-xs font-bold transition-all duration-700"
                     :style="{ width: p.pct + '%', backgroundColor: p.color, minWidth: p.pct > 0 ? '36px' : '0' }">
                  {{ p.pct }}%
                </div>
              </div>
              <span class="text-xs text-gray-500 w-10 text-right shrink-0">{{ p.count }}</span>
            </div>
          </div>
          <div v-else class="h-48 flex items-center justify-center text-gray-400 text-sm">Đang tải...</div>
        </CardBox>

      </div>

      <!-- Recent Orders & Low Stock -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Recent Orders -->
        <CardBox has-table>
          <div class="p-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
            <h4 class="text-lg font-bold">Đơn hàng mới nhất</h4>
            <router-link to="/orders" class="text-xs text-blue-600 hover:underline">Xem tất cả →</router-link>
          </div>
          <table>
            <thead>
              <tr>
                <th>Khách hàng</th>
                <th>Giá trị</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in recentOrders" :key="order.id">
                <td data-label="Khách hàng">
                  <p class="font-semibold mb-0 text-sm">{{ order.customer_name }}</p>
                  <p class="text-xs text-gray-400 mb-0">{{ order.tracking_number }}</p>
                </td>
                <td data-label="Giá trị">
                  <span class="font-bold text-emerald-600 text-sm">{{ formatPrice(order.total_amount) }}</span>
                </td>
                <td data-label="Trạng thái">
                  <span class="px-2 py-0.5 rounded text-xs font-medium" :class="getStatusClass(order.status)">
                    {{ getStatusLabel(order.status) }}
                  </span>
                </td>
                <td data-label="Ngày tạo">
                  <small class="text-gray-500">{{ new Date(order.created_at).toLocaleDateString('vi-VN') }}</small>
                </td>
              </tr>
              <tr v-if="!recentOrders.length">
                <td colspan="4" class="text-center py-6 text-gray-400">Chưa có đơn hàng</td>
              </tr>
            </tbody>
          </table>
        </CardBox>

        <!-- Low Stock Alerts -->
        <CardBox has-table>
          <div class="p-4 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
            <h4 class="text-lg font-bold flex items-center gap-2">
              <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="currentColor">
                <path :d="mdiAlertCircle" />
              </svg>
              Cảnh báo tồn kho thấp
            </h4>
            <span v-if="lowStockProducts.length" class="bg-red-100 text-red-700 text-xs font-bold px-2 py-0.5 rounded-full">
              {{ lowStockProducts.length }}
            </span>
          </div>
          <table v-if="lowStockProducts.length">
            <thead>
              <tr>
                <th>Sản phẩm</th>
                <th>Tồn kho</th>
                <th>Trạng thái</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in lowStockProducts" :key="product.id">
                <td data-label="Sản phẩm">
                  <p class="font-semibold mb-0 text-sm">{{ product.name }}</p>
                  <p class="text-xs text-gray-400 mb-0">SKU: {{ product.sku || 'N/A' }}</p>
                </td>
                <td data-label="Tồn kho">
                  <span class="font-bold text-lg" :class="product.stock === 0 ? 'text-red-600' : 'text-orange-500'">
                    {{ product.stock }}
                  </span>
                </td>
                <td data-label="Trạng thái">
                  <span v-if="product.stock === 0"
                        class="px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded font-medium">
                    Hết hàng
                  </span>
                  <span v-else
                        class="px-2 py-0.5 bg-orange-100 text-orange-700 text-xs rounded font-medium">
                    Sắp hết
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-else class="p-8 text-center text-emerald-600">
            <div class="text-3xl mb-2">✅</div>
            <p class="font-medium text-sm">Tất cả sản phẩm đủ hàng</p>
          </div>
        </CardBox>

      </div>

    </SectionMain>
  </LayoutAuthenticated>
</template>