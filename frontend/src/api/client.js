import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    Accept: 'application/json',
  },
})

api.interceptors.request.use(config => {
  // Cada petición reutiliza el token guardado al iniciar sesión.
  const token = sessionStorage.getItem('ciudad-alerta-token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(response => response, error => {
  // Si el token venció, limpia la sesión; los errores del login se muestran en su formulario.
  const isLoginRequest = error.config?.url?.includes('/auth/login')
  if (error.response?.status === 401 && !isLoginRequest) {
    sessionStorage.removeItem('ciudad-alerta-token')
    window.dispatchEvent(new CustomEvent('ciudad-alerta:session-expired'))
  }
  return Promise.reject(error)
})

export default api
