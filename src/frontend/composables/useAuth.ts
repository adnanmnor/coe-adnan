export const useAuth = () => {
  const token = useCookie<string | null>('auth_token', {
    maxAge: 60 * 60 * 24 * 7,
    sameSite: 'lax',
  })

  const user = useState<any | null>('auth_user', () => null)
  const config = useRuntimeConfig()

  const isLoggedIn = computed(() => !!token.value)

  async function fetchUser() {
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
      token.value = null
      user.value = null
      return null
    }
  }

  async function login(email: string, password: string) {
    const res = await $fetch<{ user: any; token: string }>(`${config.public.apiBase}/auth/login`, {
      method: 'POST',
      body: { email, password },
    })
    token.value = res.token
    user.value = res.user
    return res
  }

  async function register(payload: { name: string; email: string; password: string; password_confirmation: string; phone?: string }) {
    const res = await $fetch<{ user: any; token: string }>(`${config.public.apiBase}/auth/register`, {
      method: 'POST',
      body: payload,
    })
    token.value = res.token
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
    } catch (e) {
      // ignore
    }
    token.value = null
    user.value = null
  }

  return {
    token,
    user,
    isLoggedIn,
    fetchUser,
    login,
    register,
    logout,
  }
}