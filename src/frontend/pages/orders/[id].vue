<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()
const { isLoggedIn, token } = useAuth()
const router = useRouter()

onMounted(() => {
  if (!isLoggedIn.value) router.push('/login')
})

const id = route.params.id

const { data, pending, error, refresh } = await useFetch<any>(
  `${config.public.apiBase}/orders/${id}`,
  { headers: computed(() => ({ Authorization: `Bearer ${token.value}` })) }
)

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
    <NuxtLink to="/orders" style="color:#666; text-decoration:none;">← Back to Orders</NuxtLink>

    <p v-if="pending">Loading...</p>
    <p v-else-if="error" class="error">Error: {{ error.message }}</p>

    <div v-else-if="data?.data">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1rem;">
        <h1><code>{{ data.data.order_number }}</code></h1>
        <span :style="{ background: statusColor(data.data.status), color: 'white', padding: '0.5rem 1rem', borderRadius: '999px', fontWeight: 'bold' }">
          {{ data.data.status.toUpperCase() }}
        </span>
      </div>

      <!-- Timeline -->
      <div style="display:flex; gap:1rem; margin:2rem 0; padding:1rem; background:#f7f7f8; border-radius:8px;">
        <div>
          <strong>Created</strong>
          <p style="font-size:0.875rem; color:#666;">{{ new Date(data.data.created_at).toLocaleString() }}</p>
        </div>
        <div v-if="data.data.paid_at">
          <strong>Paid</strong>
          <p style="font-size:0.875rem; color:#666;">{{ new Date(data.data.paid_at).toLocaleString() }}</p>
        </div>
        <div v-if="data.data.shipped_at">
          <strong>Shipped</strong>
          <p style="font-size:0.875rem; color:#666;">{{ new Date(data.data.shipped_at).toLocaleString() }}</p>
        </div>
        <div v-if="data.data.delivered_at">
          <strong>Delivered</strong>
          <p style="font-size:0.875rem; color:#666;">{{ new Date(data.data.delivered_at).toLocaleString() }}</p>
        </div>
      </div>

      <!-- Items -->
      <h2>Items</h2>
      <table>
        <thead>
          <tr>
            <th>Product</th>
            <th>SKU</th>
            <th>Unit Price</th>
            <th>Qty</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in data.data.items" :key="item.id">
            <td>{{ item.product_name }}</td>
            <td>{{ item.product_sku }}</td>
            <td>{{ formatPrice(item.unit_price) }}</td>
            <td>{{ item.quantity }}</td>
            <td>{{ formatPrice(item.subtotal) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Totals -->
      <div style="margin-top:1.5rem; text-align:right;">
        <p>Subtotal: {{ formatPrice(data.data.subtotal) }}</p>
        <p>Tax: {{ formatPrice(data.data.tax) }}</p>
        <p>Shipping: {{ formatPrice(data.data.shipping) }}</p>
        <p style="font-size:1.5rem; font-weight:bold;">Total: {{ formatPrice(data.data.total) }}</p>
      </div>

      <!-- Payments -->
      <h2 v-if="data.data.payments?.length" style="margin-top:2rem;">Payments</h2>
      <table v-if="data.data.payments?.length">
        <thead>
          <tr>
            <th>Method</th>
            <th>Status</th>
            <th>Amount</th>
            <th>Transaction ID</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in data.data.payments" :key="p.id">
            <td>{{ p.method }}</td>
            <td>
              <span :style="{ background: p.status === 'success' ? '#10b981' : '#ef4444', color: 'white', padding: '0.2rem 0.6rem', borderRadius: '999px', fontSize: '0.75rem' }">
                {{ p.status }}
              </span>
            </td>
            <td>{{ formatPrice(p.amount) }}</td>
            <td><code v-if="p.transaction_id">{{ p.transaction_id }}</code><span v-else>—</span></td>
            <td>{{ new Date(p.created_at).toLocaleString() }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>