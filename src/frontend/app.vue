<script setup lang="ts">
const { user, isLoggedIn, fetchUser, logout } = useAuth()
const { count, fetchCart } = useCart()
const router = useRouter()

onMounted(async () => {
  if (isLoggedIn.value) {
    if (!user.value) await fetchUser()
    await fetchCart()
  }
})

async function handleLogout() {
  await logout()
  router.push('/login')
}
</script>

<template>
  <div class="app">
    <header class="nav">
      <NuxtLink to="/" class="brand">COE E-Commerce</NuxtLink>
      <nav>
        <NuxtLink to="/products">Products</NuxtLink>
        <NuxtLink v-if="user?.role === 'admin'" to="/products/create">Add Product</NuxtLink>
        <NuxtLink v-if="isLoggedIn" to="/cart">
          Cart <span v-if="count > 0" class="badge">{{ count }}</span>
        </NuxtLink>
        <NuxtLink v-if="isLoggedIn" to="/orders">Orders</NuxtLink>
        <NuxtLink v-if="!isLoggedIn" to="/login">Login</NuxtLink>
        <NuxtLink v-if="!isLoggedIn" to="/register">Register</NuxtLink>
        <NuxtLink v-if="isLoggedIn" to="/profile">Profile</NuxtLink>
        <a v-if="isLoggedIn" href="#" @click.prevent="handleLogout" style="color:white; margin-left:1.5rem; cursor:pointer;">Logout</a>
      </nav>
    </header>
    <main class="main">
      <NuxtPage />
    </main>
  </div>
</template>

<style>
* { box-sizing: border-box; }
body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: #f7f7f8; }
.app { min-height: 100vh; }
.nav { display: flex; justify-content: space-between; align-items: center; padding: 1rem 2rem; background: #1a1a2e; color: white; }
.brand { font-weight: bold; font-size: 1.2rem; color: white; text-decoration: none; }
.nav nav a { color: white; text-decoration: none; margin-left: 1.5rem; }
.nav nav a:hover { text-decoration: underline; }
.badge { background: #ef4444; color: white; border-radius: 999px; padding: 0.1rem 0.5rem; font-size: 0.75rem; margin-left: 0.25rem; }
.main { padding: 2rem; max-width: 1100px; margin: 0 auto; }
.card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #eee; }
th { background: #fafafa; font-weight: 600; }
.btn { padding: 0.5rem 1rem; background: #1a1a2e; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
.btn:hover { background: #2a2a4e; }
.btn-sm { padding: 0.25rem 0.75rem; font-size: 0.875rem; }
input, select, textarea { padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem; width: 100%; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.25rem; font-weight: 500; }
.row { display: flex; gap: 1rem; }
.row > * { flex: 1; }
.error { color: #c00; background: #fee; padding: 0.5rem; border-radius: 4px; margin-bottom: 1rem; }
.pagination { display: flex; gap: 0.25rem; margin-top: 1rem; }
.pagination button { padding: 0.5rem 0.75rem; border: 1px solid #ccc; background: white; cursor: pointer; border-radius: 4px; }
.pagination button.active { background: #1a1a2e; color: white; border-color: #1a1a2e; }
.pagination button:disabled { opacity: 0.4; cursor: not-allowed; }
</style>