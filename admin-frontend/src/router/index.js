import { createRouter, createWebHashHistory } from 'vue-router'

// Demo views (mẫu từ template)
import StyleView from '@/views/demo/StyleView.vue'
import TablesView from '@/views/demo/TablesView.vue'
import FormsView from '@/views/demo/FormsView.vue'
import UiView from '@/views/demo/UiView.vue'
import ResponsiveView from '@/views/demo/ResponsiveView.vue'

// Auth views
import LoginView from '@/views/auth/LoginView.vue'
import ErrorView from '@/views/auth/ErrorView.vue'
import ProfileView from '@/views/auth/ProfileView.vue'

// Dashboard
import HomeView from '@/views/dashboard/HomeView.vue'

// Manage views - Products
import ProductListView from '@/views/manage/products/ProductListView.vue'
import ProductFormView from '@/views/manage/products/ProductFormView.vue'

// Manage views - Categories
import CategoryListView from '@/views/manage/categories/CategoryListView.vue'

// Manage views - Users
import UserListView from '@/views/manage/users/UserListView.vue'
import UserFormView from '@/views/manage/users/UserFormView.vue'

// Manage views - Orders
import OrderListView from '@/views/manage/orders/OrderListView.vue'

// Manage views - Vouchers
import VoucherListView from '@/views/manage/vouchers/VoucherListView.vue'

// Manage views - Reviews
import ReviewListView from '@/views/manage/reviews/ReviewListView.vue'

// Manage views - Shipping
import ShippingView from '@/views/manage/shipping/ShippingView.vue'

const routes = [
  // ==================== DEMO & STYLE ====================
  {
    meta: { title: 'Select style' },
    path: '/',
    name: 'style',
    component: StyleView,
  },

  // ==================== AUTH ====================
  {
    meta: { title: 'Login' },
    path: '/login',
    name: 'login',
    component: LoginView,
  },
  {
    meta: { title: 'Profile' },
    path: '/profile',
    name: 'profile',
    component: ProfileView,
  },
  {
    meta: { title: 'Error' },
    path: '/error',
    name: 'error',
    component: ErrorView,
  },

  // ==================== DASHBOARD ====================
  {
    meta: { title: 'Dashboard' },
    path: '/dashboard',
    name: 'dashboard',
    component: HomeView,
  },

  // ==================== MANAGE: PRODUCTS ====================
  {
    meta: { title: 'Quản lý sản phẩm' },
    path: '/products',
    name: 'products.index',
    component: ProductListView,
  },
  {
    meta: { title: 'Thêm/Sửa sản phẩm' },
    path: '/products/form/:id?',
    name: 'products.form',
    component: ProductFormView,
  },

  // ==================== MANAGE: CATEGORIES ====================
  {
    meta: { title: 'Quản lý danh mục' },
    path: '/categories',
    name: 'categories.index',
    component: CategoryListView,
  },

  // ==================== MANAGE: USERS ====================
  {
    meta: { title: 'Quản lý người dùng' },
    path: '/users',
    name: 'users.index',
    component: UserListView,
  },
  {
    meta: { title: 'Thêm/Sửa người dùng' },
    path: '/users/form/:id?',
    name: 'users.form',
    component: UserFormView,
  },

  // ==================== MANAGE: ORDERS ====================
  {
    meta: { title: 'Quản lý đơn hàng' },
    path: '/orders',
    name: 'orders.index',
    component: OrderListView,
  },

  // ==================== MANAGE: VOUCHERS ====================
  {
    meta: { title: 'Quản lý Voucher' },
    path: '/vouchers',
    name: 'vouchers.index',
    component: VoucherListView,
  },

  // ==================== MANAGE: REVIEWS ====================
  {
    meta: { title: 'Quản lý Đánh giá' },
    path: '/reviews',
    name: 'reviews.index',
    component: ReviewListView,
  },

  // ==================== MANAGE: SHIPPING ====================
  {
    meta: { title: 'Quản lý Vận chuyển' },
    path: '/shipping',
    name: 'shipping',
    component: ShippingView,
  },

  // ==================== DEMO PAGES ====================
  {
    meta: { title: 'Tables' },
    path: '/tables',
    name: 'tables',
    component: TablesView,
  },
  {
    meta: { title: 'Forms' },
    path: '/forms',
    name: 'forms',
    component: FormsView,
  },
  {
    meta: { title: 'UI' },
    path: '/ui',
    name: 'ui',
    component: UiView,
  },
  {
    meta: { title: 'Responsive' },
    path: '/responsive',
    name: 'responsive',
    component: ResponsiveView,
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