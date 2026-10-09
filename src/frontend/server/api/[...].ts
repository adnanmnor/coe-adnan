export default defineEventHandler(async (event) => {
  const path = event.context.params?._ || ''
  const target = `http://backend:8000/api/${path}`
  const query = getQuery(event)
  const method = event.method

  // Forward Authorization header dari browser ke backend
  const incomingHeaders = getRequestHeaders(event)
  const forwardHeaders: Record<string, string> = {
    'Accept': 'application/json',
  }

  if (incomingHeaders.authorization) {
    forwardHeaders['Authorization'] = incomingHeaders.authorization
  }

  let body: any = undefined
  if (method !== 'GET' && method !== 'HEAD') {
    const contentType = incomingHeaders['content-type'] || ''
    if (contentType.includes('application/json')) {
      forwardHeaders['Content-Type'] = 'application/json'
      body = await readBody(event).catch(() => undefined)
    } else {
      // Biar $fetch handle multipart (FormData) sendiri
      body = await readBody(event).catch(() => undefined)
    }
  }

  try {
    return await $fetch(target, {
      method,
      query,
      body,
      headers: forwardHeaders,
    })
  } catch (e: any) {
    throw createError({
      statusCode: e.response?.status || 500,
      statusMessage: e.message,
      data: e.response?._data,
    })
  }
})