// composables/useAuth.ts
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const isAuthenticated = ref(false)
const user = ref(null)
const isLoading = ref(false)

export function useAuth() {
  const router = useRouter()

  const checkAuth = () => {
    // Check if user is authenticated (JWT token, session, etc.)
    const token = localStorage.getItem('auth_token')
    // or check your authentication method
    isAuthenticated.value = !!token
  }

  const redirectToLogin = (returnUrl?: string) => {
    const query = returnUrl ? { redirect: returnUrl } : {}
    router.push({ name: 'login', query })
  }

  const requireAuth = (callback: () => void, returnUrl?: string) => {
    if (!isAuthenticated.value) {
      redirectToLogin(returnUrl)
      return false
    }
    callback()
    return true
  }

  return {
    isAuthenticated: computed(() => isAuthenticated.value),
    user: computed(() => user.value),
    isLoading: computed(() => isLoading.value),
    checkAuth,
    redirectToLogin,
    requireAuth
  }
}
