import api from '../api/client.js'

export async function iniciarSesion(credentials) {
  const { data } = await api.post('/auth/login', credentials)
  return data
}

export async function registrarUsuario(details) {
  const { data } = await api.post('/auth/register', details)
  return data
}

export async function obtenerSesion() {
  const { data } = await api.get('/auth/me')
  return data.usuario
}

export async function actualizarPerfil(datos) {
  const { data } = await api.put('/auth/profile', datos)
  return data
}

export async function cerrarSesion() {
  const { data } = await api.post('/auth/logout')
  return data
}
