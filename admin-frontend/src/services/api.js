import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost/doantotnghiep/public',           // proxy tự forward sang Laravel lúc dev
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Tự động đính kèm token vào mọi request
api.interceptors.request.use(config => {
  const token = localStorage.getItem('admin_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Tự redirect về login nếu hết phiên
api.interceptors.response.use(
  res => res,
  err => {
    if (err.response?.status === 401) {
      localStorage.removeItem('admin_token')
      window.location.href = '/admin/login'
    }
    return Promise.reject(err)
  }
)

export default api