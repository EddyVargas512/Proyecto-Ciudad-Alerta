import { useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'

export default function AppLayout() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [loggingOut, setLoggingOut] = useState(false)

  async function handleLogout() {
    setLoggingOut(true)
    try { await logout() } finally { navigate('/login', { replace: true }) }
  }

  return (
    <>
      <nav style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 16, padding: 16 }} aria-label="Navegación principal">
        <NavLink to="/incidencias">Incidencias</NavLink>
        <NavLink to="/incidencias/nueva">Nueva incidencia</NavLink>
        <NavLink to="/perfil">Mi perfil</NavLink>
        {user?.EsAdministrador && <NavLink to="/admin">Administración</NavLink>}
        <span>{user?.Nombre}</span>
        <button type="button" onClick={handleLogout} disabled={loggingOut}>{loggingOut ? 'Cerrando sesión…' : 'Cerrar sesión'}</button>
      </nav>
      <main style={{ padding: 16 }}><Outlet /></main>
    </>
  )
}
