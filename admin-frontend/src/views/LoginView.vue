// admin-frontend/src/views/LoginView.vue
<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { mdiAccount, mdiAsterisk } from '@mdi/js'
import api from '@/services/api.js'
import { useMainStore } from '@/stores/main.js'
import SectionFullScreen from '@/components/SectionFullScreen.vue'
import CardBox from '@/components/CardBox.vue'
import FormField from '@/components/FormField.vue'
import FormControl from '@/components/FormControl.vue'
import BaseButton from '@/components/BaseButton.vue'
import BaseButtons from '@/components/BaseButtons.vue'
import FormCheckRadio from '@/components/FormCheckRadio.vue'
import LayoutGuest from '@/layouts/LayoutGuest.vue'

const form = reactive({
  login: 'admin@electro.vn',
  pass: 'password',
  remember: false,
})

const error = ref('')
const loading = ref(false)
const router = useRouter()
const mainStore = useMainStore()

const submit = async () => {
  error.value = ''
  loading.value = true
  try {
    const res = await api.post('/api/admin/login', {
      email: form.login,
      password: form.pass,
    })
    localStorage.setItem('admin_token', res.data.token)
    mainStore.setUser({
      name: res.data.user.name,
      email: res.data.user.email,
    })
    router.push('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Đăng nhập thất bại'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <LayoutGuest>
    <SectionFullScreen v-slot="{ cardClass }" bg="purplePink">
      <CardBox :class="cardClass" is-form @submit.prevent="submit">
        <div v-if="error" class="mb-4 rounded bg-red-100 p-3 text-sm text-red-700">
          {{ error }}
        </div>

        <FormField label="Email" help="Nhập email admin">
          <FormControl v-model="form.login" :icon="mdiAccount"
                       type="email" name="email" autocomplete="email" />
        </FormField>

        <FormField label="Mật khẩu" help="Nhập mật khẩu">
          <FormControl v-model="form.pass" :icon="mdiAsterisk"
                       type="password" name="password" autocomplete="current-password" />
        </FormField>

        <FormCheckRadio v-model="form.remember" name="remember"
                        label="Ghi nhớ đăng nhập" :input-value="true" />

        <template #footer>
          <BaseButtons>
            <BaseButton type="submit" color="info" label="Đăng nhập"
                        :disabled="loading" />
          </BaseButtons>
        </template>
      </CardBox>
    </SectionFullScreen>
  </LayoutGuest>
</template>