<script setup>
import { ref, onMounted } from 'vue'
import { mdiPackageVariant, mdiPlus, mdiPencil, mdiTrashCan, mdiFileExcel, mdiStar, mdiStarOutline } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import api from '@/services/api'

const products = ref([])
const currentPage = ref(1)
const lastPage = ref(1)

const importing = ref(false)
const importInput = ref(null)
const toast = ref({ show: false, message: '', type: 'success' })

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 4000)
}

const fetchProducts = async (page = 1) => {
  try {
    const response = await api.get(`/api/admin/products?page=${page}`)
    products.value = response.data.data
    currentPage.value = response.data.current_page
    lastPage.value = response.data.last_page
  } catch (error) {
    console.error('Lỗi khi lấy sản phẩm', error)
  }
}

const deleteProduct = async (id) => {
  if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
    try {
      await api.delete(`/api/admin/products/${id}`)
      fetchProducts(currentPage.value)
    } catch (error) {
      console.error('Lỗi xóa sản phẩm', error)
    }
  }
}

// ── Toggle hiển thị sản phẩm ở trang Bán chạy ──
const toggleBestseller = async (product) => {
  try {
    const res = await api.patch(`/api/admin/products/${product.id}/toggle-bestseller`)
    product.is_bestseller = res.data.is_bestseller
    showToast(res.data.message)
  } catch (e) {
    showToast('Lỗi cập nhật bestseller', 'error')
  }
}

// ── Nhập Excel ──
const triggerImport = () => importInput.value?.click()

const handleImportFile = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return

  const formData = new FormData()
  formData.append('file', file)

  importing.value = true
  try {
    const res = await api.post('/api/admin/products/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    let msg = res.data.message
    if (res.data.errors?.length) {
      msg += '\n' + res.data.errors.join('\n')
    }
    showToast(msg, res.data.errors?.length ? 'warning' : 'success')
    fetchProducts(currentPage.value)
  } catch (err) {
    showToast(err.response?.data?.message || 'Lỗi nhập file Excel', 'error')
  } finally {
    importing.value = false
    e.target.value = '' // reset input để chọn lại cùng file vẫn trigger change
  }
}

const stockClass = (stock) => {
  if (stock === 0) return 'text-red-600 font-bold'
  if (stock <= 5) return 'text-orange-500 font-bold'
  return 'text-gray-700 dark:text-slate-300'
}

onMounted(() => {
  fetchProducts()
})
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <!-- Toast -->
      <Transition name="slide-fade">
        <div v-if="toast.show"
             class="fixed top-4 right-4 z-50 px-5 py-3 rounded-lg shadow-lg text-white text-sm font-medium whitespace-pre-line max-w-md"
             :class="{
               'bg-emerald-500': toast.type === 'success',
               'bg-red-500': toast.type === 'error',
               'bg-orange-500': toast.type === 'warning',
             }">
          {{ toast.message }}
        </div>
      </Transition>

      <SectionTitleLineWithButton :icon="mdiPackageVariant" title="Danh sách sản phẩm" main>
        <div class="flex gap-2">
          <input ref="importInput" type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="handleImportFile" />
          <BaseButton
            :icon="mdiFileExcel"
            :label="importing ? 'Đang nhập...' : 'Nhập Excel'"
            color="info"
            :disabled="importing"
            rounded-full
            @click="triggerImport"
          />
          <BaseButton
            to="/products/form"
            :icon="mdiPlus"
            label="Thêm mới"
            color="success"
            rounded-full
          />
        </div>
      </SectionTitleLineWithButton>

      <CardBox class="mb-6" has-table>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Ảnh</th>
              <th>Tên sản phẩm</th>
              <th>Giá</th>
              <th>Tồn kho</th>
              <th>Danh mục</th>
              <th>Bán chạy</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in products" :key="product.id">
              <td data-label="ID">{{ product.id }}</td>
              <td data-label="Ảnh">
                <img v-if="product.image" :src="`http://localhost/doantotnghiep/public/storage/${product.image}`" class="w-12 h-12 object-cover rounded" />
              </td>
              <td data-label="Tên sản phẩm">{{ product.name }}</td>
              <td data-label="Giá">{{ product.price }} đ</td>
              <td data-label="Tồn kho">
                <span :class="stockClass(product.stock)">{{ product.stock }}</span>
              </td>
              <td data-label="Danh mục">{{ product.category ? product.category.name : 'N/A' }}</td>
              <td data-label="Bán chạy">
                <BaseButton
                  :icon="product.is_bestseller ? mdiStar : mdiStarOutline"
                  :color="product.is_bestseller ? 'warning' : 'whiteDark'"
                  small
                  :title="product.is_bestseller ? 'Đang hiện ở Bán chạy' : 'Thêm vào Bán chạy'"
                  @click="toggleBestseller(product)"
                />
              </td>
              <td class="before:hidden lg:w-1 whitespace-nowrap">
                <BaseButtons type="justify-start lg:justify-end" no-wrap>
                  <BaseButton
                    color="info"
                    :icon="mdiPencil"
                    small
                    :to="`/products/form/${product.id}`"
                  />
                  <BaseButton
                    color="danger"
                    :icon="mdiTrashCan"
                    small
                    @click="deleteProduct(product.id)"
                  />
                </BaseButtons>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="p-3 lg:px-6 border-t border-gray-100 dark:border-slate-800 flex justify-between items-center">
            <BaseButtons>
              <BaseButton v-for="page in lastPage" :key="page" :active="page === currentPage" :label="page" @click="fetchProducts(page)" small />
            </BaseButtons>
        </div>
      </CardBox>
    </SectionMain>
  </LayoutAuthenticated>
</template>

<style scoped>
.slide-fade-enter-active, .slide-fade-leave-active { transition: all .3s ease; }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateX(20px); opacity: 0; }
</style>