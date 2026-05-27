<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { mdiArrowLeft, mdiStar } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import BaseButton from '@/components/BaseButton.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()
const review = ref(null)

const fetchReview = async () => {
  try {
    const res = await api.get(`/api/admin/reviews/${route.params.id}`)
    review.value = res.data
  } catch (e) {
    alert('Không tìm thấy đánh giá!')
    router.push('/manage/reviews')
  }
}

const renderStars = (rating) => '⭐'.repeat(rating)
const formatDate = (d) => new Date(d).toLocaleString('vi-VN')

onMounted(() => fetchReview())
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiStar" title="Chi tiết Đánh giá" main>
        <BaseButton :icon="mdiArrowLeft" label="Quay lại" color="info" outline @click="router.back()" />
      </SectionTitleLineWithButton>

      <CardBox v-if="review">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          
          <div>
            <h3 class="text-lg font-bold mb-4 border-b pb-2">Nội dung đánh giá</h3>
            <p><strong>Người dùng:</strong> {{ review.user?.name }} ({{ review.user?.email }})</p>
            <p><strong>Điểm số:</strong> <span class="text-yellow-500">{{ renderStars(review.rating) }}</span></p>
            <p><strong>Ngày tạo:</strong> {{ formatDate(review.created_at) }}</p>
            <p>
              <strong>Trạng thái: </strong>
              <span class="px-2 py-1 rounded text-xs text-white" :class="review.is_visible ? 'bg-green-500' : 'bg-gray-500'">
                {{ review.is_visible ? 'Đang hiển thị' : 'Đã ẩn' }}
              </span>
            </p>
            <p><strong>Tiêu đề:</strong> {{ review.title || 'Không có' }}</p>
            <div class="mt-4 p-4 bg-gray-50 dark:bg-slate-800 rounded">
              <p><strong>Bình luận:</strong></p>
              <p class="whitespace-pre-wrap">{{ review.comment || 'Không có bình luận' }}</p>
            </div>
            
            <div v-if="review.images?.length" class="mt-4">
              <strong>Hình ảnh đính kèm:</strong>
              <div class="flex flex-wrap gap-2 mt-2">
                <img v-for="img in review.images" :key="img.id" :src="`/storage/${img.image_url}`" class="w-32 h-32 object-cover rounded shadow" />
              </div>
            </div>
          </div>

          <div>
            <h3 class="text-lg font-bold mb-4 border-b pb-2">Thông tin liên quan</h3>
            <div class="mb-4">
              <strong>Sản phẩm:</strong>
              <div v-if="review.product" class="flex items-center gap-4 mt-2 p-3 bg-gray-50 dark:bg-slate-800 rounded">
                <img :src="`/storage/${review.product.image}`" class="w-16 h-16 object-cover rounded" />
                <div>
                  <p class="font-bold">{{ review.product.name }}</p>
                  <p class="text-red-500 font-semibold">{{ Number(review.product.price).toLocaleString('vi-VN') }}đ</p>
                </div>
              </div>
            </div>

            <div v-if="review.order">
              <strong>Đơn hàng liên quan:</strong>
              <p class="mt-2">Mã đơn hàng: #{{ review.order.id }}</p>
              <p>Tổng tiền: {{ Number(review.order.total_amount).toLocaleString('vi-VN') }}đ</p>
              <p>Trạng thái mua hàng: <span class="text-green-600 font-bold">✓ Đã xác thực</span></p>
            </div>
          </div>
          
        </div>
      </CardBox>
      <CardBox v-else class="text-center py-10">Đang tải dữ liệu...</CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>