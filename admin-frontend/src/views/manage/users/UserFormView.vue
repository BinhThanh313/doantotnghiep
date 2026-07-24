<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { mdiAccountGroup } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'

const route = useRoute()
const router = useRouter()
const isEditMode = ref(false)
const apiUrl = api.defaults.baseURL + '/api/admin/users'

const form = reactive({
  name: '',
  email: '',
  password: ''
})

const getHeaders = () => {
  return { Authorization: `Bearer ${localStorage.getItem('admin_token')}` }
}

// Lấy dữ liệu cũ đổ vào form nếu ở chế độ Sửa
const fetchUser = async (id) => {
  try {
    const response = await axios.get(`${apiUrl}/${id}`, { headers: getHeaders() })
    const user = response.data.data || response.data
    form.name = user.name
    form.email = user.email
    form.password = '' // Để trống mật khẩu khi sửa
  } catch (error) {
    console.error('Lỗi tải thông tin thành viên:', error)
    alert('Không thể tải thông tin thành viên này.')
  }
}

// Xử lý gửi dữ liệu lên Laravel (Thêm hoặc Cập nhật)
const submit = async () => {
  try {
    if (isEditMode.value) {
      // Chế độ Sửa -> Loại bỏ trường mật khẩu gửi lên nếu người dùng không điền đổi mật khẩu
      const updateData = { ...form }
      if (!updateData.password) {
        delete updateData.password
      }
      await axios.put(`${apiUrl}/${route.params.id}`, updateData, { headers: getHeaders() })
      alert('Cập nhật người dùng thành công!')
    } else {
      // Chế độ Thêm mới -> POST thông thường
      await axios.post(apiUrl, form, { headers: getHeaders() })
      alert('Thêm người dùng mới thành công!')
    }
    // Thành công -> Quay lại trang danh sách người dùng
    router.push('/users')
  } catch (error) {
    console.error('Lỗi lưu thông tin:', error)
    alert('Có lỗi xảy ra (Email có thể đã bị trùng hoặc dữ liệu không hợp lệ).')
  }
}

onMounted(() => {
  // Kiểm tra xem trên thanh địa chỉ URL có ID không, nếu có thì bật EditMode
  if (route.params.id) {
    isEditMode.value = true
    fetchUser(route.params.id)
  }
})
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton 
        :icon="mdiAccountGroup" 
        :title="isEditMode ? 'Cập nhật thành viên' : 'Thêm thành viên mới'" 
        main 
      />
      
      <CardBox is-form @submit.prevent="submit">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <FormField label="Họ Tên (*)">
            <FormControl v-model="form.name" required placeholder="Nhập họ và tên..." />
          </FormField>

          <FormField label="Địa chỉ Email (*)">
            <FormControl v-model="form.email" type="email" required placeholder="Nhập email..." />
          </FormField>
        </div>

        <FormField :label="isEditMode ? 'Mật khẩu mới (Để trống nếu không muốn thay đổi)' : 'Mật khẩu (*)'">
          <FormControl 
            v-model="form.password" 
            type="password" 
            :required="!isEditMode" 
            placeholder="Nhập mật khẩu an toàn..." 
          />
        </FormField>

        <template #footer>
          <BaseButtons>
            <BaseButton type="submit" color="info" label="Lưu thông tin" />
            <BaseButton type="button" color="danger" outline label="Hủy bỏ" to="/users" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>