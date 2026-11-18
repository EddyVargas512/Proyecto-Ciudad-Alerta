import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import { actualizarPerfil as requestProfileUpdate, cerrarSesion as requestLogout, iniciarSesion, obtenerSesion, registrarUsuario } from '../services/auth.js'

const AuthContext = createContext(null)
const TOKEN_KEY = 'ciudad-alerta-token'

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(() => Boolean(sessionStorage.getItem(TOKEN_KEY)))

  useEffect(() => {
    let active = true
    const token = sessionStorage.getItem(TOKEN_KEY)

    if (!token) {
      return () => { active = false }
    }

    // Al recargar, valida el token con Laravel y restaura los datos del usuario.
    obtenerSesion()
      .then(currentUser => { if (active) setUser(currentUser) })
      .catch(() => { sessionStorage.removeItem(TOKEN_KEY) })
      .finally(() => { if (active) setLoading(false) })

    return () => { active = false }
  }, [])

  useEffect(() => {
    const expireSession = () => setUser(null)
    window.addEventListener('ciudad-alerta:session-expired', expireSession)
    return () => window.removeEventListener('ciudad-alerta:session-expired', expireSession)
  }, [])

  async function login(credentials) {
    const response = await iniciarSesion(credentials)
    sessionStorage.setItem(TOKEN_KEY, response.token)
    setUser(response.usuario)
    return response.usuario
  }

  async function register(details) {
    const response = await registrarUsuario(details)
    sessionStorage.setItem(TOKEN_KEY, response.token)
    setUser(response.usuario)
    return response.usuario
  }

  async function updateProfile(details) {
    const response = await requestProfileUpdate(details)
    setUser(response.usuario)
    return response.usuario
  }

  async function logout() {
    try {
      if (sessionStorage.getItem(TOKEN_KEY)) await requestLogout()
    } finally {
      sessionStorage.removeItem(TOKEN_KEY)
      setUser(null)
    }
  }

  // Este contexto comparte la sesión con login, navegación, perfil y rutas protegidas.
  const value = useMemo(() => ({ user, loading, login, register, updateProfile, logout }), [user, loading])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

// oxlint-disable-next-line react/only-export-components -- provider and hook share this context module.
export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) throw new Error('useAuth debe usarse dentro de AuthProvider.')
  return context
}
