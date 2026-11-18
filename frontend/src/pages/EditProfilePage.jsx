import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'
import { mensajeDeError } from '../services/incidencias.js'

export default function EditProfilePage() {
  const { user, updateProfile } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState({ Nombre: user?.Nombre ?? '', Email: user?.Email ?? '' })
  const [error, setError] = useState('')
  const [saving, setSaving] = useState(false)

  function handleChange(event) {
    const { name, value } = event.target
    setForm(current => ({ ...current, [name]: value }))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setSaving(true)
    setError('')

    try {
      await updateProfile(form)
      navigate('/perfil', { replace: true, state: { notice: 'Tu perfil se actualizó correctamente.' } })
    } catch (requestError) {
      setError(mensajeDeError(requestError))
    } finally {
      setSaving(false)
    }
  }

  return (
    <section>
      <h1>Editar perfil</h1>
      <p>Actualiza tu nombre o correo electrónico.</p>
      {error && <p role="alert">{error}</p>}
      <form onSubmit={handleSubmit}>
        <p>
          <label htmlFor="NombrePerfil">Nombre<br />
            <input id="NombrePerfil" name="Nombre" value={form.Nombre} onChange={handleChange} maxLength="100" required />
          </label>
        </p>
        <p>
          <label htmlFor="EmailPerfil">Correo electrónico<br />
            <input id="EmailPerfil" name="Email" type="email" value={form.Email} onChange={handleChange} maxLength="150" required />
          </label>
        </p>
        <button type="submit" disabled={saving}>{saving ? 'Guardando…' : 'Guardar cambios'}</button>{' '}
        <Link to="/perfil">Cancelar</Link>
      </form>
    </section>
  )
}
