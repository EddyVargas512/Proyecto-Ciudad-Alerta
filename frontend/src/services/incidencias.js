import api from '../api/client.js'

export async function listarIncidencias() {
  const { data } = await api.get('/incidencias')
  return Array.isArray(data) ? data : data.data ?? []
}

export async function crearIncidencia(datos) {
  const { data } = await api.post('/incidencias', datos)
  return data
}

export async function apoyarIncidencia(id) {
  const { data } = await api.post(`/incidencias/${id}/apoyo`)
  return data
}

export async function listarCatalogo(recurso) {
  const { data } = await api.get(`/${recurso}`)
  return Array.isArray(data) ? data : data.data ?? []
}

export async function listarIncidenciasAdmin() {
  const { data } = await api.get('/admin/incidencias')
  return Array.isArray(data) ? data : data.data ?? []
}

export async function cambiarEstado(id, Estado) {
  const { data } = await api.patch(`/admin/incidencias/${id}/estado`, { Estado })
  return data
}

export async function asignarTecnico(id, IdTecnico) {
  const { data } = await api.put(`/admin/incidencias/${id}/asignacion`, { IdTecnico })
  return data
}

export async function eliminarIncidencia(id) {
  const { data } = await api.delete(`/admin/incidencias/${id}`)
  return data
}

export async function listarTecnicos() {
  const { data } = await api.get('/admin/tecnicos')
  return Array.isArray(data) ? data : data.data ?? []
}

export async function listarDependencias() {
  const { data } = await api.get('/admin/dependencias')
  return Array.isArray(data) ? data : data.data ?? []
}

export async function crearTecnico(datos) {
  const { data } = await api.post('/admin/tecnicos', datos)
  return data
}

export function mensajeDeError(error) {
  const errores = error.response?.data?.errors
  if (errores) return Object.values(errores).flat().join(' ')
  if (error.code === 'ERR_NETWORK') return 'No se pudo conectar con el servidor. Comprueba que Laravel esté activo.'
  return error.response?.data?.message || 'Ocurrió un error. Intenta de nuevo.'
}
