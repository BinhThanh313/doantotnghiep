import {
  mdiAccountCircle,
  mdiMonitor,
  mdiPackageVariant,
  mdiFormatListBulleted,
  mdiCartOutline,
  mdiTicketPercent,
  mdiStarOutline,
  mdiTruck,
  mdiAccountGroup,
  mdiLogout,
    mdiPalette,
} from '@mdi/js'

export const menuAsideMain = [
  {
    to: '/dashboard',
    icon: mdiMonitor,
    label: 'Dashboard',
  },

  // ==================== QUẢN LÝ - Tất cả cùng cấp độ ====================
  {
    to: '/products',
    icon: mdiPackageVariant,
    label: 'Sản phẩm',
  },
  {
    to: '/categories',
    icon: mdiFormatListBulleted,
    label: 'Danh mục',
  },
  {
    to: '/orders',
    icon: mdiCartOutline,
    label: 'Đơn hàng',
  },
  {
    to: '/vouchers',
    icon: mdiTicketPercent,
    label: 'Voucher',
  },
  {
    to: '/reviews',
    icon: mdiStarOutline,
    label: 'Đánh giá',
  },
  {
    to: '/shipping',
    icon: mdiTruck,
    label: 'Vận chuyển',
  },
  {
    to: '/users',
    icon: mdiAccountGroup,
    label: 'Người dùng',
  },
]

export const menuAsideBottom = [
  {
    to: '/profile',
    label: 'Profile',
    icon: mdiAccountCircle,
  },
    {
    to: '/style',
    label: 'Style',
    icon: mdiPalette,
  },
  {
    to: '/login',
    label: 'Logout',
    icon: mdiLogout,
  },
]