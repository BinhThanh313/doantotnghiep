<script setup>
import { ref, onMounted } from 'vue'
import {
  mdiChartTimelineVariant,
  mdiCartOutline,
  mdiAccountMultiple,
  mdiPackageVariant,
  mdiCashMultiple
} from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBoxWidget from '@/components/CardBoxWidget.vue'
import CardBox from '@/components/CardBox.vue'
import LineChart from '@/components/Charts/LineChart.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import api from '@/services/api' // File api config có chứa token của bạn

// --- Biến lưu trữ dữ liệu ---
const widgets = ref({
  total_revenue: 0,
  new_orders: 0,
  total_users: 0,
  active_products: 0
})

const recentOrders = ref([])
const chartData = ref(null)

// --- Hàm định dạng tiền tệ (VND) ---
const formatPrice = (value) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
}

// --- Gọi API lấy dữ liệu Dashboard ---
const fetchDashboardData = async () => {
  try {
    const response = await api.get('/api/admin/dashboard')
    const data = response.data

    // Cập nhật Widgets
    widgets.value = data.widgets

    // Cập nhật Đơn hàng mới
    recentOrders.value = data.recent_orders

    // Cấu hình dữ liệu cho Biểu đồ
    chartData.value = {
      labels: data.chart.labels,
      datasets: [
        {
          fill: false,
          borderColor: '#10B981', // Màu xanh lá cho doanh thu
          borderWidth: 2,
          borderDash: [],
          borderDashOffset: 0.0,
          pointBackgroundColor: '#10B981',
          pointBorderColor: 'rgba(255,255,255,0)',
          pointHoverBackgroundColor: '#10B981',
          pointBorderWidth: 20,
          pointHoverRadius: 4,
          pointHoverBorderWidth: 15,
          pointRadius: 4,
          data: data.chart.data,
          tension: 0.5,
          cubicInterpolationMode: 'default'
        }
      ]
    }
  } catch (error) {
    console.error('Lỗi tải dữ liệu Dashboard:', error)
  }
}

// Dịch trạng thái đơn hàng sang Tiếng Việt
const getStatusLabel = (status) => {
  const labels = {
    pending: 'Chờ xử lý',
    processing: 'Đang chuẩn bị',
    completed: 'Đã hoàn thành',
    cancelled: 'Đã hủy'
  }
  return labels[status] || status
}

onMounted(() => {
  fetchDashboardData()
})
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiChartTimelineVariant" title="Tổng quan hệ thống" main />

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-4 mb-6">
        <CardBoxWidget
          trend-type="up"
          color="text-emerald-500"
          :icon="mdiCashMultiple"
          :number="widgets.total_revenue"
          label="Tổng doanh thu"
          suffix=" đ"
        />
        <CardBoxWidget
          trend-type="alert"
          color="text-red-500"
          :icon="mdiCartOutline"
          :number="widgets.new_orders"
          label="Đơn hàng chờ xử lý"
        />
        <CardBoxWidget
          trend-type="info"
          color="text-blue-500"
          :icon="mdiAccountMultiple"
          :number="widgets.total_users"
          label="Khách hàng"
        />
        <CardBoxWidget
          trend-type="up"
          color="text-yellow-500"
          :icon="mdiPackageVariant"
          :number="widgets.active_products"
          label="Sản phẩm hoạt động"
        />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        
        <CardBox class="mb-6">
          <div class="flex items-center justify-between mb-3">
            <h4 class="text-xl font-bold">Doanh thu 7 ngày qua</h4>
          </div>
          <div v-if="chartData" class="h-96">
            <LineChart :data="chartData" class="h-full" />
          </div>
          <div v-else class="h-96 flex items-center justify-center">
            Đang tải dữ liệu biểu đồ...
          </div>
        </CardBox>

        <CardBox class="mb-6" has-table>
          <div class="p-4 border-b border-gray-100 dark:border-slate-800">
            <h4 class="text-xl font-bold">Đơn hàng mới nhất</h4>
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
                  {{ order.customer_name }}
                </td>
                <td data-label="Giá trị">
                  <span class="font-bold text-emerald-600">{{ formatPrice(order.total_amount) }}</span>
                </td>
                <td data-label="Trạng thái">
                  <span class="px-2 py-1 rounded text-xs text-white" 
                        :class="{
                          'bg-yellow-500': order.status === 'pending',
                          'bg-blue-500': order.status === 'processing',
                          'bg-emerald-500': order.status === 'completed',
                          'bg-red-500': order.status === 'cancelled',
                        }">
                    {{ getStatusLabel(order.status) }}
                  </span>
                </td>
                <td data-label="Ngày tạo">
                  <small class="text-gray-500" :title="order.created_at">
                    {{ new Date(order.created_at).toLocaleDateString('vi-VN') }}
                  </small>
                </td>
              </tr>
              <tr v-if="recentOrders.length === 0">
                <td colspan="4" class="text-center py-4">Chưa có đơn hàng nào.</td>
              </tr>
            </tbody>
          </table>
        </CardBox>

      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>