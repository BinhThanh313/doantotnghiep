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
import CardBoxModal from '@/components/CardBoxModal.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import api from '@/services/api'
import { showToast } from '@/composables/useToast'
import { imgUrl, thumbUrl } from '@/utils/image'

const loading = ref(true)
const data = ref({
  restock: [], slow_moving: [], trending: [], advertising: [],
  combos: [], abandoned_carts: { rate: 0, items: [] },
  incomplete: [], negative_reviews: [],
})

const createdCombos = ref([])
const creatingComboKey = ref(null)

// Tối ưu hóa ảnh thumbnail để tăng tốc độ load trang
const imageUrl = (path) => thumbUrl(path, 150, `${api.defaults.baseURL}/img/product-3.png`)

const formatPrice = (v) =>
  new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(v || 0)

const fetchCreatedCombos = async () => {
  try {
    const res = await api.get('/api/admin/combos')
    createdCombos.value = res.data
  } catch (e) {
    console.error(e)
  }
}

const isComboCreated = (c) => createdCombos.value.some(
  (existing) =>
    (existing.product_id === c.product_a.id && existing.combo_product_id === c.product_b.id) ||
    (existing.product_id === c.product_b.id && existing.combo_product_id === c.product_a.id)
)

const isComboModalActive = ref(false)
const comboForm = ref({ discount_percent: 5, c: null })

const openComboModal = (c) => {
  comboForm.value = { discount_percent: 5, c }
  isComboModalActive.value = true
}

const confirmCreateCombo = async () => {
  const { discount_percent, c } = comboForm.value
  const percent = parseInt(discount_percent)
  if (isNaN(percent) || percent < 1 || percent > 99) {
    showToast('Phần trăm giảm giá không hợp lệ!', 'error')
    return
  }

  isComboModalActive.value = false // close modal

  const key = `${c.product_a.id}-${c.product_b.id}`
  creatingComboKey.value = key
  try {
    await api.post('/api/admin/combos', {
      product_id: c.product_a.id,
      combo_product_id: c.product_b.id,
      discount_percent: percent,
      similarity_score: c.score,
    })
    showToast('Đã tạo combo — sẽ hiển thị ở trang chi tiết sản phẩm')
    await fetchCreatedCombos()
  } catch (e) {
    showToast(e.response?.data?.message || 'Không thể tạo combo!', 'error')
  } finally {
    creatingComboKey.value = null
  }
}

const toggleCombo = async (combo) => {
  try {
    await api.patch(`/api/admin/combos/${combo.id}/toggle`)
    fetchCreatedCombos()
  } catch (e) {
    showToast('Không thể cập nhật combo!', 'error')
  }
}

const deleteCombo = async (combo) => {
  if (!confirm('Xóa combo này?')) return
  try {
    await api.delete(`/api/admin/combos/${combo.id}`)
    fetchCreatedCombos()
    showToast('Đã xóa combo')
  } catch (e) {
    showToast('Không thể xóa combo!', 'error')
  }
}

const fetchInsights = async (forceRefresh = false) => {
  loading.value = true
  try {
    const url = forceRefresh === true ? '/api/admin/insights?refresh=1' : '/api/admin/insights'
    const res = await api.get(url)
    data.value = res.data
    await fetchCreatedCombos()
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
    <CardBoxModal
      v-model="isComboModalActive"
      title="Thiết lập giảm giá Combo"
      button="success"
      buttonLabel="Tạo Combo"
      hasCancel
      isForm
      @confirm="confirmCreateCombo"
    >
      <div v-if="comboForm.c" class="mb-4">
        Tạo combo cho: <b>{{ comboForm.c.product_a.name }}</b> + <b>{{ comboForm.c.product_b.name }}</b>
      </div>
      <FormField label="Phần trăm giảm giá (1 - 99%)">
        <FormControl v-model="comboForm.discount_percent" type="number" min="1" max="99" required />
      </FormField>
    </CardBoxModal>

    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiBullhorn" title="Gợi ý cho Admin" main>
        <BaseButton :icon="mdiRefresh" color="success" label="Làm mới" rounded-full @click="() => fetchInsights(true)" />
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
              {{ p.days_left !== null ? `còn ${p.days_left} ngày` : 'Sắp hết' }}
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
            <div class="flex items-center whitespace-nowrap ml-2">
              <span class="text-purple-600 text-sm font-semibold mr-2">{{ Math.round(c.score * 100) }}%</span>
              <BaseButton
                v-if="!isComboCreated(c)"
                size="small"
                color="success"
                label="Tạo combo"
                :disabled="creatingComboKey === `${c.product_a.id}-${c.product_b.id}`"
                @click="openComboModal(c)"
              />
              <span v-else class="text-xs text-emerald-600 font-medium">Đã tạo ✓</span>
            </div>
          </div>
        </CardBox>

        <!-- #5b Created combos -->
        <CardBox>
          <div class="flex items-center mb-4">
            <BaseButton :icon="mdiViewGridPlus" color="success" small class="mr-2 pointer-events-none" />
            <h2 class="text-lg font-bold">Combo đã tạo</h2>
          </div>
          <p v-if="!createdCombos.length" class="text-gray-400 text-sm">Chưa có combo nào — bấm "Tạo combo" ở card bên cạnh.</p>
          <div v-for="combo in createdCombos" :key="combo.id" class="flex items-center justify-between py-2 border-b last:border-0">
            <div class="flex items-center min-w-0">
              <img :src="imageUrl(combo.product?.image)" class="w-9 h-9 object-cover rounded-full mr-1 shrink-0" />
              <img :src="imageUrl(combo.combo_product?.image)" class="w-9 h-9 object-cover rounded-full mr-3 shrink-0 -ml-3 border-2 border-white" />
              <div class="min-w-0">
                <p class="font-medium truncate text-sm">{{ combo.product?.name }}</p>
                <p class="font-medium truncate text-sm">+ {{ combo.combo_product?.name }}</p>
              </div>
            </div>
            <div class="flex items-center whitespace-nowrap ml-2 gap-2">
              <span class="text-xs" :class="combo.is_active ? 'text-emerald-600' : 'text-gray-400'">
                {{ combo.is_active ? 'Đang hiển thị' : 'Đã ẩn' }}
              </span>
              <BaseButton size="small" color="whiteDark" :label="combo.is_active ? 'Ẩn' : 'Hiện'" @click="toggleCombo(combo)" />
              <BaseButton size="small" color="danger" label="Xóa" @click="deleteCombo(combo)" />
            </div>
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