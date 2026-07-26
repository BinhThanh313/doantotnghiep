<script setup>
import { ref, watch, computed } from 'vue'
import {
  mdiTrashCan, mdiChevronDown, mdiChevronUp, mdiStar, mdiStarOutline,
  mdiArrowUp, mdiArrowDown, mdiPencil, mdiPlus, mdiClose, mdiLink,
} from '@mdi/js'
import CardBox from '@/components/CardBox.vue'
import CardBoxModal from '@/components/CardBoxModal.vue'
import BaseButton from '@/components/BaseButton.vue'
import FormControl from '@/components/FormControl.vue'
import api from '@/services/api'
import { showToast } from '@/composables/useToast'
import { imgUrl } from '@/utils/image'

// ── Props ────────────────────────────────────────────────────
const props = defineProps({
  productId: { type: [Number, String], required: true },
})

const MAX_IMAGES = 6

// ── State ────────────────────────────────────────────────────
const images     = ref([]) // [{ id, image_url, alt_text, sort_order, is_primary }]
const loading    = ref(false)
const uploading  = ref(false)
const isExpanded = ref(false)
const editingId  = ref(null) // id ảnh đang sửa alt_text
const editAlt    = ref('')
const newAltText = ref('')
const fileInput  = ref(null)
const replaceInputs = ref({}) // refs cho input[type=file] thay ảnh từng dòng
const newImageUrl = ref('') // dán URL ảnh từ ngoài thay vì upload file (khi THÊM ảnh mới)
const showAddByUrl = ref(false)

const replacingByUrlId = ref(null) // id ảnh đang mở ô dán URL để THAY ảnh
const replaceUrlValue  = ref('')

const isDeleteModalActive = ref(false)
const itemToDelete = ref(null)

const confirmDelete = (img) => {
  itemToDelete.value = img
  isDeleteModalActive.value = true
}

const canAddMore  = computed(() => images.value.length < MAX_IMAGES)

const fetchImages = async () => {
  loading.value = true
  try {
    const res = await api.get(`/api/admin/products/${props.productId}/images`)
    images.value = res.data
  } catch {
    showToast('Lỗi tải danh sách ảnh', 'error')
  } finally {
    loading.value = false
  }
}

watch(() => props.productId, fetchImages, { immediate: true })
watch(isExpanded, (v) => { if (v && !images.value.length) fetchImages() })

// ── Thêm ảnh mới ─────────────────────────────────────────────
const triggerAdd = () => fileInput.value?.click()

const onAddFile = async (event) => {
  const file = event.target.files?.[0]
  event.target.value = null // cho phép chọn lại cùng 1 file lần sau
  if (!file) return

  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('image', file)
    if (newAltText.value) formData.append('alt_text', newAltText.value)

    await api.post(`/api/admin/products/${props.productId}/images`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    newAltText.value = ''
    showToast('Đã thêm ảnh')
    await fetchImages()
  } catch (e) {
    showToast(e.response?.data?.message || 'Lỗi thêm ảnh', 'error')
  } finally {
    uploading.value = false
  }
}

// ── Thêm ảnh mới bằng URL dán từ ngoài ──────────────────────
const onAddByUrl = async () => {
  if (!newImageUrl.value) return

  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('image_url', newImageUrl.value)
    if (newAltText.value) formData.append('alt_text', newAltText.value)

    await api.post(`/api/admin/products/${props.productId}/images`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    newImageUrl.value = ''
    newAltText.value = ''
    showAddByUrl.value = false
    showToast('Đã thêm ảnh')
    await fetchImages()
  } catch (e) {
    showToast(e.response?.data?.message || 'Lỗi thêm ảnh', 'error')
  } finally {
    uploading.value = false
  }
}

// ── Thay ảnh (giữ nguyên vị trí / trạng thái chính) ─────────
const triggerReplace = (imageId) => replaceInputs.value[imageId]?.click()

