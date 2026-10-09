<script setup lang="ts">
const { items, total, loading, fetchCart, updateItem, removeItem, clearCart } = useCart()
const { isLoggedIn, token } = useAuth()
const router = useRouter()
const config = useRuntimeConfig()

const checkoutLoading = ref(false)
const error = ref('')

onMounted(async () => {
  if (!isLoggedIn.value) {
    router.push('/login')
    return
  }
  await fetchCart()
})

function formatPrice(v: number) {
  return '$' + Number(v).toFixed(2)
}

async function handleCheckout() {
  if (items.value.length === 0) return
  checkoutLoading.value = true
  error.value = ''
  try {
    const res = await $fetch<{ data: { id: number } }>(
      `${config.public.apiBase}/checkout`,
      {
        method: 'POST',
        headers: { Authorization: `Bearer ${token.value}` },
      }
    )
    router.push(`/orders/${res.data.id}`)
  } catch (e: any) {
    error.value = e.data?.message || e.message
  } finally {
    checkoutLoading.value = false
  }
}
</script>

<template>
  <div class="card">
    <h1>My Cart</h1>

    <p v-if="loading">Loading...</p>
    <p v-else-if="items.length === 0">Cart kosong. <NuxtLink to="/products">Lihat produk</NuxtLink>.</p>

    <div v-else>
      <p v-if="error" class="error">{{ error }}</p>

      <table>
        <thead>
          <tr>
            <th>Product</th>
            <th>SKU</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Subtotal</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.product_id">
            <td>{{ item.name }}</td>
            <td>{{ item.sku }}</td>
            <td>{{ formatPrice(item.price) }}</td>
            <td>
              <input
                type="number"
                min="1"
                :value="item.quantity"
                style="width:80px;"
                @change="updateItem(item.product_id, parseInt(($event.target as HTMLInputElement).value))"
              />
            </td>
            <td>{{ formatPrice(item.subtotal) }}</td>
            <td>
              <button class="btn btn-sm" style="background:#900;" @click="removeItem(item.product_id)">Remove</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div style="margin-top:1.5rem; text-align:right;">
        <p style="font-size:1.5rem; font-weight:bold;">Total: {{ formatPrice(total) }}</p>
        <button class="btn" :disabled="checkoutLoading" @click="handleCheckout">
          {{ checkoutLoading ? 'Processing...' : 'Checkout' }}
        </button>
        <button class="btn" style="background:#666; margin-left:0.5rem;" @click="clearCart">Clear Cart</button>
      </div>
    </div>
  </div>
</template>