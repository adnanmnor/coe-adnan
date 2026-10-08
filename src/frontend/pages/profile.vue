<script setup lang="ts">
const { user, token, fetchUser, logout } = useAuth()
const router = useRouter()
const config = useRuntimeConfig()

// Protect: redirect if not logged in
onMounted(async () => {
  if (!token.value) {
    router.push('/login')
    return
  }
  if (!user.value) {
    await fetchUser()
  }
})

const form = reactive({
  name: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

watchEffect(() => {
  if (user.value) {
    form.name = user.value.name || ''
    form.phone = user.value.phone || ''
  }
})

const errors = ref<Record<string, string[]>>({})
const success = ref('')
const loading = ref(false)

async function updateProfile() {
  errors.value = {}
  success.value = ''
  loading.value = true

  try {
    const body: any = { name: form.name, phone: form.phone }
    if (form.password) {
      body.password = form.password
      body.password_confirmation = form.password_confirmation
    }

    const res = await $fetch<{ user: any }>(`${config.public.apiBase}/auth/profile`, {
      method: 'PUT',
      headers: { Authorization: `Bearer ${token.value}` },
      body,
    })
    user.value = res.user
    success.value = 'Profile updated.'
    form.password = ''
    form.password_confirmation = ''
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

async function handleLogout() {
  await logout()
  router.push('/login')
}
</script>

<template>
  <div class="card" style="max-width:600px; margin:0 auto;">
    <h1>My Profile</h1>

    <div v-if="user" style="margin-bottom:1.5rem; padding:1rem; background:#f7f7f8; border-radius:4px;">
      <p><strong>Email:</strong> {{ user.email }}</p>
      <p><strong>Role:</strong> {{ user.role }}</p>
    </div>

    <p v-if="success" style="color:green; background:#efe; padding:0.5rem; border-radius:4px;">
      {{ success }}
    </p>
    <p v-if="errors._global" class="error">{{ errors._global[0] }}</p>

    <form @submit.prevent="updateProfile">
      <div class="form-group">
        <label>Name</label>
        <input v-model="form.name" required />
        <small v-if="errors.name" class="error">{{ errors.name[0] }}</small>
      </div>

      <div class="form-group">
        <label>Phone</label>
        <input v-model="form.phone" />
        <small v-if="errors.phone" class="error">{{ errors.phone[0] }}</small>
      </div>

      <div class="form-group">
        <label>New Password (kosongkan kalau tak tukar)</label>
        <input v-model="form.password" type="password" />
        <small v-if="errors.password" class="error">{{ errors.password[0] }}</small>
      </div>

      <div class="form-group">
        <label>Confirm New Password</label>
        <input v-model="form.password_confirmation" type="password" />
      </div>

      <button type="submit" class="btn" :disabled="loading">
        {{ loading ? 'Saving...' : 'Save Changes' }}
      </button>
      <button type="button" class="btn" style="background:#900; margin-left:0.5rem;" @click="handleLogout">
        Logout
      </button>
    </form>
  </div>
</template>