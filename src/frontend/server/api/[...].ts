export default defineEventHandler(async (event) => {
  const path = event.context.params?._ || ''
  const target = `http://backend:8000/api/${path}`
  const query = getQuery(event)
  const method = event.method

  const body = method !== 'GET' && method !== 'HEAD'
    ? await readBody(event).catch(() => undefined)
    : undefined

  try {
    return await $fetch(target, {
      method,
      query,
      body,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    })
  } catch (e: any) {
    throw createError({
      statusCode: e.response?.status || 500,
      statusMessage: e.message,
      data: e.response?._data,
    })
  }
})