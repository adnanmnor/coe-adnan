<script setup lang="ts">
const { register } = useAuth()
const router = useRouter()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
})

const errors = ref<Record<string, string[]>>({})
const loading = ref(false)

async function submit() {
  errors.value = {}
  loading.value = true
  try {
    await register(form)
    router.push('/')
  } catch (e: any) {
    if (e.data?.errors) {
      errors.value = e.data.errors
    } else {
      errors.value = { _global: [e.data?.message || e.message] }
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="card" style="max-width:500px; margin:0 auto;">
    <h1>Register</h1>

    <p v-if="errors._global" class="error">{{ errors._global[0] }}</p>

    <form @submit.prevent="submit">
      <div class="form-group">
        <label>Name</label>
        <input v-model="form.name" required />
        <small v-if="errors.name" class="error">{{ errors.name[0] }}</small>
      </div>

      <div class="form-group">
        <label>Email</label>
        <input v-model="form.email" type="email" required />
        <small v-if="errors.email" class="error">{{ errors.email[0] }}</small>
      </div>

      <div class="form-group">
        <label>Phone (optional)</label>
        <input v-model="form.phone" />
      </div>

      <div class="form-group">
        <label>Password</label>
        <input v-model="form.password" type="password" required />
        <small v-if="errors.password" class="error">{{ errors.password[0] }}</small>
      </div>

      <div class="form-group">
        <label>Confirm Password</label>
        <input v-model="form.password_confirmation" type="password" required />
      </div>

      <button type="submit" class="btn" :disabled="loading">
        {{ loading ? 'Creating...' : 'Create Account' }}
      </button>
    </form>

    <p style="margin-top:1rem;">
      Sudah ada akaun?
      <NuxtLink to="/login">Login di sini</NuxtLink>
    </p>
  </div>
</template>