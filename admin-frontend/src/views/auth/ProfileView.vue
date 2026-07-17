<script setup>
import { reactive, ref } from 'vue'
import { useMainStore } from '@/stores/main'
import { mdiAccount, mdiMail, mdiAsterisk, mdiFormTextboxPassword } from '@mdi/js'
import api from '@/services/api'
import { showToast } from '@/composables/useToast'
import SectionMain from '@/components/SectionMain.vue'
import CardBox from '@/components/CardBox.vue'
import BaseDivider from '@/components/BaseDivider.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import UserCard from '@/components/UserCard.vue'
import LayoutAuthenticated from '@/layouts/LayoutAuthenticated.vue'

const mainStore = useMainStore()

const profileForm = reactive({
  name: mainStore.userName,
  email: mainStore.userEmail,
})

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const profileSaving  = ref(false)
const passwordSaving = ref(false)

const submitProfile = async () => {
  profileSaving.value = true
  try {
    await api.put('/api/admin/profile', profileForm)
    mainStore.setUser(profileForm)
    showToast('Cập nhật thông tin thành công!')
  } catch (e) {
    showToast(e.response?.data?.message || 'Lỗi cập nhật thông tin!', 'error')
  } finally {
    profileSaving.value = false
  }
}

const submitPass = async () => {
  if (passwordForm.password !== passwordForm.password_confirmation) {
    return showToast('Mật khẩu xác nhận không khớp!', 'error')
  }
  if (passwordForm.password.length < 8) {
    return showToast('Mật khẩu mới phải có ít nhất 8 ký tự!', 'error')
  }

  passwordSaving.value = true
  try {
    await api.put('/api/admin/profile/password', passwordForm)
    showToast('Đổi mật khẩu thành công!')
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
  } catch (e) {
    showToast(e.response?.data?.message || 'Lỗi đổi mật khẩu!', 'error')
  } finally {
    passwordSaving.value = false
  }
}
</script>

<template>
  <LayoutAuthenticated>
    <SectionMain>

      <UserCard class="mb-6" />

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <CardBox is-form @submit.prevent="submitProfile">
          <FormField label="Họ tên" help="Bắt buộc">
            <FormControl
              v-model="profileForm.name"
              :icon="mdiAccount"
              name="username"
              required
              autocomplete="username"
            />
          </FormField>
          <FormField label="Email" help="Bắt buộc">
            <FormControl
              v-model="profileForm.email"
              :icon="mdiMail"
              type="email"
              name="email"
              required
              autocomplete="email"
            />
          </FormField>

          <template #footer>
            <BaseButtons>
              <BaseButton color="info" type="submit" label="Lưu thay đổi"
                :disabled="profileSaving" :class="{ 'opacity-50': profileSaving }" />
            </BaseButtons>
          </template>
        </CardBox>

        <CardBox is-form @submit.prevent="submitPass">
          <FormField label="Mật khẩu hiện tại" help="Bắt buộc">
            <FormControl
              v-model="passwordForm.current_password"
              :icon="mdiAsterisk"
              name="current_password"
              type="password"
              required
              autocomplete="current-password"
            />
          </FormField>

          <BaseDivider />

          <FormField label="Mật khẩu mới" help="Bắt buộc, tối thiểu 8 ký tự">
            <FormControl
              v-model="passwordForm.password"
              :icon="mdiFormTextboxPassword"
              name="password"
              type="password"
              required
              autocomplete="new-password"
            />
          </FormField>

          <FormField label="Xác nhận mật khẩu mới" help="Bắt buộc, nhập lại mật khẩu mới">
            <FormControl
              v-model="passwordForm.password_confirmation"
              :icon="mdiFormTextboxPassword"
              name="password_confirmation"
              type="password"
              required
              autocomplete="new-password"
            />
          </FormField>

          <template #footer>
            <BaseButtons>
              <BaseButton type="submit" color="info" label="Đổi mật khẩu"
                :disabled="passwordSaving" :class="{ 'opacity-50': passwordSaving }" />
            </BaseButtons>
          </template>
        </CardBox>
      </div>
    </SectionMain>
  </LayoutAuthenticated>
</template>