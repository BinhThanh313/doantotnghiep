<script setup>
import { ref, onMounted } from 'vue'
import {
  mdiPackageVariantClosed, mdiTagOff, mdiTrendingUp, mdiBullhorn,
  mdiViewGridPlus, mdiCartOff, mdiImageOff, mdiAlertOctagon, mdiRefresh,
} from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import BaseButton from '@/components/BaseButton.vue'
import api from '@/services/api'
import { showToast } from '@/composables/useToast'

const loading = ref(true)
const data = ref({
  restock: [], slow_moving: [], trending: [], advertising: [],
  combos: [], abandoned_carts: { rate: 0, items: [] },
  incomplete: [], negative_reviews: [],
})

const imageUrl = (path) =>
  path ? `http://localhost/doantotnghiep/public/storage/${path}` : 'http://localhost/doantotnghiep/public/img/product-3.png'

const formatPrice = (v) =>
  new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v || 0)

const fetchInsights = async () => {
  loading.value = true
  try {
    const res = await api.get('/api/admin/insights')
    data.value = res.data
  } catch (e) {
    showToast(e.response?.data?.message || 'Không thể tải dữ liệu gợi ý!', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(fetchInsights)
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiBullhorn" title="Gợi ý cho Admin" main>
        <BaseButton :icon="mdiRefresh" color="whiteDark" label="Làm mới" @click="fetchInsights" />
      </SectionTitleLineWithButton>

      <div v-if="loading" class="text-center py-10 text-gray-500">Đang tải dữ liệu...</div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- #1 Restock -->
        <CardBox>
          <div class="flex items-center mb-4">
            <BaseButton :icon="mdiPackageVariantClosed" color="danger" small class="mr-2 pointer-events-none" />
            <h2 class="text-lg font-bold">Cần nhập thêm hàng</h2>
          </div>
          <p v-if="!data.restock.length" class="text-gray-400 text-sm">Không có sản phẩm nào sắp hết hàng.</p>
          <div v-for="p in data.restock" :key="p.product_id" class="flex items-center justify-between py-2 border-b last:border-0">
            <div class="flex items-center min-w-0">
              <img :src="imageUrl(p.image)" class="w-10 h-10 object-cover rounded mr-3 shrink-0" />
              <div class="min-w-0">
                <p class="font-medium truncate">{{ p.name }}</p>
                <p class="text-xs text-gray-500">Tồn kho: {{ p.stock }} · Bán ~{{ p.avg_daily_sales }}/ngày</p>
              </div>
            </div>
            <span class="text-red-600 text-sm font-semibold whitespace-nowrap ml-2">
              còn {{ p.days_left }} ngày
            </span>
          </div>
        </CardBox>

        <!-- #2 Slow moving -->
        <CardBox>
          <div class="flex items-center mb-4">
            <BaseButton :icon="mdiTagOff" color="warning" small class="mr-2 pointer-events-none" />
            <h2 class="text-lg font-bold">Bán chậm — nên giảm giá</h2>
          </div>
          <p v-if="!data.slow_moving.length" class="text-gray-400 text-sm">Không có sản phẩm bán chậm đáng chú ý.</p>
          <div v-for="p in data.slow_moving" :key="p.product_id" class="flex items-center justify-between py-2 border-b last:border-0">
            <div class="flex items-center min-w-0">
              <img :src="imageUrl(p.image)" class="w-10 h-10 object-cover rounded mr-3 shrink-0" />
              <div class="min-w-0">
                <p class="font-medium truncate">{{ p.name }}</p>
                <p class="text-xs text-gray-500">Tồn kho: {{ p.stock }} · {{ formatPrice(p.price) }}</p>
              </div>
            </div>
            <span class="text-amber-600 text-sm font-semibold whitespace-nowrap ml-2">
              đề xuất -{{ p.suggested_discount }}%
            </span>
          </div>
        </CardBox>

        <!-- #3 Trending -->
        <CardBox>
          <div class="flex items-center mb-4">
            <BaseButton :icon="mdiTrendingUp" color="success" small class="mr-2 pointer-events-none" />
            <h2 class="text-lg font-bold">Đang có xu hướng tăng</h2>
          </div>
          <p v-if="!data.trending.length" class="text-gray-400 text-sm">Chưa có sản phẩm nào tăng trưởng nổi bật tuần này.</p>
          <div v-for="p in data.trending" :key="p.product_id" class="flex items-center justify-between py-2 border-b last:border-0">
            <div class="flex items-center min-w-0">
              <img :src="imageUrl(p.image)" class="w-10 h-10 object-cover rounded mr-3 shrink-0" />
              <div class="min-w-0">
                <p class="font-medium truncate">{{ p.name }}</p>
                <p class="text-xs text-gray-500">{{ p.qty_this_week }} bán tuần này (trước: {{ p.qty_prev_week }})</p>
              </div>
            </div>
            <span class="text-emerald-600 text-sm font-semibold whitespace-nowrap ml-2">
              +{{ p.growth_percent }}%
            </span>
          </div>
        </CardBox>

        <!-- #4 Advertising -->
        <CardBox>
          <div class="flex items-center mb-4">
            <BaseButton :icon="mdiBullhorn" color="info" small class="mr-2 pointer-events-none" />
            <h2 class="text-lg font-bold">Nên đẩy mạnh quảng cáo</h2>
          </div>
          <p v-if="!data.advertising.length" class="text-gray-400 text-sm">Chưa đủ dữ liệu đơn hàng để đề xuất.</p>
          <div v-for="p in data.advertising" :key="p.product_id" class="flex items-center justify-between py-2 border-b last:border-0">
            <div class="flex items-center min-w-0">
              <img :src="imageUrl(p.image)" class="w-10 h-10 object-cover rounded mr-3 shrink-0" />
              <div class="min-w-0">
                <p class="font-medium truncate">{{ p.name }}</p>
                <p class="text-xs text-gray-500">Đã bán {{ p.qty_sold }}</p>
              </div>
            </div>
            <span class="text-blue-600 text-sm font-semibold whitespace-nowrap ml-2">
              {{ formatPrice(p.revenue) }}
            </span>
          </div>
        </CardBox>

        <!-- #5 Combos -->
        <CardBox>
          <div class="flex items-center mb-4">
            <BaseButton :icon="mdiViewGridPlus" color="primary" small class="mr-2 pointer-events-none" />
            <h2 class="text-lg font-bold">Gợi ý tạo combo</h2>
          </div>
          <p v-if="!data.combos.length" class="text-gray-400 text-sm">Chưa đủ dữ liệu tương đồng sản phẩm.</p>
          <div v-for="(c, i) in data.combos" :key="i" class="flex items-center justify-between py-2 border-b last:border-0">
            <div class="flex items-center min-w-0">
              <img :src="imageUrl(c.product_a.image)" class="w-9 h-9 object-cover rounded-full mr-1 shrink-0" />
              <img :src="imageUrl(c.product_b.image)" class="w-9 h-9 object-cover rounded-full mr-3 shrink-0 -ml-3 border-2 border-white" />
              <div class="min-w-0">
                <p class="font-medium truncate text-sm">{{ c.product_a.name }}</p>
                <p class="font-medium truncate text-sm">+ {{ c.product_b.name }}</p>
              </div>
            </div>
            <span class="text-purple-600 text-sm font-semibold whitespace-nowrap ml-2">
              {{ Math.round(c.score * 100) }}% liên quan
            </span>
          </div>
        </CardBox>

        <!-- #7 Abandoned carts -->
        <CardBox>
          <div class="flex items-center mb-4">
            <BaseButton :icon="mdiCartOff" color="danger" small class="mr-2 pointer-events-none" />
            <h2 class="text-lg font-bold">Giỏ hàng bị bỏ quên</h2>
            <span v-if="data.abandoned_carts.items.length" class="ml-auto text-sm text-gray-500">
              Tỷ lệ: {{ data.abandoned_carts.rate }}%
            </span>
          </div>
          <p v-if="!data.abandoned_carts.items.length" class="text-gray-400 text-sm">Không có giỏ hàng nào bị bỏ quên.</p>
          <div v-for="(item, i) in data.abandoned_carts.items" :key="i" class="flex items-center justify-between py-2 border-b last:border-0">
            <div class="min-w-0">
              <p class="font-medium truncate">{{ item.product_name }} × {{ item.quantity }}</p>
              <p class="text-xs text-gray-500 truncate">{{ item.user_name }} ({{ item.user_email }})</p>
            </div>
            <span class="text-gray-500 text-xs whitespace-nowrap ml-2">{{ item.hours_ago }}h trước</span>
          </div>
        </CardBox>

        <!-- #8 Incomplete products -->
        <CardBox>
          <div class="flex items-center mb-4">
            <BaseButton :icon="mdiImageOff" color="warning" small class="mr-2 pointer-events-none" />
            <h2 class="text-lg font-bold">Thiếu thông tin sản phẩm</h2>
          </div>
          <p v-if="!data.incomplete.length" class="text-gray-400 text-sm">Tất cả sản phẩm đã đầy đủ thông tin.</p>
          <div v-for="p in data.incomplete" :key="p.product_id" class="flex items-center justify-between py-2 border-b last:border-0">
            <div class="flex items-center min-w-0">
              <img :src="imageUrl(p.image)" class="w-10 h-10 object-cover rounded mr-3 shrink-0" />
              <p class="font-medium truncate">{{ p.name }}</p>
            </div>
            <span class="text-amber-600 text-xs whitespace-nowrap ml-2">
              {{ !p.has_gallery ? 'thiếu ảnh phụ' : `mô tả ${p.description_len} ký tự` }}
            </span>
          </div>
        </CardBox>

        <!-- #9 Negative reviews -->
        <CardBox>
          <div class="flex items-center mb-4">
            <BaseButton :icon="mdiAlertOctagon" color="danger" small class="mr-2 pointer-events-none" />
            <h2 class="text-lg font-bold">Đánh giá xấu cần kiểm tra</h2>
          </div>
          <p v-if="!data.negative_reviews.length" class="text-gray-400 text-sm">Không có sản phẩm nào bị đánh giá xấu bất thường.</p>
          <div v-for="p in data.negative_reviews" :key="p.product_id" class="flex items-center justify-between py-2 border-b last:border-0">
            <div class="flex items-center min-w-0">
              <img :src="imageUrl(p.image)" class="w-10 h-10 object-cover rounded mr-3 shrink-0" />
              <p class="font-medium truncate">{{ p.name }}</p>
            </div>
            <span class="text-red-600 text-sm font-semibold whitespace-nowrap ml-2">
              {{ p.bad_count }} đánh giá xấu ({{ p.avg_rating }}★)
            </span>
          </div>
        </CardBox>

      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>
