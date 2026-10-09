export const useAuth = () => {
  const user = useState<any | null>('auth_user', () => null)
  const token = useState<string | null>('auth_token_state', () => null)
  const config = useRuntimeConfig()

  const isLoggedIn = computed(() => !!token.value)

  function loadToken() {
    if (process.client) {
      const t = localStorage.getItem('auth_token')
      if (t) token.value = t
    }
  }

  function saveToken(t: string | null) {
    token.value = t
    if (process.client) {
      if (t) localStorage.setItem('auth_token', t)
      else localStorage.removeItem('auth_token')
    }
  }

  async function fetchUser() {
    loadToken()
    if (!token.value) {
      user.value = null
      return null
    }
    try {
      const res = await $fetch<{ user: any }>(`${config.public.apiBase}/auth/me`, {
        headers: { Authorization: `Bearer ${token.value}` },
      })
      user.value = res.user
      return res.user
    } catch (e) {
      saveToken(null)
      user.value = null
      return null
    }
  }

  async function login(email: string, password: string) {
    const res = await $fetch<{ user: any; token: string }>(`${config.public.apiBase}/auth/login`, {
      method: 'POST',
      body: { email, password },
    })
    saveToken(res.token)
    user.value = res.user
    return res
  }

  async function register(payload: { name: string; email: string; password: string; password_confirmation: string; phone?: string }) {
    const res = await $fetch<{ user: any; token: string }>(`${config.public.apiBase}/auth/register`, {
      method: 'POST',
      body: payload,
    })
    saveToken(res.token)
    user.value = res.user
    return res
  }

  async function logout() {
    try {
      if (token.value) {
        await $fetch(`${config.public.apiBase}/auth/logout`, {
          method: 'POST',
          headers: { Authorization: `Bearer ${token.value}` },
        })
      }
    } catch (e) {}
    saveToken(null)
    user.value = null
  }

  if (process.client) {
    loadToken()
  }

  return { token, user, isLoggedIn, fetchUser, login, register, logout }
}