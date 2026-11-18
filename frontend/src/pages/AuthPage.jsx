import { useEffect, useState } from 'react'
import { Link, Navigate, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'
import { mensajeDeError } from '../services/incidencias.js'

export default function AuthPage({ registerMode = false }) {
  const { user, loading, login, register } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState({ Nombre: '', Email: '', Contraseña: '', Contraseña_confirmation: '' })
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    if (user) navigate(user.EsAdministrador ? '/admin' : '/incidencias', { replace: true })
  }, [user, navigate])

  if (loading) return <div className="auth-loading"><span className="spinner" />Cargando…</div>
  if (user) return <Navigate to={user.EsAdministrador ? '/admin' : '/incidencias'} replace />

  function handleChange(event) {
    const { name, value } = event.target
    setForm(current => ({ ...current, [name]: value }))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setSubmitting(true)
    setError('')

    try {
      const currentUser = registerMode
        ? await register(form)
        : await login({ Email: form.Email, Contraseña: form.Contraseña })
      navigate(currentUser.EsAdministrador ? '/admin' : '/incidencias', { replace: true })
    } catch (requestError) {
      setError(mensajeDeError(requestError))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main className="auth-page">
      <section className="auth-card">
        <p>Ciudad Alerta</p>
        <div className="auth-intro">
          <p className="eyebrow">Cuidemos la ciudad entre todos</p>
          <h1>{registerMode ? 'Crea tu cuenta' : 'Bienvenido de nuevo'}</h1>
          <p>{registerMode ? 'Regístrate para informar problemas y apoyar reportes de tu comunidad.' : 'Ingresa a tu cuenta para reportar incidencias y darles seguimiento.'}</p>
        </div>
        {error && <div className="notice error" role="alert">{error}</div>}
        <form className="auth-form" onSubmit={handleSubmit}>
          {registerMode && <div className="field"><label htmlFor="Nombre">Nombre completo</label><input id="Nombre" name="Nombre" value={form.Nombre} onChange={handleChange} autoComplete="name" maxLength="100" required /></div>}
          <div className="field"><label htmlFor="Email">Correo electrónico</label><input id="Email" name="Email" type="email" value={form.Email} onChange={handleChange} autoComplete="email" required /></div>
          <div className="field"><label htmlFor="Contraseña">Contraseña</label><input id="Contraseña" name="Contraseña" type="password" value={form.Contraseña} onChange={handleChange} autoComplete={registerMode ? 'new-password' : 'current-password'} minLength="8" required /></div>
          {registerMode && <div className="field"><label htmlFor="Contraseña_confirmation">Confirma tu contraseña</label><input id="Contraseña_confirmation" name="Contraseña_confirmation" type="password" value={form.Contraseña_confirmation} onChange={handleChange} autoComplete="new-password" minLength="8" required /></div>}
          <button className="button auth-submit" type="submit" disabled={submitting}>{submitting ? 'Un momento…' : registerMode ? 'Crear cuenta' : 'Iniciar sesión'}</button>
        </form>
        <p className="auth-switch">{registerMode ? '¿Ya tienes cuenta?' : '¿Eres nuevo en Ciudad Alerta?'} <Link to={registerMode ? '/login' : '/registro'}>{registerMode ? 'Inicia sesión' : 'Crea una cuenta'}</Link></p>
        <p className="auth-footnote">Las cuentas nuevas se crean con perfil ciudadano. El acceso de administración lo configura el responsable del sistema.</p>
      </section>
      <aside className="auth-aside">
        <div className="auth-aside-content">
          <span className="auth-aside-kicker">Una ciudad que escucha</span>
          <h2>Un reporte puede ser el primer paso para mejorar un lugar.</h2>
          <p>Comparte lo que ocurre en tu barrio. La comunidad puede apoyar el reporte y el equipo encargado actualizará su seguimiento.</p>
        </div>
        <div className="map-dots" aria-hidden="true" />
      </aside>
    </main>
  )
}
