<script setup lang="ts">
const { login } = useAuth()
const router = useRouter()

const form = reactive({ email: '', password: '' })
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await login(form.email, form.password)
    router.push('/')
  } catch (e: any) {
    error.value = e.data?.message || e.data?.errors?.email?.[0] || e.message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="card" style="max-width:500px; margin:0 auto;">
    <h1>Login</h1>

    <p v-if="error" class="error">{{ error }}</p>

    <form @submit.prevent="submit">
      <div class="form-group">
        <label>Email</label>
        <input v-model="form.email" type="email" required />
      </div>

      <div class="form-group">
        <label>Password</label>
        <input v-model="form.password" type="password" required />
      </div>

      <button type="submit" class="btn" :disabled="loading">
        {{ loading ? 'Logging in...' : 'Login' }}
      </button>
    </form>

    <p style="margin-top:1rem;">
      Belum ada akaun?
      <NuxtLink to="/register">Daftar di sini</NuxtLink>
    </p>
  </div>
</template>