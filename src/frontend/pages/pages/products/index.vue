<script setup lang="ts">
const config = useRuntimeConfig()
const apiBase = config.public.apiBase

const search = ref('')
const sort = ref('created_at')
const order = ref('desc')
const page = ref(1)
const perPage = 10

const query = computed(() => ({
  search: search.value || undefined,
  sort: sort.value,
  order: order.value,
  page: page.value,
  per_page: perPage,
}))

const { data, pending, error } = await useFetch(`${apiBase}/products`, {
  query,
  watch: [query],
})

function formatPrice(v: number) {
  return '$' + Number(v).toFixed(2)
}

function goPage(p: number) {
  page.value = p
}
</script>

<template>
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
      <h1>Products</h1>
      <NuxtLink to="/products/create" class="btn">+ Add Product</NuxtLink>
    </div>

    <div class="row" style="margin-bottom:1rem;">
      <input v-model="search" placeholder="Search by name, SKU, description..." />
      <select v-model="sort" style="max-width:200px;">
        <option value="created_at">Newest</option>
        <option value="name">Name</option>
        <option value="price">Price</option>
        <option value="stock_quantity">Stock</option>
      </select>
      <select v-model="order" style="max-width:150px;">
        <option value="desc">Desc</option>
        <option value="asc">Asc</option>
      </select>
    </div>

    <p v-if="pending">Loading...</p>
    <p v-else-if="error" class="error">Error: {{ error.message }}</p>

    <table v-else-if="data?.data?.length">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>SKU</th>
          <th>Category</th>
          <th>Price</th>
          <th>Stock</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="p in data.data" :key="p.id">
          <td>{{ p.id }}</td>
          <td>{{ p.name }}</td>
          <td>{{ p.sku }}</td>
          <td>{{ p.category?.name }}</td>
          <td>{{ formatPrice(p.price) }}</td>
          <td>{{ p.stock_quantity }}</td>
          <td>
            <NuxtLink :to="`/products/${p.id}`" class="btn btn-sm">View</NuxtLink>
          </td>
        </tr>
      </tbody>
    </table>

    <p v-else>No products found.</p>

    <div v-if="data?.meta" class="pagination">
      <button :disabled="!data.links.prev" @click="goPage(data.meta.current_page - 1)">‹ Prev</button>
      <button
        v-for="link in data.meta.links.slice(1, -1)"
        :key="link.label"
        :class="{ active: link.active }"
        :disabled="!link.url"
        @click="goPage(link.page)"
      >
        {{ link.label }}
      </button>
      <button :disabled="!data.links.next" @click="goPage(data.meta.current_page + 1)">Next ›</button>
    </div>

    <p v-if="data?.meta" style="margin-top:1rem; color:#666;">
      Showing page {{ data.meta.current_page }} of {{ data.meta.last_page }} — total {{ data.meta.total }} products.
    </p>
  </div>
</template>