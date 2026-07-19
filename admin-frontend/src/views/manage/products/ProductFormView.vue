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
// ── Import component thông số kỹ thuật (cùng thư mục) ──────────
import ProductSpecificationManager from './ProductSpecificationManager.vue'
import ProductImageManager from './ProductImageManager.vue'
import ProductVariantManager from './ProductVariantManager.vue'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()
const isEditMode = ref(false)
const categories = ref([])

const form = reactive({
  name: '',
  category_id: '',
  price: '',
  original_price: '',
  description: '',
  stock: 0,
  is_active: true,
  is_new: false,
  is_bestseller: false,
})
const imageFile = ref(null)
const imageUrl  = ref('')

const fetchCategories = async () => {
  try {
    const res = await api.get('/api/admin/categories')
    categories.value = res.data.map(cat => ({ id: cat.id, label: cat.name }))
  } catch (error) { console.error('Lỗi tải danh mục', error) }
}

const fetchProduct = async (id) => {
  try {
    const res = await api.get(`/api/admin/products/${id}`)
    const prod = res.data
    form.name           = prod.name
    form.category_id    = { id: prod.category_id, label: prod.category.name }
    form.price          = prod.price
    form.original_price = prod.original_price
    form.description    = prod.description
    form.stock          = prod.stock
    form.is_active      = Boolean(prod.is_active)
    form.is_new         = Boolean(prod.is_new)
    form.is_bestseller = Boolean(prod.is_bestseller)
  } catch (error) { console.error('Lỗi tải sản phẩm', error) }
}

const submit = async () => {
  let formData = new FormData()
  formData.append('name', form.name)
  formData.append('category_id', form.category_id.id ? form.category_id.id : form.category_id)
  formData.append('price', form.price)
  if (form.original_price) formData.append('original_price', form.original_price)
  if (form.description)    formData.append('description', form.description)
  formData.append('stock', form.stock)
  formData.append('is_active', form.is_active ? 1 : 0)
  formData.append('is_new', form.is_new ? 1 : 0)
  formData.append('is_bestseller', form.is_bestseller ? 1 : 0)
  if (imageFile.value) formData.append('image', imageFile.value)
  else if (imageUrl.value) formData.append('image_url', imageUrl.value)

  const config = { headers: { 'Content-Type': 'multipart/form-data' } }

  try {
    if (isEditMode.value) {
      formData.append('_method', 'PUT')
      await api.post(`/api/admin/products/${route.params.id}`, formData, config)
      alert('Cập nhật thành công!')
    } else {
      await api.post('/api/admin/products', formData, config)
      alert('Thêm mới thành công!')
    }
    router.push('/products')
  } catch (error) {
    if (error.response?.status === 422) {
      const errors = error.response.data.errors
      let msg = 'Dữ liệu không hợp lệ:\n'
      for (let field in errors) msg += `- ${errors[field][0]}\n`
      alert(msg)
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
      <SectionTitleLineWithButton
        :icon="mdiPackageVariant"
        :title="isEditMode ? 'Cập nhật Sản phẩm' : 'Thêm Sản phẩm mới'"
        main
      />

      <!-- ── Form thông tin sản phẩm ─────────────────────────── -->
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

        <FormField :label="isEditMode ? 'Ảnh đại diện nhanh (tuỳ chọn)' : 'Hình ảnh'">
          <FormFilePicker v-model="imageFile" label="Chọn ảnh sản phẩm" />
          <div class="text-xs text-gray-400 mt-2 mb-1">— hoặc —</div>
          <FormControl v-model="imageUrl" placeholder="Dán URL ảnh (VD: https://...)" :disabled="!!imageFile" />
          <p v-if="isEditMode" class="text-xs text-gray-400 mt-1">
            Ảnh này sẽ ghi đè ảnh chính hiện tại. Để quản lý từng ảnh trong gallery (thêm/sửa/xoá/sắp xếp), dùng mục "Ảnh sản phẩm (gallery)" bên dưới sau khi lưu.
          </p>
        </FormField>

        <div class="flex gap-6 mt-4 mb-6">
          <label class="flex items-center cursor-pointer">
            <input type="checkbox" v-model="form.is_active" class="mr-2"> Đang hoạt động
          </label>
          <label class="flex items-center cursor-pointer">
            <input type="checkbox" v-model="form.is_new" class="mr-2"> Sản phẩm mới
          </label>
          <label class="flex items-center cursor-pointer">
            <input type="checkbox" v-model="form.is_bestseller" class="mr-2"> Hiển thị ở trang Bán chạy
          </label>
        </div>

        <template #footer>
          <BaseButtons>
            <BaseButton type="submit" color="info" label="Lưu thông tin" />
            <BaseButton type="button" color="danger" outline label="Hủy bỏ" to="/products" />
          </BaseButtons>
        </template>
      </CardBox>

      <!-- ── Panel quản lý gallery ảnh: chỉ hiện khi đang SỬA sản phẩm ── -->
      <ProductImageManager
        v-if="isEditMode && route.params.id"
        :product-id="Number(route.params.id)"
      />

      <!-- ── Panel quản lý biến thể (màu/size...): chỉ hiện khi đang SỬA ── -->
      <ProductVariantManager
        v-if="isEditMode && route.params.id"
        :product-id="Number(route.params.id)"
      />

      <!-- ── Panel thông số kỹ thuật: chỉ hiện khi đang SỬA sản phẩm ── -->
      <ProductSpecificationManager
        v-if="isEditMode && route.params.id"
        :product-id="Number(route.params.id)"
      />

      <!-- Hint khi tạo mới -->
      <div v-else class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm text-blue-600 dark:text-blue-400">
        💡 Sau khi lưu sản phẩm, bạn có thể quay lại trang <strong>Sửa</strong> để thêm nhiều ảnh vào gallery và chỉnh thông số kỹ thuật (đã được sinh tự động theo danh mục + giá).
      </div>

    </SectionMain>
  </LayoutAuthenticated>
</template>