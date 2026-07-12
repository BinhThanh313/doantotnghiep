<script setup>
import { ref, onMounted } from 'vue'
import { mdiStar, mdiEyeOff, mdiEye, mdiTrashCan, mdiMagnify } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import FormControl from '@/components/FormControl.vue'
import FormField from '@/components/FormField.vue'
import api from '@/services/api'
import { showToast } from '@/composables/useToast'

const reviews = ref([])
const currentPage = ref(1)
const lastPage = ref(1)
const filterRating = ref('')
const filterVisible = ref('')

const ratingOptions = [
  { id: '', label: 'Tất cả sao' },
  { id: '5', label: '⭐⭐⭐⭐⭐ 5 sao' },
  { id: '4', label: '⭐⭐⭐⭐ 4 sao' },
  { id: '3', label: '⭐⭐⭐ 3 sao' },
  { id: '2', label: '⭐⭐ 2 sao' },
  { id: '1', label: '⭐ 1 sao' },
]

const visibleOptions = [
  { id: '', label: 'Tất cả' },
  { id: '1', label: 'Đang hiện' },
  { id: '0', label: 'Đã ẩn' },
]

const fetchReviews = async (page = 1) => {
  try {
    const params = { page }
    if (filterRating.value) params.rating = filterRating.value
    if (filterVisible.value !== '') params.is_visible = filterVisible.value
    const res = await api.get('/api/admin/reviews', { params })
    reviews.value = res.data.data
    currentPage.value = res.data.current_page
    lastPage.value = res.data.last_page
  } catch (e) {
    console.error(e)
  }
}

const toggleVisibility = async (id) => {
  try {
    const res = await api.patch(`/api/admin/reviews/${id}/toggle-visibility`)
    const idx = reviews.value.findIndex(r => r.id === id)
    if (idx !== -1) reviews.value[idx].is_visible = res.data.is_visible
    showToast(res.data.is_visible ? 'Đã hiện đánh giá' : 'Đã ẩn đánh giá')
  } catch (e) {
    showToast('Lỗi thay đổi trạng thái!', 'error')
  }
}

const deleteReview = async (id) => {
  if (!confirm('Xóa đánh giá này?')) return
  try {
    await api.delete(`/api/admin/reviews/${id}`)
    fetchReviews(currentPage.value)
    showToast('Đã xóa đánh giá')
  } catch (e) {
    showToast('Lỗi xóa đánh giá!', 'error')
  }
}

const renderStars = (rating) => '⭐'.repeat(rating)

const formatDate = (d) => new Date(d).toLocaleDateString('vi-VN')

onMounted(() => fetchReviews())
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiStar" title="Quản lý Đánh giá sản phẩm" main />

      <!-- Filters -->
      <CardBox class="mb-4">
        <div class="flex flex-wrap gap-4 items-end p-2">
          <FormField label="Lọc theo sao" class="mb-0">
            <FormControl v-model="filterRating" :options="ratingOptions" />
          </FormField>
          <FormField label="Hiển thị" class="mb-0">
            <FormControl v-model="filterVisible" :options="visibleOptions" />
          </FormField>
          <BaseButton color="info" label="Lọc" @click="fetchReviews(1)" />
        </div>
      </CardBox>

      <!-- Reviews Table -->
      <CardBox has-table>
        <table>
          <thead>
            <tr>
              <th>Sản phẩm</th>
              <th>Người dùng</th>
              <th>Đánh giá</th>
              <th>Nội dung</th>
              <th>Lượt helpful</th>
              <th>Ngày</th>
              <th>Trạng thái</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in reviews" :key="r.id" :class="{ 'opacity-50': !r.is_visible }">
              <td data-label="Sản phẩm">
                <span class="text-sm font-semibold line-clamp-1">{{ r.product?.name || 'N/A' }}</span>
              </td>
              <td data-label="Người dùng">
                <div>
                  <p class="font-semibold mb-0 text-sm">{{ r.user?.name || 'Ẩn danh' }}</p>
                  <span v-if="r.verified_purchase"
                    class="text-xs bg-emerald-100 text-emerald-700 px-1 py-0.5 rounded">✓ Đã mua</span>
                </div>
              </td>
              <td data-label="Sao">
                <div class="flex items-center gap-1">
                  <span class="text-yellow-500 text-sm">{{ renderStars(r.rating) }}</span>
                  <span class="text-xs text-gray-500">({{ r.rating }}/5)</span>
                </div>
              </td>
              <td data-label="Nội dung">
                <p v-if="r.title" class="font-semibold text-sm mb-1">{{ r.title }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-0">
                  {{ r.comment || '(Không có nội dung)' }}
                </p>
                <div v-if="r.images?.length" class="flex gap-1 mt-1">
                  <img v-for="img in r.images" :key="img.id"
                    :src="`/storage/${img.image_url}`"
                    class="w-8 h-8 rounded object-cover" />
                </div>
              </td>
              <td data-label="Helpful">
                <span class="text-sm">👍 {{ r.helpful_count }}</span>
              </td>
              <td data-label="Ngày">
                <small class="text-gray-500">{{ formatDate(r.created_at) }}</small>
              </td>
              <td data-label="Trạng thái">
                <span class="px-2 py-1 rounded-full text-xs font-medium"
                  :class="r.is_visible ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'">
                  {{ r.is_visible ? 'Đang hiện' : 'Đã ẩn' }}
                </span>
              </td>
              <td class="before:hidden lg:w-1 whitespace-nowrap">
                <BaseButtons no-wrap>
                  <BaseButton
                    :color="r.is_visible ? 'warning' : 'success'"
                    :icon="r.is_visible ? mdiEyeOff : mdiEye"
                    small
                    :title="r.is_visible ? 'Ẩn review' : 'Hiện review'"
                    @click="toggleVisibility(r.id)"
                  />
                  <BaseButton 
                    color="info" 
                    :icon="mdiMagnify" 
                    small 
                    title="Xem chi tiết" 
                    :to="`/manage/reviews/${r.id}`" 
                  />
                  <BaseButton color="danger" :icon="mdiTrashCan" small @click="deleteReview(r.id)" />
                </BaseButtons>
              </td>
            </tr>
            <tr v-if="reviews.length === 0">
              <td colspan="8" class="text-center py-8 text-gray-500">Chưa có đánh giá nào.</td>
            </tr>
          </tbody>
        </table>

        <div class="p-3 lg:px-6 border-t border-gray-100 dark:border-slate-800 flex justify-between items-center">
          <BaseButtons>
            <BaseButton v-for="page in lastPage" :key="page"
              :active="page === currentPage" :label="page"
              :color="page === currentPage ? 'lightDark' : 'whiteDark'"
              small @click="fetchReviews(page)" />
          </BaseButtons>
        </div>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>