const onReplaceFile = async (imageId, event) => {
  const file = event.target.files?.[0]
  event.target.value = null
  if (!file) return

  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('image', file)
    formData.append('_method', 'PUT')
    await api.post(`/api/admin/products/${props.productId}/images/${imageId}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    showToast('Đã thay ảnh')
    await fetchImages()
  } catch (e) {
    showToast(e.response?.data?.message || 'Lỗi thay ảnh', 'error')
  } finally {
    uploading.value = false
  }
}

// ── Thay ảnh bằng URL dán từ ngoài ──────────────────────────
const startReplaceByUrl = (img) => {
  replacingByUrlId.value = img.id
  replaceUrlValue.value = ''
}

const submitReplaceByUrl = async (img) => {
  if (!replaceUrlValue.value) return

  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('image_url', replaceUrlValue.value)
    formData.append('_method', 'PUT')
    await api.post(`/api/admin/products/${props.productId}/images/${img.id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    replacingByUrlId.value = null
    showToast('Đã thay ảnh')
    await fetchImages()
  } catch (e) {
    showToast(e.response?.data?.message || 'Lỗi thay ảnh', 'error')
  } finally {
    uploading.value = false
  }
}

// ── Sửa mô tả (alt_text) ─────────────────────────────────────
const startEditAlt = (img) => {
  editingId.value = img.id
  editAlt.value = img.alt_text || ''
}

const saveAlt = async (img) => {
  try {
    await api.put(`/api/admin/products/${props.productId}/images/${img.id}`, {
      alt_text: editAlt.value,
    })
    img.alt_text = editAlt.value
    editingId.value = null
    showToast('Đã lưu mô tả ảnh')
  } catch {
    showToast('Lỗi lưu mô tả ảnh', 'error')
  }
}

// ── Đặt ảnh chính ────────────────────────────────────────────
const setPrimary = async (img) => {
  if (img.is_primary) return
  try {
    await api.put(`/api/admin/products/${props.productId}/images/${img.id}`, {
      is_primary: true,
    })
    showToast('Đã đặt làm ảnh chính')
    await fetchImages()
  } catch {
    showToast('Lỗi đặt ảnh chính', 'error')
  }
}

const removeImage = async () => {
  if (!itemToDelete.value) return
  try {
    await api.delete(`/api/admin/products/${props.productId}/images/${itemToDelete.value.id}`)
    showToast('Đã xoá ảnh')
    await fetchImages()
  } catch {
    showToast('Lỗi xoá ảnh', 'error')
  } finally {
    itemToDelete.value = null
  }
}

// ── Sắp xếp thứ tự ───────────────────────────────────────────
const move = async (index, direction) => {
  const target = index + direction
  if (target < 0 || target >= images.value.length) return
  const reordered = [...images.value]
  ;[reordered[index], reordered[target]] = [reordered[target], reordered[index]]
  images.value = reordered

  try {
    await api.patch(`/api/admin/products/${props.productId}/images/reorder`, {
      order: reordered.map(i => i.id),
    })
  } catch {
    showToast('Lỗi lưu thứ tự ảnh', 'error')
    fetchImages()
  }
}
</script>

<template>
  <CardBox class="mt-4">
    <!-- Header toggle -->
    <div class="flex items-center justify-between p-4 cursor-pointer select-none"
         @click="isExpanded = !isExpanded">
      <div class="flex items-center gap-3">
        <span class="font-bold text-base">Ảnh sản phẩm (gallery)</span>
        <span v-if="images.length"
              class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">
          {{ images.length }}/{{ MAX_IMAGES }} ảnh
        </span>
      </div>
      <div class="flex items-center gap-2">
        <component :is="'svg'" class="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
          <path :d="isExpanded ? mdiChevronUp : mdiChevronDown" />
        </component>
      </div>
    </div>

    <div v-if="isExpanded" class="p-4">
      <p class="text-xs text-gray-400 mb-3">
        Thêm, thay, xoá hoặc sắp xếp từng ảnh riêng lẻ (khác với chức năng nhập ảnh hàng loạt qua file Excel).
        Ảnh có gắn <strong>★ Ảnh chính</strong> sẽ hiển thị ở danh sách sản phẩm, giỏ hàng và là ảnh đầu tiên ở trang chi tiết.
      </p>

      <div v-if="loading" class="p-6 text-center text-gray-400">
        <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-blue-500 border-t-transparent"></div>
      </div>

      <div v-else class="flex flex-wrap gap-4">
        <!-- Ảnh hiện có -->
        <div v-for="(img, index) in images" :key="img.id"
             class="relative w-40 border rounded-lg overflow-hidden dark:border-slate-700 group">
          <div class="relative aspect-square bg-gray-50 dark:bg-slate-800">
            <img :src="imgUrl(img.image_url)" :alt="img.alt_text || ''"
                 class="w-full h-full object-cover" />

            <span v-if="img.is_primary"
                  class="absolute top-1 left-1 bg-yellow-400 text-yellow-900 text-[10px] font-bold px-1.5 py-0.5 rounded flex items-center gap-0.5">
              <component :is="'svg'" class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path :d="mdiStar" /></component>
              Chính
            </span>

            <!-- Overlay hành động -->
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100">
              <button type="button" title="Đặt làm ảnh chính" @click="setPrimary(img)"
                      class="bg-white/90 hover:bg-white rounded p-1.5" :disabled="img.is_primary">
                <component :is="'svg'" class="w-4 h-4" :class="img.is_primary ? 'text-gray-300' : 'text-yellow-600'" viewBox="0 0 24 24" fill="currentColor">
                  <path :d="img.is_primary ? mdiStar : mdiStarOutline" />
                </component>
              </button>
              <button type="button" title="Thay ảnh (chọn file)" @click="triggerReplace(img.id)"
                      class="bg-white/90 hover:bg-white rounded p-1.5">
                <component :is="'svg'" class="w-4 h-4 text-blue-600" viewBox="0 0 24 24" fill="currentColor"><path :d="mdiPencil" /></component>
              </button>
              <button type="button" title="Thay ảnh (dán URL)" @click="startReplaceByUrl(img)"
                      class="bg-white/90 hover:bg-white rounded p-1.5">
                <component :is="'svg'" class="w-4 h-4 text-purple-600" viewBox="0 0 24 24" fill="currentColor"><path :d="mdiLink" /></component>
              </button>
              <button type="button" title="Xoá ảnh" @click="confirmDelete(img)"
                      class="bg-white/90 hover:bg-white rounded p-1.5">
                <component :is="'svg'" class="w-4 h-4 text-red-600" viewBox="0 0 24 24" fill="currentColor"><path :d="mdiTrashCan" /></component>
              </button>
            </div>

            <input type="file" accept="image/*" class="hidden"
                   :ref="el => (replaceInputs[img.id] = el)"
                   @change="onReplaceFile(img.id, $event)" />
          </div>

          <!-- Ô dán URL để thay ảnh -->
          <div v-if="replacingByUrlId === img.id" class="p-1.5 pt-0 flex gap-1">
            <FormControl v-model="replaceUrlValue" class="!text-xs" placeholder="Dán URL ảnh..." />
            <button type="button" class="text-green-600 px-1" @click="submitReplaceByUrl(img)">✓</button>
            <button type="button" class="text-gray-400 px-1" @click="replacingByUrlId = null">
              <component :is="'svg'" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path :d="mdiClose" /></component>
            </button>
          </div>

          <!-- Mô tả ảnh (alt_text) -->
          <div class="p-1.5 text-xs">
            <div v-if="editingId === img.id" class="flex gap-1">
              <FormControl v-model="editAlt" class="!text-xs" />
              <button type="button" class="text-green-600 px-1" @click="saveAlt(img)">✓</button>
              <button type="button" class="text-gray-400 px-1" @click="editingId = null">
                <component :is="'svg'" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path :d="mdiClose" /></component>
              </button>
            </div>
            <div v-else class="flex items-center justify-between gap-1 text-gray-500 truncate cursor-pointer"
                 @click="startEditAlt(img)" title="Nhấn để sửa mô tả ảnh">
              <span class="truncate">{{ img.alt_text || '(chưa có mô tả)' }}</span>
              <component :is="'svg'" class="w-3 h-3 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path :d="mdiPencil" /></component>
            </div>
          </div>

          <!-- Nút sắp xếp -->
          <div class="flex justify-between px-1.5 pb-1.5">
            <button type="button" class="text-gray-400 hover:text-gray-700 disabled:opacity-20"
                    :disabled="index === 0" @click="move(index, -1)" title="Lên trước">
              <component :is="'svg'" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path :d="mdiArrowUp" /></component>
            </button>
            <button type="button" class="text-gray-400 hover:text-gray-700 disabled:opacity-20"
                    :disabled="index === images.length - 1" @click="move(index, 1)" title="Xuống sau">
              <component :is="'svg'" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path :d="mdiArrowDown" /></component>
            </button>
          </div>
        </div>

        <!-- Ô thêm ảnh mới -->
        <div v-if="canAddMore"
             class="w-40 aspect-square border-2 border-dashed rounded-lg flex flex-col items-center justify-center gap-2 p-2 text-gray-400 dark:border-slate-700">
          <template v-if="showAddByUrl">
            <span class="text-xs font-medium text-gray-500">Dán URL ảnh</span>
            <FormControl v-model="newImageUrl" class="!text-xs w-full" placeholder="https://..." />
            <div class="flex gap-2">
              <button type="button" class="text-green-600 text-xs font-medium" :disabled="uploading" @click="onAddByUrl">
                {{ uploading ? '...' : 'Thêm' }}
              </button>
              <button type="button" class="text-gray-400 text-xs" @click="showAddByUrl = false; newImageUrl = ''">Huỷ</button>
            </div>
          </template>
          <template v-else>
            <div class="flex flex-col items-center gap-2 cursor-pointer hover:text-blue-500" @click="triggerAdd">
              <div v-if="uploading" class="animate-spin rounded-full h-6 w-6 border-2 border-blue-500 border-t-transparent"></div>
              <template v-else>
                <component :is="'svg'" class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor"><path :d="mdiPlus" /></component>
                <span class="text-xs">Thêm ảnh (file)</span>
              </template>
            </div>
            <button type="button" class="text-xs text-purple-500 hover:underline flex items-center gap-1" @click="showAddByUrl = true">
              <component :is="'svg'" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path :d="mdiLink" /></component>
              hoặc dán URL
            </button>
          </template>
          <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onAddFile" />
        </div>

        <div v-if="!images.length && !loading" class="text-sm text-gray-400 py-6">
          Sản phẩm chưa có ảnh nào trong gallery. Nhấn ô "Thêm ảnh" để tải ảnh đầu tiên lên.
        </div>
      </div>
    </div>

    <!-- ══ CONFIRM DELETE MODAL ══ -->
    <CardBoxModal
      v-model="isDeleteModalActive"
      title="Xác nhận xóa"
      button-label="Xóa"
      has-cancel
      @confirm="removeImage"
    >
      <p>Xoá ảnh này khỏi sản phẩm?</p>
    </CardBoxModal>
  </CardBox>
</template>