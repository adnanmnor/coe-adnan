<script setup lang="ts">
const { isLoggedIn, token } = useAuth()
const router = useRouter()
const config = useRuntimeConfig()

onMounted(() => {
  if (!isLoggedIn.value) router.push('/login')
})

const { data, pending, error } = await useFetch<any>(`${config.public.apiBase}/orders`, {
  headers: computed(() => ({ Authorization: `Bearer ${token.value}` })),
})

function formatPrice(v: number) {
  return '$' + Number(v).toFixed(2)
}

function statusColor(s: string) {
  return {
    pending: '#f59e0b',
    paid: '#10b981',
    shipped: '#3b82f6',
    delivered: '#8b5cf6',
    failed: '#ef4444',
    cancelled: '#6b7280',
  }[s] || '#666'
}
</script>

<template>
  <div class="card">
    <h1>My Orders</h1>

    <p v-if="pending">Loading...</p>
    <p v-else-if="error" class="error">Error: {{ error.message }}</p>
    <p v-else-if="data?.data?.length === 0">Belum ada order. <NuxtLink to="/products">Lihat produk</NuxtLink>.</p>

    <table v-else>
      <thead>
        <tr>
          <th>Order #</th>
          <th>Status</th>
          <th>Total</th>
          <th>Items</th>
          <th>Date</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="order in data.data" :key="order.id">
          <td><code>{{ order.order_number }}</code></td>
          <td>
            <span :style="{ background: statusColor(order.status), color: 'white', padding: '0.2rem 0.6rem', borderRadius: '999px', fontSize: '0.75rem' }">
              {{ order.status }}
            </span>
          </td>
          <td>{{ formatPrice(order.total) }}</td>
          <td>{{ order.items.length }}</td>
          <td>{{ new Date(order.created_at).toLocaleString() }}</td>
          <td><NuxtLink :to="`/orders/${order.id}`" class="btn btn-sm">View</NuxtLink></td>
        </tr>
      </tbody>
    </table>
  </div>
</template>