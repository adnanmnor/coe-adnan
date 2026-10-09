export const useCart = () => {
  const { token } = useAuth()
  const config = useRuntimeConfig()

  const items = ref<any[]>([])
  const total = ref(0)
  const loading = ref(false)

  async function fetchCart() {
    if (!token.value) {
      items.value = []
      total.value = 0
      return
    }
    loading.value = true
    try {
      const res = await $fetch<{ items: any[]; total: number }>(
        `${config.public.apiBase}/cart`,
        { headers: { Authorization: `Bearer ${token.value}` } }
      )
      items.value = res.items
      total.value = res.total
    } catch (e) {
      items.value = []
      total.value = 0
    } finally {
      loading.value = false
    }
  }

  async function addItem(productId: number, quantity = 1) {
  const auth = useAuth()
  console.log('[useCart] token from useAuth():', auth.token.value)
  console.log('[useCart] token from closure:', token.value)
  console.log('[useCart] localStorage:', localStorage.getItem('auth_token'))

  const res = await $fetch<{ items: any[]; total: number }>(
    `${config.public.apiBase}/cart/items`,
    {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}` },
      body: { product_id: productId, quantity },
    }
  )
  items.value = res.items
  total.value = res.total
}

  async function updateItem(productId: number, quantity: number) {
    const res = await $fetch<{ items: any[]; total: number }>(
      `${config.public.apiBase}/cart/items/${productId}`,
      {
        method: 'PUT',
        headers: { Authorization: `Bearer ${token.value}` },
        body: { quantity },
      }
    )
    items.value = res.items
    total.value = res.total
  }

  async function removeItem(productId: number) {
    const res = await $fetch<{ items: any[]; total: number }>(
      `${config.public.apiBase}/cart/items/${productId}`,
      { method: 'DELETE', headers: { Authorization: `Bearer ${token.value}` } }
    )
    items.value = res.items
    total.value = res.total
  }

  async function clearCart() {
    await $fetch(`${config.public.apiBase}/cart`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${token.value}` },
    })
    items.value = []
    total.value = 0
  }

  const count = computed(() =>
    items.value.reduce((sum, i) => sum + (i.quantity || 0), 0)
  )

  return { items, total, count, loading, fetchCart, addItem, updateItem, removeItem, clearCart }
}