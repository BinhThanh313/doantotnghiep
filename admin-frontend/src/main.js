import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import api from '@/services/api'
import { useMainStore } from '@/stores/main.js'

import './css/main.css'

// Init Pinia
const pinia = createPinia()

// Create Vue app instance (chưa mount)
const app = createApp(App).use(router).use(pinia)

// Init main store
const mainStore = useMainStore(pinia)

/**
 * Nếu đã có admin_token (VD: F5 lại trang, hoặc gõ thẳng URL) thì phải đợi
 * lấy tên/email admin thật từ API RỒI MỚI mount app — nếu mount app trước
 * rồi mới fetch (như cách làm cũ ở App.vue::onMounted), Vue sẽ render giá
 * trị mặc định "John Doe" trước, rồi mới re-render đè lên khi API trả về
 * → gây hiện tượng chớp tên sai dù rất nhanh.
 *
 * Trường hợp token không còn hợp lệ (hết hạn, hoặc DB vừa bị seed lại
 * bằng migrate:fresh khiến bảng personal_access_tokens bị xóa sạch) thì
 * KHÔNG được im lặng bỏ qua rồi mount app — vì window.location.href ở
 * interceptor không dừng code ngay lập tức, nên app vẫn mount và gọi các
 * API khác bằng token đã chết trong lúc chờ trình duyệt chuyển trang,
 * gây hiện tượng "chớp John Doe + mất dữ liệu mọi trang" cho tới khi
 * người dùng F5 thủ công. Ở đây ta chủ động dừng hẳn và điều hướng ngay,
 * không mount app với state rỗng/giả nữa.
 */
async function bootstrap() {
  const token = localStorage.getItem('admin_token')

  if (token) {
    try {
      const res = await api.get('/api/admin/me')
      mainStore.setUser({
        name: res.data.name,
        email: res.data.email,
      })
    } catch (e) {
      localStorage.removeItem('admin_token')
      window.location.replace('/doantotnghiep/public/admin/login#/login')
      return // dừng hẳn, không mount app với dữ liệu cũ/rỗng
    }
  }

  app.mount('#app')

  // Fetch sample data
  mainStore.fetchSampleClients()
  mainStore.fetchSampleHistory()
}

bootstrap()

// Dark mode
// Uncomment, if you'd like to restore persisted darkMode setting, or use `prefers-color-scheme: dark`.
// import { useDarkModeStore } from '@/stores/darkMode'

// const darkModeStore = useDarkModeStore(pinia)
// darkModeStore.init()

// Default title tag
const defaultDocumentTitle = 'Admin One Vue 3 Tailwind'

// Set document title from route meta
router.afterEach((to) => {
  document.title = to.meta?.title
    ? `${to.meta.title} — ${defaultDocumentTitle}`
    : defaultDocumentTitle
})