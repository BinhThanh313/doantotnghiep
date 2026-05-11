<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { mdiAccountGroup, mdiTrashCan, mdiSquareEditOutline } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'

// Biến lưu trữ danh sách người dùng lấy từ API
const users = ref([])

// Hàm gọi API lấy danh sách User
const fetchUsers = async () => {
  try {
    const token = localStorage.getItem('admin_token')
    // Đảm bảo URL này đúng với URL Laravel Backend của bạn đang chạy
    const response = await axios.get('http://127.0.0.1:8000/api/admin/users', {
      headers: { Authorization: `Bearer ${token}` }
    })
    // Laravel paginate trả về data bọc trong object 'data'
    users.value = response.data.data 
  } catch (error) {
    console.error("Lỗi lấy dữ liệu:", error)
  }
}

// Hàm gọi API xóa User
const deleteUser = async (id) => {
  if (confirm('Bạn có chắc chắn muốn xóa người dùng này?')) {
    try {
      const token = localStorage.getItem('admin_token')
      await axios.delete(`http://127.0.0.1:8000/api/admin/users/${id}`, {
        headers: { Authorization: `Bearer ${token}` }
      })
      alert('Đã xóa thành công!')
      fetchUsers() // Gọi lại hàm fetch để cập nhật lại bảng
    } catch (error) {
      console.error("Lỗi khi xóa:", error)
      alert('Có lỗi xảy ra khi xóa.')
    }
  }
}

// Tự động chạy hàm fetchUsers khi load trang
onMounted(() => {
  fetchUsers()
})
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiAccountGroup" title="Quản lý Người dùng" main />

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
                  <BaseButton color="info" :icon="mdiSquareEditOutline" small title="Sửa" />
                  <BaseButton color="danger" :icon="mdiTrashCan" small title="Xóa" @click="deleteUser(user.id)" />
                </BaseButtons>
              </td>
            </tr>
            <tr v-if="users.length === 0">
              <td colspan="5" class="text-center py-4">Chưa có dữ liệu người dùng.</td>
            </tr>
          </tbody>
        </table>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>