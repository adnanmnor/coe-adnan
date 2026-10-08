<script setup lang="ts">
const config = useRuntimeConfig()
const apiBase = config.public.apiBase
const router = useRouter()

const form = reactive({
  category_id: '',
  name: '',
  sku: '',
  description: '',
  price: 0,
  stock_quantity: 0,
  is_active: true,
})

const errors = ref<Record<string, string[]>>({})
const submitting = ref(false)

const { data: categories } = await useFetch(`${apiBase}/categories`)

async function submit() {
  submitting.value = true
  errors.value = {}
  try {
    const res = await $fetch(`${apiBase}/products`, {
      method: 'POST',
      body: form,
    })
    router.push(`/products/${res.data.id}`)
  } catch (e: any) {
    if (e.data?.errors) {
      errors.value = e.data.errors
    } else {
      errors.value = { _global: [e.message] }
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="card">
    <h1>Add Product</h1>

    <div v-if="errors._global" class="error">{{ errors._global.join(', ') }}</div>

    <form @submit.prevent="submit">
      <div class="form-group">
        <label>Category</label>
        <select v-model="form.category_id" required>
          <option value="">-- Select category --</option>
          <option v-for="c in categories?.data" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <small v-if="errors.category_id" class="error">{{ errors.category_id[0] }}</small>
      </div>

      <div class="form-group">
        <label>Name</label>
        <input v-model="form.name" required />
        <small v-if="errors.name" class="error">{{ errors.name[0] }}</small>
      </div>

      <div class="form-group">
        <label>SKU</label>
        <input v-model="form.sku" required />
        <small v-if="errors.sku" class="error">{{ errors.sku[0] }}</small>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea v-model="form.description" rows="3" style="width:100%;padding:0.5rem;border:1px solid #ccc;border-radius:4px;"></textarea>
      </div>

      <div class="row">
        <div class="form-group">
          <label>Price</label>
          <input v-model.number="form.price" type="number" step="0.01" min="0" required />
          <small v-if="errors.price" class="error">{{ errors.price[0] }}</small>
        </div>
        <div class="form-group">
          <label>Stock</label>
          <input v-model.number="form.stock_quantity" type="number" min="0" required />
          <small v-if="errors.stock_quantity" class="error">{{ errors.stock_quantity[0] }}</small>
        </div>
      </div>

      <div class="form-group">
        <label>
          <input type="checkbox" v-model="form.is_active" style="width:auto;" />
          Active
        </label>
      </div>

      <button type="submit" class="btn" :disabled="submitting">
        {{ submitting ? 'Saving...' : 'Create Product' }}
      </button>
      <NuxtLink to="/products" class="btn" style="background:#666; margin-left:0.5rem;">Cancel</NuxtLink>
    </form>
  </div>
</template>