<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()
const { user, token, isLoggedIn } = useAuth()
const { addItem } = useCart()
const router = useRouter()

const id = route.params.id

const { data, pending, error, refresh } = await useFetch(`${config.public.apiBase}/products/${id}`)

function formatPrice(v: number) {
  return '$' + Number(v).toFixed(2)
}

// Cart
const quantity = ref(1)
const adding = ref(false)
const addedMessage = ref('')
const cartError = ref('')

async function handleAddToCart() {
  if (!isLoggedIn.value) {
    router.push('/login')
    return
  }

  adding.value = true
  cartError.value = ''
  addedMessage.value = ''

  try {
    await addItem(parseInt(id as string), quantity.value)
    addedMessage.value = `Added ${quantity.value} item(s) to cart.`
    setTimeout(() => { addedMessage.value = '' }, 3000)
  } catch (e: any) {
    cartError.value = e.data?.message || e.message
  } finally {
    adding.value = false
  }
}

// Image upload (admin)
const fileInput = ref<HTMLInputElement | null>(null)
const uploading = ref(false)
const uploadError = ref('')

async function uploadImage() {
  const file = fileInput.value?.files?.[0]
  if (!file) return

  uploading.value = true
  uploadError.value = ''

  const formData = new FormData()
  formData.append('image', file)

  try {
    await $fetch(`${config.public.apiBase}/products/${id}/image`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}` },
      body: formData,
    })
    await refresh()
  } catch (e: any) {
    uploadError.value = e.data?.message || e.message
  } finally {
    uploading.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}
</script>

<template>
  <div class="card">
    <NuxtLink to="/products" style="color:#666; text-decoration:none;">← Back to Products</NuxtLink>

    <p v-if="pending">Loading...</p>
    <p v-else-if="error" class="error">Error: {{ error.message }}</p>

    <div v-else-if="data?.data">
      <h1>{{ data.data.name }}</h1>
      <p style="color:#666;">{{ data.data.description || 'No description' }}</p>

      <!-- Image -->
      <div style="margin:1rem 0;">
        <img
          v-if="data.data.image_url"
          :src="data.data.image_url"
          alt="Product image"
          style="max-width:400px; border-radius:8px; border:1px solid #eee;"
        />
        <p v-else style="color:#999;">No image uploaded.</p>
      </div>

      <!-- Upload form (admin only) -->
      <div v-if="user?.role === 'admin'" style="margin-top:1rem; padding:1rem; background:#f7f7f8; border-radius:4px;">
        <label style="display:block; font-weight:500;">Upload Product Image (max 5MB)</label>
        <input type="file" ref="fileInput" accept="image/*" style="margin-top:0.5rem;" />
        <button class="btn" style="margin-top:0.5rem;" :disabled="uploading" @click="uploadImage">
          {{ uploading ? 'Uploading...' : 'Upload' }}
        </button>
        <p v-if="uploadError" class="error" style="margin-top:0.5rem;">{{ uploadError }}</p>
      </div>

      <div class="row" style="margin-top:1.5rem;">
        <div>
          <strong>SKU</strong>
          <p>{{ data.data.sku }}</p>
        </div>
        <div>
          <strong>Category</strong>
          <p>{{ data.data.category?.name }}</p>
        </div>
      </div>

      <div class="row" style="margin-top:1rem;">
        <div>
          <strong>Price</strong>
          <p style="font-size:1.5rem; color:#1a1a2e; font-weight:bold;">
            {{ formatPrice(data.data.price) }}
          </p>
        </div>
        <div>
          <strong>Stock</strong>
          <p>{{ data.data.stock_quantity }} units</p>
        </div>
        <div>
          <strong>Status</strong>
          <p>{{ data.data.is_active ? 'Active' : 'Inactive' }}</p>
        </div>
      </div>

      <!-- ADD TO CART -->
      <div style="margin-top:2rem; padding:1rem; background:#f0f4ff; border-radius:8px;">
        <p v-if="addedMessage" style="color:#10b981; font-weight:500;">✓ {{ addedMessage }}</p>
        <p v-if="cartError" class="error">{{ cartError }}</p>

        <div style="display:flex; gap:0.5rem; align-items:flex-end;">
          <div>
            <label style="display:block; font-weight:500; margin-bottom:0.25rem;">Quantity</label>
            <input
              v-model.number="quantity"
              type="number"
              min="1"
              :max="data.data.stock_quantity"
              style="width:100px;"
            />
          </div>
          <button
            class="btn"
            :disabled="adding || data.data.stock_quantity < 1"
            @click="handleAddToCart"
          >
            {{ adding ? 'Adding...' : 'Add to Cart' }}
          </button>
          <NuxtLink v-if="isLoggedIn" to="/cart" class="btn" style="background:#666;">View Cart</NuxtLink>
        </div>

        <p v-if="!isLoggedIn" style="margin-top:0.5rem; color:#666; font-size:0.875rem;">
          <NuxtLink to="/login">Login</NuxtLink> untuk tambah ke cart.
        </p>
      </div>

      <p style="margin-top:1.5rem; color:#999; font-size:0.875rem;">
        Product ID: {{ data.data.id }} · Last updated: {{ data.data.updated_at }}
      </p>
    </div>
  </div>
</template>