import {
  mdiMonitor,
  mdiPackageVariant,
  mdiFormatListBulleted,
  mdiCartOutline,
  mdiTicketPercent,
  mdiStarOutline,
  mdiTruck,
  mdiAccountGroup,
    mdiCreditCardOutline,
    mdiBolt,
    mdiEmailOutline,
    mdiLightbulbOnOutline,
} from '@mdi/js'

export const menuAsideMain = [
  { to: '/dashboard',  icon: mdiMonitor,           label: 'Dashboard'     },
  { to: '/insights',   icon: mdiLightbulbOnOutline, label: 'Gợi ý cho Admin' },
  { to: '/products',   icon: mdiPackageVariant,     label: 'Sản phẩm'      },
  { to: '/categories', icon: mdiFormatListBulleted, label: 'Danh mục'      },
  { to: '/orders',     icon: mdiCartOutline,        label: 'Đơn hàng'      },
  { to: '/payments',   icon: mdiCreditCardOutline,  label: 'Thanh toán'    },
  { to: '/flash-sales',  icon: mdiBolt,               label: 'Flash Sale'    }, // ← thêm
  { to: '/vouchers',   icon: mdiTicketPercent,      label: 'Voucher'       },
  { to: '/reviews',    icon: mdiStarOutline,        label: 'Đánh giá'      },
  { to: '/shipping',   icon: mdiTruck,              label: 'Vận chuyển'    },
  { to: '/contact-messages', icon: mdiEmailOutline, label: 'Liên hệ'       },
  { to: '/users',      icon: mdiAccountGroup,       label: 'Người dùng'    },
]

export const menuAsideBottom = []