<!-- admin-frontend/src/components/ToastContainer.vue -->
<!--
  Toast dùng chung cho toàn bộ admin panel.
  Chỉ cần đặt <ToastContainer /> MỘT LẦN trong LayoutAuthenticated.vue.
  Các trang khác gọi showToast('Nội dung', 'success' | 'error') từ
  composables/useToast.js, không cần khai báo state/template riêng nữa.
-->
<script setup>
import { useToast } from '@/composables/useToast'

const { toast } = useToast()
</script>

<template>
  <Transition name="toast-slide-fade">
    <div
      v-if="toast.show"
      class="fixed top-4 right-4 z-50 px-5 py-3 rounded-lg shadow-lg text-white text-sm font-medium whitespace-pre-line max-w-md"
      :class="{
        'bg-emerald-500': toast.type === 'success',
        'bg-red-500': toast.type === 'error',
        'bg-orange-500': toast.type === 'warning',
      }"
    >
      {{ toast.message }}
    </div>
  </Transition>
</template>

<style scoped>
.toast-slide-fade-enter-active,
.toast-slide-fade-leave-active {
  transition: all 0.3s ease;
}
.toast-slide-fade-enter-from,
.toast-slide-fade-leave-to {
  transform: translateX(20px);
  opacity: 0;
}
</style>