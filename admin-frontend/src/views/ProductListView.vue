<script setup>
import { ref, onMounted } from 'vue'
import { mdiPackageVariant, mdiPlus, mdiPencil, mdiTrashCan } from '@mdi/js'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'
import SectionTitleLineWithButton from '@/components/SectionTitleLineWithButton.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import api from '@/services/api' // Giả sử bạn có file này để gọi axios kèm token

const products = ref([])
const currentPage = ref(1)
const lastPage = ref(1)

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
      fetchProducts(currentPage.value) // Load lại trang hiện tại
    } catch (error) {
      console.error('Lỗi xóa sản phẩm', error)
    }
  }
}

onMounted(() => {
  fetchProducts()
})
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>
      <SectionTitleLineWithButton :icon="mdiPackageVariant" title="Danh sách sản phẩm" main>
        <BaseButton
          to="/products/form"
          :icon="mdiPlus"
          label="Thêm mới"
          color="success"
          rounded-full
        />
      </SectionTitleLineWithButton>

      <CardBox class="mb-6" has-table>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Ảnh</th>
              <th>Tên sản phẩm</th>
              <th>Giá</th>
              <th>Danh mục</th>
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
              <td data-label="Danh mục">{{ product.category ? product.category.name : 'N/A' }}</td>
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