import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'

export default function ProtectedRoute({ adminOnly = false }) {
  const { user, loading } = useAuth()

  if (loading) return <div className="loading-state"><span className="spinner" />Comprobando sesión…</div>
  // El navegador oculta pantallas según la sesión y el rol; la API valida permisos por separado.
  if (!user) return <Navigate to="/login" replace />
  if (adminOnly && !user.EsAdministrador) return <Navigate to="/incidencias" replace />

  return <Outlet />
}
