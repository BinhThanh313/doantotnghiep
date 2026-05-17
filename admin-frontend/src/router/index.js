import { createRouter, createWebHashHistory } from 'vue-router'
import Style from '@/views/StyleView.vue'
import Home from '@/views/HomeView.vue'

const routes = [
  {
    meta: {
      title: 'Select style',
    },
    path: '/',
    name: 'style',
    component: Style,
  },
  {
    // Document title tag
    // We combine it with defaultDocumentTitle set in `src/main.js` on router.afterEach hook
    meta: {
      title: 'Dashboard',
    },
    path: '/dashboard',
    name: 'dashboard',
    component: Home,
  },
  {
    meta: { title: 'Quản lý sản phẩm' },
    path: '/products',
    name: 'products.index',
    component: () => import('@/views/ProductListView.vue'),
  },
  {
    meta: { title: 'Thêm/Sửa sản phẩm' },
    path: '/products/form/:id?', // Có :id? để dùng chung cho cả Thêm và Sửa
    name: 'products.form',
    component: () => import('@/views/ProductFormView.vue'),
  },
  {
  meta: {
    title: 'Quản lý người dùng',
  },
  path: '/users',
  name: 'users.index',
  component: () => import('@/views/UserListView.vue'), // Trỏ tới file bạn vừa tạo
},
{
    meta: { title: 'Thêm/Sửa người dùng' },
    path: '/users/form/:id?', // Dấu hỏi chấm ? có nghĩa id này có thể có hoặc không
    name: 'users.form',
    component: () => import('@/views/UserFormView.vue'),
  },
  {
    meta: {
      title: 'Tables',
    },
    path: '/tables',
    name: 'tables',
    component: () => import('@/views/TablesView.vue'),
  },
  {
    meta: {
      title: 'Forms',
    },
    path: '/forms',
    name: 'forms',
    component: () => import('@/views/FormsView.vue'),
  },
  {
    meta: {
      title: 'Profile',
    },
    path: '/profile',
    name: 'profile',
    component: () => import('@/views/ProfileView.vue'),
  },
  {
    meta: {
      title: 'Ui',
    },
    path: '/ui',
    name: 'ui',
    component: () => import('@/views/UiView.vue'),
  },
  {
    meta: {
      title: 'Responsive layout',
    },
    path: '/responsive',
    name: 'responsive',
    component: () => import('@/views/ResponsiveView.vue'),
  },
  {
    meta: {
      title: 'Login',
    },
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
  },
  {
    meta: {
      title: 'Error',
    },
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

// admin-frontend/src/router/index.js — thêm vào cuối trước export
router.beforeEach((to, from, next) => {
  const publicPages = ['/login', '/error', '/']
  const authRequired = !publicPages.includes(to.path)
  const token = localStorage.getItem('admin_token')

  if (authRequired && !token) {
    return next('/login')
  }
  next()
})
export default router
