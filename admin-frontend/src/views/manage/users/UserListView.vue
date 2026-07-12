<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { mdiAccountGroup, mdiTrashCan, mdiSquareEditOutline, mdiPlus } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import { showToast } from '@/composables/useToast'

const apiUrl = 'http://localhost/doantotnghiep/public/api/admin/users'
const users = ref([])

const getHeaders = () => {
  return { Authorization: `Bearer ${localStorage.getItem('admin_token')}` }
}

// 1. Lấy danh sách người dùng
const fetchUsers = async () => {
  try {
    const response = await axios.get(apiUrl, { headers: getHeaders() })
    users.value = response.data.data || response.data
  } catch (error) {
    console.error("Lỗi lấy dữ liệu:", error)
  }
}

// 2. Xóa người dùng
const deleteUser = async (id) => {
  if (confirm('Bạn có chắc chắn muốn xóa người dùng này?')) {
    try {
      await axios.delete(`${apiUrl}/${id}`, { headers: getHeaders() })
      showToast('Đã xóa thành công!')
      fetchUsers()
    } catch (error) {
      console.error("Lỗi khi xóa:", error)
      showToast('Có lỗi xảy ra khi xóa.', 'error')
    }
  }
}

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
            <tr v-if="users.length === 0">
              <td colspan="5" class="text-center py-4">Chưa có dữ liệu người dùng.</td>
            </tr>
          </tbody>
        </table>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>