import { Link, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'

export default function ProfilePage() {
  const { user } = useAuth()
  const location = useLocation()

  return (
    <section>
      <h1>Mi perfil</h1>
      {location.state?.notice && <p role="status">{location.state.notice}</p>}
      <p>Consulta la información de tu cuenta.</p>
      <dl>
        <dt>Nombre</dt>
        <dd>{user?.Nombre}</dd>
        <dt>Correo electrónico</dt>
        <dd>{user?.Email}</dd>
        <dt>Tipo de cuenta</dt>
        <dd>{user?.TipoUsuario}</dd>
      </dl>
      <Link to="/perfil/editar">Editar perfil</Link>
    </section>
  )
}
