import { createRouter, createWebHashHistory } from 'vue-router'
import Style from '@/views/StyleView.vue'
import Home from '@/views/HomeView.vue'

const routes = [
  {
    meta: { title: 'Select style' },
    path: '/',
    name: 'style',
    component: Style,
  },
  {
    meta: { title: 'Dashboard' },
    path: '/dashboard',
    name: 'dashboard',
    component: Home,
  },

  // ==================== PRODUCTS ====================
  {
    meta: { title: 'Quản lý sản phẩm' },
    path: '/products',
    name: 'products.index',
    component: () => import('@/views/ProductListView.vue'),
  },
  {
    meta: { title: 'Thêm/Sửa sản phẩm' },
    path: '/products/form/:id?',
    name: 'products.form',
    component: () => import('@/views/ProductFormView.vue'),
  },

  // ==================== USERS ====================
  {
    meta: { title: 'Quản lý người dùng' },
    path: '/users',
    name: 'users.index',
    component: () => import('@/views/UserListView.vue'),
  },
  {
    meta: { title: 'Thêm/Sửa người dùng' },
    path: '/users/form/:id?',
    name: 'users.form',
    component: () => import('@/views/UserFormView.vue'),
  },

  // ==================== ORDERS ====================
  {
    meta: { title: 'Quản lý đơn hàng' },
    path: '/orders',
    name: 'orders.index',
    component: () => import('@/views/OrderListView.vue'),
  },

  // ==================== VOUCHERS ====================
  {
    meta: { title: 'Quản lý Voucher' },
    path: '/vouchers',
    name: 'vouchers.index',
    component: () => import('@/views/VoucherListView.vue'),
  },

  // ==================== SHIPPING ====================
  {
    meta: { title: 'Quản lý Vận chuyển' },
    path: '/shipping',
    name: 'shipping',
    component: () => import('@/views/ShippingView.vue'),
  },

  // ==================== REVIEWS ====================
  {
    meta: { title: 'Quản lý Đánh giá' },
    path: '/reviews',
    name: 'reviews.index',
    component: () => import('@/views/ReviewListView.vue'),
  },

  // ==================== CATEGORIES (optional page) ====================
  {
    meta: { title: 'Quản lý danh mục' },
    path: '/categories',
    name: 'categories.index',
    component: () => import('@/views/CategoryListView.vue'),
  },

  // ==================== MISC ====================
  {
    meta: { title: 'Tables' },
    path: '/tables',
    name: 'tables',
    component: () => import('@/views/TablesView.vue'),
  },
  {
    meta: { title: 'Forms' },
    path: '/forms',
    name: 'forms',
    component: () => import('@/views/FormsView.vue'),
  },
  {
    meta: { title: 'Profile' },
    path: '/profile',
    name: 'profile',
    component: () => import('@/views/ProfileView.vue'),
  },
  {
    meta: { title: 'Login' },
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
  },
  {
    meta: { title: 'Error' },
    path: '/error',
    name: 'error',
    component: () => import('@/views/ErrorView.vue'),
  },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    return savedPosition || { top: 0 }
  },
})

// Auth guard
router.beforeEach((to, from, next) => {
  const publicPages = ['/login', '/error', '/']
  const authRequired = !publicPages.includes(to.path)
  const token = localStorage.getItem('admin_token')
  if (authRequired && !token) return next('/login')
  next()
})

export default router