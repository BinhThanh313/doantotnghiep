<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  mdiAccountGroup, mdiTrashCan, mdiSquareEditOutline, mdiPlus,
  mdiChevronLeft, mdiChevronRight, mdiChevronDoubleLeft, mdiChevronDoubleRight,
} from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import { showToast } from '@/composables/useToast'
import api from '@/services/api'

const users       = ref([])
const currentPage = ref(1)
const lastPage    = ref(1)
const totalItems  = ref(0)
const loading     = ref(false)

// 1. Lấy danh sách người dùng (có phân trang, khớp với response paginate() của Laravel)
const fetchUsers = async (page = 1) => {
  loading.value = true
  try {
    const res = await api.get('/api/admin/users', { params: { page } })
    users.value       = res.data.data
    currentPage.value = res.data.current_page
    lastPage.value    = res.data.last_page
    totalItems.value  = res.data.total
  } catch (error) {
    console.error('Lỗi lấy dữ liệu:', error)
    showToast('Không thể tải danh sách người dùng.', 'error')
  } finally {
    loading.value = false
  }
}

// 2. Xóa người dùng
const deleteUser = async (id) => {
  if (confirm('Bạn có chắc chắn muốn xóa người dùng này?')) {
    try {
      await api.delete(`/api/admin/users/${id}`)
      showToast('Đã xóa thành công!')
      // Nếu xóa hết user cuối cùng của trang hiện tại (trang > 1) thì lùi về trang trước
      const targetPage = users.value.length === 1 && currentPage.value > 1
        ? currentPage.value - 1
        : currentPage.value
      fetchUsers(targetPage)
    } catch (error) {
      console.error('Lỗi khi xóa:', error)
      showToast('Có lỗi xảy ra khi xóa.', 'error')
    }
  }
}

// Phân trang
const visiblePages = computed(() => {
  const range = []
  const delta = 2
  for (let i = Math.max(1, currentPage.value - delta); i <= Math.min(lastPage.value, currentPage.value + delta); i++) {
    range.push(i)
  }
  return range
})

onMounted(() => {
  fetchUsers()
})
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiAccountGroup" title="Quản lý Người dùng" main>
        <BaseButton 
          color="success" 
          :icon="mdiPlus" 
          label="Thêm Mới" 
          to="/users/form" 
          rounded-full 
        />
      </SectionTitleLineWithButton>

      <CardBox class="mb-6" has-table>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Tên</th>
              <th>Email</th>
              <th>Ngày tạo</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td data-label="ID">{{ user.id }}</td>
              <td data-label="Tên">{{ user.name }}</td>
              <td data-label="Email">{{ user.email }}</td>
              <td data-label="Ngày tạo">{{ new Date(user.created_at).toLocaleDateString('vi-VN') }}</td>
              
              <td class="before:hidden lg:w-1 whitespace-nowrap">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton 
                    color="info" 
                    :icon="mdiSquareEditOutline" 
                    small 
                    title="Sửa" 
                    :to="`/users/form/${user.id}`" 
                  />
                  <BaseButton 
                    color="danger" 
                    :icon="mdiTrashCan" 
                    small 
                    title="Xóa" 
                    @click="deleteUser(user.id)" 
                  />
                </BaseButtons>
              </td>
            </tr>
            <tr v-if="!loading && users.length === 0">
              <td colspan="5" class="text-center py-4">Chưa có dữ liệu người dùng.</td>
            </tr>
          </tbody>
        </table>

        <!-- Phân trang -->
        <div class="p-3 lg:px-6 border-t border-gray-100 dark:border-slate-800 flex justify-between items-center flex-wrap gap-2">
          <small class="text-gray-500">Tổng {{ totalItems }} người dùng | Trang {{ currentPage }}/{{ lastPage }}</small>
          <BaseButtons no-wrap>
            <BaseButton v-if="currentPage > 1" :icon="mdiChevronDoubleLeft" color="whiteDark" small @click="fetchUsers(1)" />
            <BaseButton v-if="currentPage > 1" :icon="mdiChevronLeft" color="whiteDark" small @click="fetchUsers(currentPage - 1)" />
            <BaseButton v-for="page in visiblePages" :key="page"
                        :active="page === currentPage" :label="page"
                        :color="page === currentPage ? 'lightDark' : 'whiteDark'"
                        small @click="fetchUsers(page)" />
            <BaseButton v-if="currentPage < lastPage" :icon="mdiChevronRight" color="whiteDark" small @click="fetchUsers(currentPage + 1)" />
            <BaseButton v-if="currentPage < lastPage" :icon="mdiChevronDoubleRight" color="whiteDark" small @click="fetchUsers(lastPage)" />
          </BaseButtons>
        </div>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>