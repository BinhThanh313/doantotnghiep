<script setup>
import { ref, watch } from 'vue'
import {
  mdiPlus, mdiTrashCan, mdiChevronDown, mdiChevronUp, mdiRefresh,
} from '@mdi/js'
import CardBox from '@/components/CardBox.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import FormControl from '@/components/FormControl.vue'
import api from '@/services/api'
import { showToast } from '@/composables/useToast'

// ── Props ────────────────────────────────────────────────────
const props = defineProps({
  productId: { type: [Number, String], required: true },
})

// ── State ────────────────────────────────────────────────────
const specs      = ref([]) // [{ group_name, label, value, unit }]
const loading    = ref(false)
const saving     = ref(false)
const isExpanded = ref(false)

const fetchSpecs = async () => {
  loading.value = true
  try {
    const res = await api.get(`/api/admin/products/${props.productId}/specifications`)
    specs.value = res.data.map(s => ({
      group_name: s.group_name, label: s.label, value: s.value, unit: s.unit || '',
    }))
  } catch {
    showToast('Lỗi tải thông số kỹ thuật', 'error')
  } finally {
    loading.value = false
  }
}

watch(() => props.productId, fetchSpecs, { immediate: true })
watch(isExpanded, (v) => { if (v && !specs.value.length) fetchSpecs() })

const addRow = () => {
  const lastGroup = specs.value.length ? specs.value[specs.value.length - 1].group_name : ''
  specs.value.push({ group_name: lastGroup, label: '', value: '', unit: '' })
}

const removeRow = (index) => specs.value.splice(index, 1)

const save = async () => {
  saving.value = true
  try {
    const res = await api.put(`/api/admin/products/${props.productId}/specifications`, {
      specifications: specs.value,
    })
    specs.value = res.data.map(s => ({
      group_name: s.group_name, label: s.label, value: s.value, unit: s.unit || '',
    }))
    showToast('Đã lưu thông số kỹ thuật')
  } catch (e) {
    const errors = e.response?.data?.errors
    if (errors) showToast(Object.values(errors).flat().join(' | '), 'error')
    else showToast(e.response?.data?.message || 'Lỗi lưu thông số', 'error')
  } finally {
    saving.value = false
  }
}

const regenerate = async () => {
  if (!confirm('Xoá toàn bộ thông số hiện tại và sinh lại tự động theo danh mục + giá?')) return
  loading.value = true
  try {
    const res = await api.post(`/api/admin/products/${props.productId}/specifications/regenerate`)
    specs.value = res.data.map(s => ({
      group_name: s.group_name, label: s.label, value: s.value, unit: s.unit || '',
    }))
    showToast('Đã sinh lại thông số kỹ thuật')
  } catch {
    showToast('Lỗi sinh lại thông số', 'error')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <CardBox class="mt-4">
    <!-- Header toggle -->
    <div class="flex items-center justify-between p-4 cursor-pointer select-none"
         @click="isExpanded = !isExpanded">
      <div class="flex items-center gap-3">
        <span class="font-bold text-base">Thông số kỹ thuật</span>
        <span v-if="specs.length"
              class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">
          {{ specs.length }} dòng
        </span>
      </div>
      <div class="flex items-center gap-2">
        <template v-if="isExpanded">
          <BaseButton :icon="mdiRefresh" color="warning" small label="Sinh lại tự động" @click.stop="regenerate" />
          <BaseButton :icon="mdiPlus" color="success" small label="Thêm dòng" @click.stop="addRow" />
          <BaseButton color="info" small label="Lưu" :disabled="saving" @click.stop="save" />
        </template>
        <component :is="'svg'" class="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
          <path :d="isExpanded ? mdiChevronUp : mdiChevronDown" />
        </component>
      </div>
    </div>

    <div v-if="isExpanded" class="p-4">
      <p class="text-xs text-gray-400 mb-3">
        "Nhóm" là tiêu đề hiển thị (VD: Màn hình, Camera sau, Pin & sạc...) — các dòng cùng nhóm sẽ được gộp lại khi hiển thị ngoài trang sản phẩm.
      </p>

      <div v-if="loading" class="p-6 text-center text-gray-400">
        <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-blue-500 border-t-transparent"></div>
      </div>

      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-slate-800">
          <tr>
            <th class="px-2 py-2 text-left" style="width: 18%;">Nhóm</th>
            <th class="px-2 py-2 text-left" style="width: 22%;">Tên thông số</th>
            <th class="px-2 py-2 text-left">Giá trị</th>
            <th class="px-2 py-2 text-left" style="width: 10%;">Đơn vị</th>
            <th class="px-2 py-2 text-center" style="width: 5%;"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, index) in specs" :key="index"
              class="border-t border-gray-100 dark:border-slate-700">
            <td class="px-2 py-1">
              <FormControl v-model="row.group_name" placeholder="VD: Màn hình" />
            </td>
            <td class="px-2 py-1">
              <FormControl v-model="row.label" placeholder="VD: Kích thước màn hình" />
            </td>
            <td class="px-2 py-1">
              <FormControl v-model="row.value" type="textarea" placeholder="VD: 6.1 inches" />
            </td>
            <td class="px-2 py-1">
              <FormControl v-model="row.unit" placeholder="VD: GB" />
            </td>
            <td class="px-2 py-1 text-center">
              <BaseButton :icon="mdiTrashCan" color="danger" small @click="removeRow(index)" title="Xóa dòng" />
            </td>
          </tr>
          <tr v-if="!specs.length">
            <td colspan="5" class="text-center py-6 text-gray-400">
              Chưa có thông số nào. Nhấn "Sinh lại tự động" để tạo theo danh mục, hoặc "Thêm dòng" để tự nhập.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </CardBox>
</template>