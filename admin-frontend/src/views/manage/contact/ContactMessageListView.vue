<script setup>
import { ref, onMounted } from 'vue'
import { mdiEmailOutline, mdiEye, mdiTrashCan, mdiEmailOpenOutline, mdiEmailAlertOutline } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import BaseButton from '@/components/BaseButton.vue'
import api from '@/services/api'
import { showToast } from '@/composables/useToast'

const messages = ref([])
const currentPage = ref(1)
const lastPage = ref(1)
const unreadCount = ref(0)
const onlyUnread = ref(false)

const isModalActive = ref(false)
const selected = ref(null)

const fetchMessages = async (page = 1) => {
  try {
    const res = await api.get('/api/admin/contact-messages', {
      params: {
        page,
        unread: onlyUnread.value ? 1 : undefined,
      },
    })
    messages.value = res.data.data
    currentPage.value = res.data.current_page
    lastPage.value = res.data.last_page
    unreadCount.value = res.data.unread_count
  } catch (e) {
    console.error(e)
  }
}

const openDetail = async (id) => {
  try {
    const res = await api.get(`/api/admin/contact-messages/${id}`)
    selected.value = res.data
    isModalActive.value = true
    // Nếu tin nhắn vừa được đánh dấu đã đọc, cập nhật lại danh sách/badge
    fetchMessages(currentPage.value)
  } catch (e) {
    console.error(e)
  }
}

const toggleRead = async (msg) => {
  try {
    await api.patch(`/api/admin/contact-messages/${msg.id}/toggle-read`)
    fetchMessages(currentPage.value)
  } catch (e) {
    console.error(e)
  }
}

const deleteMessage = async (id) => {
  if (!confirm('Xóa tin nhắn liên hệ này?')) return
  try {
    await api.delete(`/api/admin/contact-messages/${id}`)
    isModalActive.value = false
    fetchMessages(currentPage.value)
    showToast('Đã xóa tin nhắn')
  } catch (e) {
    showToast(e.response?.data?.message || 'Không thể xóa tin nhắn!', 'error')
  }
}

const toggleFilter = () => {
  onlyUnread.value = !onlyUnread.value
  fetchMessages(1)
}

const formatDateTime = (d) => d ? new Date(d).toLocaleString('vi-VN') : '—'
const truncate = (text, len = 60) => {
  if (!text) return ''
  return text.length > len ? text.substring(0, len) + '…' : text
}

onMounted(() => fetchMessages())
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiEmailOutline" title="Tin nhắn liên hệ" main>
        <BaseButtons>
          <span v-if="unreadCount > 0" class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
            {{ unreadCount }} chưa đọc
          </span>
          <BaseButton
            :label="onlyUnread ? 'Đang lọc: Chưa đọc' : 'Xem tất cả'"
            :color="onlyUnread ? 'danger' : 'whiteDark'"
            small
            rounded-full
            @click="toggleFilter"
          />
        </BaseButtons>
      </SectionTitleLineWithButton>

      <CardBox has-table>
        <table>
          <thead>
            <tr>
              <th>Trạng thái</th>
              <th>Người gửi</th>
              <th>Chủ đề</th>
              <th>Nội dung</th>
              <th>Thời gian</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="m in messages"
              :key="m.id"
              :class="!m.is_read ? 'font-semibold' : ''"
              class="cursor-pointer"
              @click="openDetail(m.id)"
            >
              <td data-label="Trạng thái">
                <span
                  class="px-2 py-1 rounded-full text-xs"
                  :class="m.is_read ? 'bg-gray-100 text-gray-500' : 'bg-emerald-100 text-emerald-700'"
                >
                  {{ m.is_read ? 'Đã đọc' : 'Mới' }}
                </span>
              </td>
              <td data-label="Người gửi">
                <p class="mb-0">{{ m.name }}</p>
                <p class="mb-0 text-xs text-gray-500">{{ m.email }}</p>
                <p v-if="m.user" class="mb-0 text-xs text-blue-600">
                  🔑 Tài khoản: {{ m.user.name }} ({{ m.user.email }})
                </p>
                <p v-else class="mb-0 text-xs text-gray-400">Khách (chưa đăng nhập)</p>
              </td>
              <td data-label="Chủ đề">{{ m.subject || '(Không có)' }}</td>
              <td data-label="Nội dung">{{ truncate(m.message) }}</td>
              <td data-label="Thời gian">{{ formatDateTime(m.created_at) }}</td>
              <td class="before:hidden lg:w-1 whitespace-nowrap" @click.stop>
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton color="info" :icon="mdiEye" small @click="openDetail(m.id)" />
                  <BaseButton
                    :color="m.is_read ? 'whiteDark' : 'success'"
                    :icon="m.is_read ? mdiEmailAlertOutline : mdiEmailOpenOutline"
                    small
                    :title="m.is_read ? 'Đánh dấu chưa đọc' : 'Đánh dấu đã đọc'"
                    @click="toggleRead(m)"
                  />
                  <BaseButton color="danger" :icon="mdiTrashCan" small @click="deleteMessage(m.id)" />
                </BaseButtons>
              </td>
            </tr>
            <tr v-if="messages.length === 0">
              <td colspan="6" class="text-center py-8 text-gray-500">Chưa có tin nhắn liên hệ nào.</td>
            </tr>
          </tbody>
        </table>

        <div class="p-3 lg:px-6 border-t border-gray-100 dark:border-slate-800 flex justify-end items-center flex-wrap gap-2">
          <BaseButtons>
            <BaseButton
              v-for="page in lastPage"
              :key="page"
              :active="page === currentPage"
              :label="page"
              :color="page === currentPage ? 'lightDark' : 'whiteDark'"
              small
              @click="fetchMessages(page)"
            />
          </BaseButtons>
        </div>
      </CardBox>

      <!-- Modal chi tiết -->
      <CardBoxModal
        v-model="isModalActive"
        title="Chi tiết tin nhắn liên hệ"
        button-label="Đóng"
        has-cancel
      >
        <div v-if="selected" class="space-y-3">
          <div v-if="selected.user" class="p-3 rounded bg-blue-50 border border-blue-100">
            <p class="text-xs text-blue-600 mb-1">🔑 Gửi từ tài khoản đã đăng nhập</p>
            <p class="font-semibold">{{ selected.user.name }}</p>
            <p class="text-xs text-gray-600">{{ selected.user.email }}</p>
          </div>
          <div v-else class="p-3 rounded bg-gray-50 border border-gray-100">
            <p class="text-xs text-gray-500">Khách gửi khi chưa đăng nhập tài khoản</p>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <p class="text-xs text-gray-500 mb-1">Họ và tên (điền trong form)</p>
              <p class="font-semibold">{{ selected.name }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 mb-1">Email (điền trong form)</p>
              <p class="font-semibold">{{ selected.email }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 mb-1">Số điện thoại</p>
              <p class="font-semibold">{{ selected.phone || 'Không cung cấp' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 mb-1">Thời gian gửi</p>
              <p class="font-semibold">{{ formatDateTime(selected.created_at) }}</p>
            </div>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Chủ đề</p>
            <p class="font-semibold">{{ selected.subject || '(Không có)' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 mb-1">Nội dung</p>
            <p class="whitespace-pre-line bg-gray-50 rounded p-3">{{ selected.message }}</p>
          </div>
          <div class="flex justify-end pt-2">
            <BaseButton color="danger" :icon="mdiTrashCan" label="Xóa tin nhắn" small @click="deleteMessage(selected.id)" />
          </div>
        </div>
      </CardBoxModal>
    </SectionMain>
  </LayoutAuthenticated>
</template>