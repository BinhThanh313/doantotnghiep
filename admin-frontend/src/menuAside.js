import {
  mdiAccountCircle,
  mdiMonitor,
  mdiGithub,
  mdiLock,
  mdiAlertCircle,
  mdiSquareEditOutline,
  mdiTable,
  mdiViewList,
  mdiTelevisionGuide,
  mdiResponsive,
  mdiPalette,
  mdiLogout,
  mdiAccountGroup,
  mdiPackageVariant,
  mdiCartOutline,        // Đơn hàng
  mdiTicketPercent,      // Voucher
  mdiStarOutline,        // Đánh giá
  mdiTruckDelivery,      // Vận chuyển
  mdiFolderText,
  mdiFormatListBulleted,
} from '@mdi/js'

export const menuAsideMain = [
  {
    to: '/dashboard',
    icon: mdiMonitor,
    label: 'Dashboard',
  },

  // ==================== PRODUCTS ====================
  {
    to: '/products',
    label: 'Quản lý Sản phẩm',
    icon: mdiPackageVariant,
  },

  // ==================== CATEGORIES ====================
  {
    to: '/categories',
    icon: mdiFormatListBulleted, // Bạn cần import icon này từ @mdi/js
    label: 'Quản lý Danh mục'
  },

  // ==================== ORDERS ====================
  {
    to: '/orders',
    label: 'Quản lý Đơn hàng',
    icon: mdiCartOutline,
  },

  // ==================== USERS ====================
  {
    to: '/users',
    label: 'Quản lý User',
    icon: mdiAccountGroup,
  },

  // ==================== VOUCHERS ====================
  {
    to: '/vouchers',
    label: 'Quản lý Voucher',
    icon: mdiTicketPercent,
  },

  // ==================== REVIEWS ====================
  {
    to: '/reviews',
    label: 'Quản lý Đánh giá',
    icon: mdiStarOutline,
  },

  // ==================== SHIPPING ====================
  {
    to: '/shipping',
    label: 'Quản lý Vận chuyển',
    icon: mdiTruckDelivery,
  },
]
// export const menuAsideMain = [
//   {
//     to: '/dashboard',
//     icon: mdiMonitor,
//     label: 'Dashboard',
//   },
//   {
//     to: '/products',
//     label: 'Quản lý Sản phẩm',
//     icon: mdiPackageVariant,
//   },
//   {
//     to: '/users',
//     label: 'Quản lý User',
//     icon: mdiAccountGroup,
//   },
  // {
  //   to: '/tables',
  //   label: 'Tables',
  //   icon: mdiTable,
  // },
  // {
  //   to: '/forms',
  //   label: 'Forms',
  //   icon: mdiSquareEditOutline,
  // },
  // {
  //   to: '/ui',
  //   label: 'UI',
  //   icon: mdiTelevisionGuide,
  // },
  // {
  //   to: '/responsive',
  //   label: 'Responsive',
  //   icon: mdiResponsive,
  // },
  // {
  //   to: '/',
  //   label: 'Styles',
  //   icon: mdiPalette,
  // },
  // {
  //   to: '/profile',
  //   label: 'Profile',
  //   icon: mdiAccountCircle,
  // },
  // {
  //   to: '/login',
  //   label: 'Login',
  //   icon: mdiLock,
  // },
  // {
  //   to: '/error',
  //   label: 'Error',
  //   icon: mdiAlertCircle,
  // },
  // {
  //   label: 'Dropdown',
  //   icon: mdiViewList,
  //   menu: [
  //     {
  //       label: 'Item One',
  //     },
  //     {
  //       label: 'Item Two',
  //     },
  //   ],
  // },
  // {
  //   href: 'https://github.com/justboil/admin-one-vue-tailwind',
  //   label: 'GitHub',
  //   icon: mdiGithub,
  //   target: '_blank',
  // },
// ]

export const menuAsideBottom = [
  {
    label: 'Logout',
    icon: mdiLogout,
    color: 'info',
    isLogout: true,
  },
]
