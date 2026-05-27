<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { mdiPackageVariant } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import FormFilePicker from '@/components/FormFilePicker.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import api from '@/services/api' // Đường dẫn file API của bạn

const route = useRoute()
const router = useRouter()
const isEditMode = ref(false)
const categories = ref([])

// Dữ liệu Form
const form = reactive({
  name: '',
  category_id: '',
  price: '',
  original_price: '',
  description: '',
  stock: 0,
  is_active: true, // Trạng thái mặc định
  is_new: false,
})
const imageFile = ref(null)

const fetchCategories = async () => {
  try {
    const res = await api.get('/api/admin/categories')
    // Ánh xạ lại cho phù hợp với FormControl (cần object {id, label})
    categories.value = res.data.map(cat => ({ id: cat.id, label: cat.name }))
  } catch (error) { console.error('Lỗi tải danh mục', error) }
}

const fetchProduct = async (id) => {
  try {
    const res = await api.get(`/api/admin/products/${id}`)
    const prod = res.data
    form.name = prod.name
    form.category_id = { id: prod.category_id, label: prod.category.name }
    form.price = prod.price
    form.original_price = prod.original_price
    form.description = prod.description
    form.stock = prod.stock
    form.is_active = Boolean(prod.is_active)
form.is_new = Boolean(prod.is_new)
  } catch (error) { console.error('Lỗi tải sản phẩm', error) }
}

const submit = async () => {
  let formData = new FormData()
  formData.append('name', form.name)
  formData.append('category_id', form.category_id.id ? form.category_id.id : form.category_id) 
  formData.append('price', form.price)
  if(form.original_price) formData.append('original_price', form.original_price)
  if(form.description) formData.append('description', form.description)
  formData.append('stock', form.stock)
  formData.append('is_active', form.is_active ? 1 : 0)
  formData.append('is_new', form.is_new ? 1 : 0)
  
  if (imageFile.value) {
    formData.append('image', imageFile.value)
  }

  // THÊM ĐOẠN CẤU HÌNH HEADER NÀY
  const config = {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  }

  try {
    if (isEditMode.value) {
      formData.append('_method', 'PUT')
      // Thêm biến config vào tham số thứ 3 của axios
      await api.post(`/api/admin/products/${route.params.id}`, formData, config)
      alert('Cập nhật thành công!')
    } else {
      // Thêm biến config vào tham số thứ 3 của axios
      await api.post('/api/admin/products', formData, config)
      alert('Thêm mới thành công!')
    }
    router.push('/products')
  } catch (error) {
    // Phần bắt lỗi chi tiết như đã làm
    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors
      let errorMessage = 'Dữ liệu không hợp lệ:\n'
      for (let field in errors) {
        errorMessage += `- ${errors[field][0]}\n`
      }
      alert(errorMessage)
    } else {
      console.error('Lỗi lưu sản phẩm', error.response?.data || error)
      alert('Có lỗi xảy ra, vui lòng kiểm tra lại console.')
    }
  }
}

onMounted(() => {
  fetchCategories()
  if (route.params.id) {
    isEditMode.value = true
    fetchProduct(route.params.id)
  }
})
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiPackageVariant" :title="isEditMode ? 'Cập nhật Sản phẩm' : 'Thêm Sản phẩm mới'" main />
      
      <CardBox is-form @submit.prevent="submit">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <FormField label="Tên sản phẩm (*)">
            <FormControl v-model="form.name" required />
            </FormField>

            <FormField label="Danh mục (*)">
            <FormControl v-model="form.category_id" :options="categories" required />
            </FormField>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <FormField label="Giá bán (*)">
            <FormControl v-model="form.price" type="number" required />
            </FormField>

            <FormField label="Giá gốc">
            <FormControl v-model="form.original_price" type="number" />
            </FormField>

            <FormField label="Tồn kho">
            <FormControl v-model="form.stock" type="number" required />
            </FormField>
        </div>

        <FormField label="Mô tả sản phẩm">
          <FormControl type="textarea" v-model="form.description" />
        </FormField>

        <FormField label="Hình ảnh">
          <FormFilePicker v-model="imageFile" label="Chọn ảnh sản phẩm" />
        </FormField>

        <div class="flex gap-6 mt-4 mb-6">
    <label class="flex items-center cursor-pointer">
        <input type="checkbox" v-model="form.is_active" class="mr-2"> Đang hoạt động
    </label>
    <label class="flex items-center cursor-pointer">
        <input type="checkbox" v-model="form.is_new" class="mr-2"> Sản phẩm mới
    </label>
</div>
        <template #footer>
          <BaseButtons>
            <BaseButton type="submit" color="info" label="Lưu thông tin" />
            <BaseButton type="button" color="danger" outline label="Hủy bỏ" to="/products" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>