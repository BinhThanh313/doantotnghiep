import { createRouter, createWebHashHistory } from 'vue-router'

// Toàn bộ view được lazy-load (route-level code splitting) để trang nào
// dùng mới tải JS của trang đó, thay vì gộp hết vào 1 bundle ban đầu
// (giúp giảm thời gian tải, đặc biệt trang Dashboard/Login khi mở app).

const routes = [
  // ==================== DEFAULT REDIRECT ====================
  {
    path: '/',
    redirect: (to) => {
      const token = localStorage.getItem('admin_token')
      return token ? '/dashboard' : '/login'
    }
  },

  // ==================== AUTH ====================
  {
    meta: { title: 'Login' },
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
  },
  {
    meta: { title: 'Profile' },
    path: '/profile',
    name: 'profile',
    component: () => import('@/views/auth/ProfileView.vue'),
  },
  {
    meta: { title: 'Error' },
    path: '/error',
    name: 'error',
    component: () => import('@/views/auth/ErrorView.vue'),
  },

  // ==================== DASHBOARD ====================
  {
    meta: { title: 'Dashboard' },
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/dashboard/HomeView.vue'),
  },

  // ==================== ADMIN INSIGHTS ====================
  {
    meta: { title: 'Gợi ý cho Admin' },
    path: '/insights',
    name: 'insights',
    component: () => import('@/views/insights/InsightsView.vue'),
  },

  // ==================== MANAGE: PRODUCTS ====================
  {
    meta: { title: 'Quản lý sản phẩm' },
    path: '/products',
    name: 'products.index',
    component: () => import('@/views/manage/products/ProductListView.vue'),
  },
  {
    meta: { title: 'Thêm/Sửa sản phẩm' },
    path: '/products/form/:id?',
    name: 'products.form',
    component: () => import('@/views/manage/products/ProductFormView.vue'),
  },

  // ==================== MANAGE: CATEGORIES ====================
  {
    meta: { title: 'Quản lý danh mục' },
    path: '/categories',
    name: 'categories.index',
    component: () => import('@/views/manage/categories/CategoryListView.vue'),
  },

  // ==================== MANAGE: USERS ====================
  {
    meta: { title: 'Quản lý người dùng' },
    path: '/users',
    name: 'users.index',
    component: () => import('@/views/manage/users/UserListView.vue'),
  },
  {
    meta: { title: 'Thêm/Sửa người dùng' },
    path: '/users/form/:id?',
    name: 'users.form',
    component: () => import('@/views/manage/users/UserFormView.vue'),
  },

  // ==================== MANAGE: ORDERS ====================
  {
    meta: { title: 'Quản lý đơn hàng' },
    path: '/orders',
    name: 'orders.index',
    component: () => import('@/views/manage/orders/OrderListView.vue'),
  },

  // ==================== MANAGE: VOUCHERS ====================
  {
    meta: { title: 'Quản lý Voucher' },
    path: '/vouchers',
    name: 'vouchers.index',
    component: () => import('@/views/manage/vouchers/VoucherListView.vue'),
  },

  // ==================== MANAGE: REVIEWS ====================
  {
    meta: { title: 'Quản lý Đánh giá' },
    path: '/reviews',
    name: 'reviews.index',
    component: () => import('@/views/manage/reviews/ReviewListView.vue'),
  },
  {
    meta: { title: 'Chi tiết đánh giá' },
    path: '/manage/reviews/:id',
    name: 'review-detail',
    component: () => import('@/views/manage/reviews/ReviewDetailView.vue'),
  },

  // ==================== MANAGE: SHIPPING ====================
  {
    meta: { title: 'Quản lý Vận chuyển' },
    path: '/shipping',
    name: 'shipping',
    component: () => import('@/views/manage/shipping/ShippingView.vue'),
  },

  // ==================== MANAGE: CONTACT MESSAGES ====================
  {
    meta: { title: 'Tin nhắn liên hệ' },
    path: '/contact-messages',
    name: 'contact-messages.index',
    component: () => import('@/views/manage/contact/ContactMessageListView.vue'),
  },

  // ==================== MANAGE: PAYMENTS ====================
  {
    meta: { title: 'Quản lý Thanh toán' },
    path: '/payments',
    name: 'payments.index',
    component: () => import('@/views/manage/payment/PaymentListView.vue'),
  },
  {
    meta: { title: 'Flash Sale' },
    path: '/flash-sales',
    name: 'flash-sales.index',
    component: () => import('@/views/manage/flash-sales/FlashSaleView.vue'),
  },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    return savedPosition || { left: 0, top: 0 }
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