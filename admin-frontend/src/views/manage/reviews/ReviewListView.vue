<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  mdiStar, mdiEyeOff, mdiEye, mdiTrashCan, mdiMagnify,
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
import { imgUrl } from '@/utils/image'

const reviews = ref([])
const currentPage = ref(1)
const lastPage = ref(1)
const totalItems = ref(0)
const filterRating = ref('')
const filterVisible = ref('')

const isDeleteModalActive = ref(false)
const itemToDelete = ref(null)

const confirmDelete = (id) => {
  itemToDelete.value = id
  isDeleteModalActive.value = true
}

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
    totalItems.value = res.data.total
  } catch (e) {
    console.error(e)
  }
}

// Phân trang kiểu cửa sổ trượt (giống UserListView) — tránh hiện tất cả
// số trang liền một hàng khi có nhiều trang (vd: review 26 trang).
const visiblePages = computed(() => {
  const range = []
  const delta = 2
  for (let i = Math.max(1, currentPage.value - delta); i <= Math.min(lastPage.value, currentPage.value + delta); i++) {
    range.push(i)
  }
  return range
})

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

const deleteReview = async () => {
  if (!itemToDelete.value) return
  try {
    await api.delete(`/api/admin/reviews/${itemToDelete.value}`)
    fetchReviews(currentPage.value)
    showToast('Đã xóa đánh giá')
  } catch (e) {
    showToast('Lỗi xóa đánh giá!', 'error')
  } finally {
    itemToDelete.value = null
  }
}

const renderStars = (rating) => '⭐'.repeat(rating)

const formatDate = (d) => new Date(d).toLocaleDateString('vi-VN')

onMounted(() => fetchReviews())
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiStar" title="Quản lý Đánh giá sản phẩm" main>
        <span></span>
      </SectionTitleLineWithButton>

      <!-- Filters -->
      <CardBox class="mb-4">
  <div class="flex items-end gap-4 w-full p-2">
    <FormField label="Lọc theo sao" class="flex-1 mb-0">
      <FormControl
        v-model="filterRating"
        :options="ratingOptions"
        class="w-full"
      />
    </FormField>

    <FormField label="Hiển thị" class="flex-1 mb-0">
      <FormControl
        v-model="filterVisible"
        :options="visibleOptions"
        class="w-full"
      />
    </FormField>

    <BaseButton
      color="info"
      label="Lọc"
      rounded-full
      class="flex-none"
      @click="fetchReviews(1)"
    />
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
                    :src="imgUrl(img.image_url)"
                    class="w-8 h-8 rounded object-cover" loading="lazy" />
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
                    @click="confirmDelete(r.id)"
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

        <div class="p-3 lg:px-6 border-t border-gray-100 dark:border-slate-800 flex justify-between items-center flex-wrap gap-2">
          <small class="text-gray-500">Tổng {{ totalItems }} đánh giá | Trang {{ currentPage }}/{{ lastPage }}</small>
          <BaseButtons no-wrap>
            <BaseButton v-if="currentPage > 1" :icon="mdiChevronDoubleLeft" color="whiteDark" small @click="fetchReviews(1)" />
            <BaseButton v-if="currentPage > 1" :icon="mdiChevronLeft" color="whiteDark" small @click="fetchReviews(currentPage - 1)" />
            <BaseButton v-for="page in visiblePages" :key="page"
              :active="page === currentPage" :label="page"
              :color="page === currentPage ? 'lightDark' : 'whiteDark'"
              small @click="fetchReviews(page)" />
            <BaseButton v-if="currentPage < lastPage" :icon="mdiChevronRight" color="whiteDark" small @click="fetchReviews(currentPage + 1)" />
            <BaseButton v-if="currentPage < lastPage" :icon="mdiChevronDoubleRight" color="whiteDark" small @click="fetchReviews(lastPage)" />
          </BaseButtons>
        </div>
      </CardBox>

      <!-- ══ CONFIRM DELETE MODAL ══ -->
      <CardBoxModal
        v-model="isDeleteModalActive"
        title="Xác nhận xóa"
        button-label="Xóa"
        has-cancel
        @confirm="deleteReview"
      >
        <p>Xóa đánh giá này?</p>
      </CardBoxModal>
    </SectionMain>
  </LayoutAuthenticated>
</template>