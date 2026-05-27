<script setup>
import { ref, onMounted } from 'vue'
import { mdiFormatListBulleted, mdiPlus, mdiPencil, mdiTrashCan } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import api from '@/services/api' 

const categories = ref([])
const isModalActive = ref(false)
const isEditing = ref(false)
const form = ref({ id: null, name: '', description: '' })

// Lấy danh sách danh mục (Backend trả về mảng trực tiếp, không phân trang như Product)
const fetchCategories = async () => {
  try {
    const response = await api.get('/api/admin/categories')
    categories.value = response.data
  } catch (error) {
    console.error('Lỗi khi lấy danh mục', error)
  }
}

// Mở cửa sổ Modal (Popup) để thêm hoặc sửa nhanh
const openModal = (category = null) => {
  if (category) {
    isEditing.value = true
    form.value = { ...category }
  } else {
    isEditing.value = false
    form.value = { id: null, name: '', description: '' }
  }
  isModalActive.value = true
}

// Lưu dữ liệu danh mục
const saveCategory = async () => {
  try {
    if (isEditing.value) {
      await api.put(`/api/admin/categories/${form.value.id}`, form.value)
    } else {
      await api.post('/api/admin/categories', form.value)
    }
    isModalActive.value = false
    fetchCategories() // Tải lại danh sách sau khi lưu thành công
  } catch (error) {
    alert('Có lỗi xảy ra: ' + (error.response?.data?.message || error.message))
  }
}

// Xóa danh mục
const deleteCategory = async (id) => {
  if (confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
    try {
      await api.delete(`/api/admin/categories/${id}`)
      fetchCategories()
    } catch (error) {
      // Nhận thông báo lỗi từ Laravel nếu danh mục đang chứa sản phẩm
      alert(error.response?.data?.message || 'Lỗi khi xóa danh mục')
    }
  }
}

onMounted(() => {
  fetchCategories()
})
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiFormatListBulleted" title="Quản lý danh mục sản phẩm" main>
        <BaseButton
          :icon="mdiPlus"
          label="Thêm mới"
          color="success"
          rounded-full
          @click="openModal()"
        />
      </SectionTitleLineWithButton>

      <CardBox class="mb-6" has-table>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Tên danh mục</th>
              <th>Slug (Đường dẫn)</th>
              <th>Mô tả</th>
              <th>Số sản phẩm</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="category in categories" :key="category.id">
              <td data-label="ID">{{ category.id }}</td>
              <td data-label="Tên danh mục">{{ category.name }}</td>
              <td data-label="Slug">{{ category.slug }}</td>
              <td data-label="Mô tả">{{ category.description || 'Không có mô tả' }}</td>
              <td data-label="Số sản phẩm">{{ category.products_count ?? 0 }}</td> 
              <td class="before:hidden lg:w-1 whitespace-nowrap">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton
                    color="info"
                    :icon="mdiPencil"
                    small
                    @click="openModal(category)"
                  />
                  <BaseButton
                    color="danger"
                    :icon="mdiTrashCan"
                    small
                    @click="deleteCategory(category.id)"
                  />
                </BaseButtons>
              </td>
            </tr>
            <tr v-if="categories.length === 0">
              <td colspan="6" class="text-center py-4">Không có dữ liệu hoặc danh mục trống.</td>
            </tr>
          </tbody>
        </table>
      </CardBox>

      <CardBoxModal
        v-model="isModalActive"
        :title="isEditing ? 'Cập nhật danh mục' : 'Thêm danh mục mới'"
        button="info"
        has-cancel
        @confirm="saveCategory"
      >
        <FormField label="Tên danh mục">
          <FormControl v-model="form.name" placeholder="Nhập tên danh mục (ví dụ: Áo thun)..." />
        </FormField>
        <FormField label="Mô tả">
          <FormControl v-model="form.description" type="textarea" placeholder="Nhập mô tả danh mục..." />
        </FormField>
      </CardBoxModal>
    </SectionMain>
  </LayoutAuthenticated>
</template>