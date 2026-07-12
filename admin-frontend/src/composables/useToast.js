// admin-frontend/src/composables/useToast.js
//
// Toast thông báo dùng chung cho toàn bộ admin panel.
// State là singleton ở module-level (không phải trong function useToast)
// nên mọi component gọi showToast() đều điều khiển cùng 1 toast duy nhất
// hiển thị bởi <ToastContainer /> đặt trong LayoutAuthenticated.vue.
import { ref } from 'vue'

const toast = ref({ show: false, message: '', type: 'success' })
let hideTimer = null

/**
 * Hiện toast thông báo.
 * @param {string} message - Nội dung hiển thị
 * @param {'success'|'error'} type - Loại thông báo (đổi màu nền)
 * @param {number} duration - Thời gian hiện (ms), mặc định 3500ms
 */
function showToast(message, type = 'success', duration = 3500) {
  if (hideTimer) clearTimeout(hideTimer)
  toast.value = { show: true, message, type }
  hideTimer = setTimeout(() => {
    toast.value.show = false
  }, duration)
}

export function useToast() {
  return { toast, showToast }
}

// Cho phép import trực tiếp showToast() mà không cần gọi useToast() trước,
// tiện cho các hàm xử lý lỗi/thành công ngắn gọn trong <script setup>.
export { showToast }