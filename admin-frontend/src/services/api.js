import axios from 'axios'

// ── Instance ─────────────────────────────────────────────────────
const basePath = window.location.pathname.includes('/doantotnghiep/public') ? '/doantotnghiep/public' : '';

const api = axios.create({
  baseURL: window.location.origin + basePath,
  withCredentials: true,
  timeout: 600000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

// ── Request interceptor ──────────────────────────────────────────
api.interceptors.request.use(
  config => {
    const token = localStorage.getItem('admin_token')
    if (token) config.headers.Authorization = `Bearer ${token}`

    // Đính kèm CSRF token nếu có (Laravel Sanctum SPA)
    const csrf = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
    if (csrf) config.headers['X-XSRF-TOKEN'] = decodeURIComponent(csrf[1])

    // Workaround for PHP 8.3+ request_parse_body fatal error on Render
    // Convert PUT, PATCH, DELETE to POST with ?_method=...
    if (config.method && ['put', 'patch', 'delete'].includes(config.method.toLowerCase())) {
      const overrideMethod = config.method.toUpperCase()
      config.method = 'post'
      // Use URL parameter for _method, which works universally in Laravel for both JSON and FormData
      config.url = config.url + (config.url.includes('?') ? '&' : '?') + '_method=' + overrideMethod
    }

    return config
  },
  error => Promise.reject(error)
)

// ── Response interceptor ─────────────────────────────────────────
api.interceptors.response.use(
  res => res,
  err => {
    const status  = err.response?.status
    const url     = err.config?.url || ''
    const message = err.response?.data?.message || err.message

    switch (status) {
      case 401:
        // Hết phiên → về login
        // Tránh lặp vô hạn nếu đang ở trang login
        if (!window.location.hash.includes('/login')) {
          window.location.href = basePath + '/admin/login#/login'
        }
        break

      case 403:
        toast.error('Bạn không có quyền thực hiện thao tác này.')
        break

      case 422:
        // Validation errors - để component tự xử lý
        break

      case 429:
        toast.warning('Quá nhiều yêu cầu. Vui lòng thử lại sau.')
        break

      case 500:
      case 502:
      case 503:
        toast.error('Lỗi máy chủ. Vui lòng thử lại sau.')
        break

      default:
        if (!err.response) {
          toast.error('Không thể kết nối đến máy chủ. Kiểm tra kết nối mạng.')
        }
    }

    return Promise.reject(err)
  }
)

// ── Toast helper ─────────────────────────────────────────────────
// Sử dụng custom event để component nào cũng có thể lắng nghe
const createToastEvent = (type, message) => {
  window.dispatchEvent(new CustomEvent('app:toast', {
    detail: { type, message, id: Date.now() }
  }))
}

export const toast = {
  success: (msg) => createToastEvent('success', msg),
  error:   (msg) => createToastEvent('error',   msg),
  warning: (msg) => createToastEvent('warning', msg),
  info:    (msg) => createToastEvent('info',    msg),
}

// ── Utility helpers ──────────────────────────────────────────────

/**
 * Lấy validation errors từ response Laravel (422)
 * @param {Error} error
 * @returns {Object} { field: ['message'] }
 */
export const getValidationErrors = (error) => {
  if (error.response?.status === 422) {
    return error.response.data.errors || {}
  }
  return {}
}

/**
 * Chuyển validation errors thành string đơn giản
 */
export const flattenErrors = (errors) =>
  Object.values(errors).flat().join('\n')

/**
 * Format status đơn hàng → tiếng Việt
 */
export const formatOrderStatus = (status) => ({
  pending:       'Chờ xử lý',
  processing:    'Đang chuẩn bị',
  ready_to_ship: 'Sẵn sàng giao',
  shipped:       'Đang vận chuyển',
  delivered:     'Đã giao hàng',
  completed:     'Hoàn thành',
  cancelled:     'Đã hủy',
}[status] || status)

/**
 * Tính tổng tiền đơn hàng
 */
export const calculateOrderTotal = (order) =>
  (order.total_amount || 0) + (order.shipping_fee || 0) - (order.discount_amount || 0)

/**
 * Format tracking number
 */
export const formatTrackingNumber = (num) =>
  num ? num.toString().toUpperCase() : 'N/A'

/**
 * Format currency VND
 */
export const formatCurrency = (amount) =>
  new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount || 0)

export default api