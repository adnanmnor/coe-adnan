<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase

const id = route.params.id

const { data, pending, error } = await useFetch(`${apiBase}/products/${id}`)

function formatPrice(v: number) {
  return '$' + Number(v).toFixed(2)
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

      <p style="margin-top:1.5rem; color:#999; font-size:0.875rem;">
        Product ID: {{ data.data.id }} · Last updated: {{ data.data.updated_at }}
      </p>
    </div>
  </div>
</template>