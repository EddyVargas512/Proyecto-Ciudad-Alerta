import { useCallback, useEffect, useMemo, useState } from 'react'
import { listarDependencias, listarIncidenciasAdmin, listarTecnicos, cambiarEstado, asignarTecnico, crearTecnico, eliminarIncidencia, mensajeDeError } from '../services/incidencias.js'
import StatusBadge from '../components/StatusBadge.jsx'

const STATUSES = ['Pendiente', 'En proceso', 'Resuelta']

function priorityClass(value) {
  const name = (value || '').toLowerCase()
  if (name.includes('alta')) return 'priority-high'
  if (name.includes('media')) return 'priority-medium'
  return 'priority-low'
}

export default function AdminPage() {
  const [incidencias, setIncidencias] = useState([])
  const [tecnicos, setTecnicos] = useState([])
  const [dependencias, setDependencias] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('Todas')
  const [priorityFilter, setPriorityFilter] = useState('Todas')
  const [savingKey, setSavingKey] = useState('')
  const [showTechnicianForm, setShowTechnicianForm] = useState(false)
  const [technicianForm, setTechnicianForm] = useState({ Nombre: '', Email: '', IdDependencia: '' })
  const [lightbox, setLightbox] = useState(null)

  const cargarDatos = useCallback(async () => {
    setError('')
    try {
      // Carga en paralelo lo necesario para revisar reportes y asignar técnicos.
      const [reports, staff, departments] = await Promise.all([listarIncidenciasAdmin(), listarTecnicos(), listarDependencias()])
      setIncidencias(reports)
      setTecnicos(staff)
      setDependencias(departments)
      setTechnicianForm(current => ({ ...current, IdDependencia: current.IdDependencia || departments[0]?.IdDependencia || '' }))
    } catch (requestError) {
      setError(mensajeDeError(requestError))
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => { void Promise.resolve().then(cargarDatos) }, [cargarDatos])

  useEffect(() => {
    if (!lightbox) return undefined

    const closeWithEscape = event => {
      if (event.key === 'Escape') setLightbox(null)
    }
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    window.addEventListener('keydown', closeWithEscape)

    return () => {
      document.body.style.overflow = previousOverflow
      window.removeEventListener('keydown', closeWithEscape)
    }
  }, [lightbox])

  const filtradas = useMemo(() => {
    const term = search.trim().toLocaleLowerCase('es')
    return incidencias.filter(item => {
      const matchesStatus = statusFilter === 'Todas' || item.Estado === statusFilter
      const matchesPriority = priorityFilter === 'Todas' || item.PrioridadCalculada === priorityFilter
      const matchesSearch = !term || [item.Titulo, item.Descripcion, item.UbicacionTexto, item.usuario?.Nombre].some(value => value?.toLocaleLowerCase('es').includes(term))
      return matchesStatus && matchesPriority && matchesSearch
    }).sort((a, b) => (b.PuntajePrioridad ?? 0) - (a.PuntajePrioridad ?? 0))
  }, [incidencias, search, statusFilter, priorityFilter])
  const pendingCount = incidencias.filter(item => item.Estado === 'Pendiente').length
  const processCount = incidencias.filter(item => item.Estado === 'En proceso').length
  const priorityCount = incidencias.filter(item => item.PrioridadCalculada === 'Alta').length

  async function handleStatusChange(item, nextStatus) {
    const key = `status-${item.IdIncidencia}`
    setSavingKey(key)
    setError('')
    setNotice('')
    try {
      await cambiarEstado(item.IdIncidencia, nextStatus)
      setIncidencias(current => current.map(row => row.IdIncidencia === item.IdIncidencia ? { ...row, Estado: nextStatus } : row))
      setNotice(`El estado del reporte “${item.Titulo}” cambió a ${nextStatus}.`)
    } catch (requestError) {
      setError(mensajeDeError(requestError))
    } finally {
      setSavingKey('')
    }
  }

  async function handleAssign(item, technicianId) {
    if (!technicianId) return
    const key = `assign-${item.IdIncidencia}`
    setSavingKey(key)
    setError('')
    setNotice('')
    try {
      await asignarTecnico(item.IdIncidencia, technicianId)
      const assigned = tecnicos.find(technician => String(technician.IdTecnico) === String(technicianId))
      setIncidencias(current => current.map(row => row.IdIncidencia === item.IdIncidencia ? {
        ...row,
        Estado: row.Estado === 'Pendiente' ? 'En proceso' : row.Estado,
        AsignacionActual: { IdTecnico: Number(technicianId), Tecnico: assigned, FechaAsignacion: new Date().toISOString() },
      } : row))
      setNotice(`Se asignó ${assigned?.Nombre || 'el técnico'} al reporte “${item.Titulo}”.`)
    } catch (requestError) {
      setError(mensajeDeError(requestError))
    } finally {
      setSavingKey('')
    }
  }

  async function handleDelete(item) {
    // La confirmación evita borrados accidentales; Laravel borra también los datos relacionados.
    const confirmed = window.confirm(`¿Seguro que deseas eliminar la incidencia “${item.Titulo}”? También se borrarán sus fotos, apoyos, asignaciones e historial. Esta acción no se puede deshacer.`)
    if (!confirmed) return

    const key = `delete-${item.IdIncidencia}`
    setSavingKey(key)
    setError('')
    setNotice('')
    try {
      await eliminarIncidencia(item.IdIncidencia)
      setIncidencias(current => current.filter(row => row.IdIncidencia !== item.IdIncidencia))
      setNotice(`La incidencia “${item.Titulo}” fue eliminada.`)
    } catch (requestError) {
      setError(mensajeDeError(requestError))
    } finally {
      setSavingKey('')
    }
  }

  async function handleCreateTechnician(event) {
    event.preventDefault()
    setSavingKey('create-technician')
    setError('')
    try {
      const newTechnician = await crearTecnico({ ...technicianForm, IdDependencia: Number(technicianForm.IdDependencia) })
      setTecnicos(current => [...current, newTechnician])
      setTechnicianForm(current => ({ ...current, Nombre: '', Email: '' }))
      setNotice(`Se agregó a ${newTechnician.Nombre} como técnico disponible.`)
      setShowTechnicianForm(false)
    } catch (requestError) {
      setError(mensajeDeError(requestError))
    } finally {
      setSavingKey('')
    }
  }

  return (
    <>
      <section className="hero-row admin-hero">
        <div><p className="eyebrow">Panel de control</p><h1>Administración de incidencias</h1><p className="page-subtitle">Revisa los reportes, asigna responsables y actualiza el seguimiento.</p></div>
        <button className="button button-secondary" onClick={() => { setLoading(true); void cargarDatos() }} disabled={loading}> Actualizar</button>
      </section>

      {notice && <div className="notice"><span>✓</span>{notice}</div>}
      {error && <div className="notice error" role="alert">{error}</div>}

      <section className="stats-grid admin-stats" aria-label="Resumen de administración">
        <div className="stat-card"><span className="stat-icon"></span><div><p className="stat-label">Incidencias</p><div className="stat-value">{incidencias.length}</div></div></div>
        <div className="stat-card"><span className="stat-icon amber"></span><div><p className="stat-label">Por revisar</p><div className="stat-value">{pendingCount}</div></div></div>
        <div className="stat-card"><span className="stat-icon blue"></span><div><p className="stat-label">En proceso</p><div className="stat-value">{processCount}</div></div></div>
        <div className="stat-card"><span className="stat-icon red"></span><div><p className="stat-label">Prioridad alta</p><div className="stat-value">{priorityCount}</div></div></div>
      </section>

      <section className="admin-staff-panel">
        <div><h2>Equipo técnico</h2><p>{tecnicos.length ? `${tecnicos.length} técnicos disponibles para asignación.` : 'Aún no hay técnicos registrados para asignar.'}</p></div>
        <button className="button button-secondary" onClick={() => setShowTechnicianForm(value => !value)}><span>{showTechnicianForm ? 'Cerrar' : 'Agregar técnico'}</span></button>
      </section>
      {showTechnicianForm && <form className="technician-form" onSubmit={handleCreateTechnician}>
        <div className="field"><label htmlFor="NombreTecnico">Nombre</label><input id="NombreTecnico" name="Nombre" value={technicianForm.Nombre} onChange={event => setTechnicianForm(current => ({ ...current, Nombre: event.target.value }))} maxLength="150" required/></div>
        <div className="field"><label htmlFor="EmailTecnico">Correo</label><input id="EmailTecnico" name="Email" type="email" value={technicianForm.Email} onChange={event => setTechnicianForm(current => ({ ...current, Email: event.target.value }))} maxLength="150" required/></div>
        <div className="field"><label htmlFor="IdDependencia">Dependencia</label><select id="IdDependencia" value={technicianForm.IdDependencia} onChange={event => setTechnicianForm(current => ({ ...current, IdDependencia: event.target.value }))} required>{dependencias.map(item => <option key={item.IdDependencia} value={item.IdDependencia}>{item.NombreDependencia}</option>)}</select></div>
        <button className="button" type="submit" disabled={savingKey === 'create-technician' || !dependencias.length}> Guardar técnico</button>
        {!dependencias.length && <p className="field-hint technician-hint">No hay dependencias. Crea una en la tabla de dependencias antes de agregar técnicos.</p>}
      </form>}

      <section className="panel admin-reports-panel">
        <div className="panel-toolbar admin-toolbar">
          <h2 className="panel-title">Todos los reportes <span className="panel-count">{loading ? 'Cargando…' : filtradas.length}</span></h2>
          <div className="filters admin-filters">
            <label className="search-wrap"><input className="search-input" type="search" placeholder="Buscar reporte o autor…" value={search} onChange={event => setSearch(event.target.value)} aria-label="Buscar incidencias"/></label>
            <select className="filter-select" value={statusFilter} onChange={event => setStatusFilter(event.target.value)} aria-label="Filtrar por estado"><option>Todas</option>{STATUSES.map(status => <option key={status}>{status}</option>)}</select>
            <select className="filter-select" value={priorityFilter} onChange={event => setPriorityFilter(event.target.value)} aria-label="Filtrar por prioridad"><option>Todas</option><option>Baja</option><option>Media</option><option>Alta</option></select>
          </div>
        </div>

        {loading ? <div className="loading-state"><span className="spinner"/>Cargando reportes…</div> : filtradas.length === 0 ? <div className="empty-state"><span className="empty-illustration"></span><h2>No hay incidencias para mostrar</h2><p>Los reportes nuevos aparecerán aquí para que puedas revisarlos.</p></div> : (
          <div className="incident-list">
            {filtradas.map(item => {
              const statusKey = `status-${item.IdIncidencia}`
              const assignKey = `assign-${item.IdIncidencia}`
              return <article className="admin-incident-card" key={item.IdIncidencia}>
                <div className="admin-incident-heading">
                  <div><p className="admin-record-id">Reporte #{item.IdIncidencia} · {item.usuario?.Nombre || `Usuario ${item.IdUsuario}`}</p><div className="incident-topline"><h3 className="incident-title">{item.Titulo}</h3><StatusBadge status={item.Estado}/><span className={`priority-badge ${priorityClass(item.PrioridadCalculada)}`}>{item.PrioridadCalculada || 'Baja'} prioridad</span></div></div>
                  <span className="meta-item">{new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(item.created_at))}</span>
                </div>
                <p className="incident-description admin-description">{item.Descripcion}</p>
                <div className="incident-meta">
                  {item.UbicacionTexto && <span className="meta-item">{item.UbicacionTexto}</span>}
                  <span className="meta-item">{item.ReportesCercanos ?? 1} reportes en 100 m</span>
                  <span className="meta-item">{item.CantidadApoyos ?? 0} apoyos</span>
                </div>
                {item.evidencias?.length > 0 && <div className="evidence-strip">{item.evidencias.map(photo => <button className="evidence-thumb" key={photo.IdEvidencia} type="button" onClick={() => setLightbox({ url: photo.UrlArchivo, title: item.Titulo })} aria-label={`Ampliar evidencia de ${item.Titulo}`}><img src={photo.UrlArchivo} alt="Evidencia fotográfica" loading="lazy"/></button>)}</div>}
                <div className="admin-controls">
                  <label className="admin-control"><span>Estado del reporte</span><select value={item.Estado} disabled={savingKey === statusKey} onChange={event => handleStatusChange(item, event.target.value)}>{STATUSES.map(status => <option key={status}>{status}</option>)}</select></label>
                  <label className="admin-control"><span>Técnico asignado</span><select value={item.AsignacionActual?.IdTecnico ?? ''} disabled={!tecnicos.length || savingKey === assignKey || item.Estado === 'Resuelta'} onChange={event => handleAssign(item, event.target.value)}><option value="">{tecnicos.length ? 'Sin asignar' : 'Agrega un técnico primero'}</option>{tecnicos.map(technician => <option key={technician.IdTecnico} value={technician.IdTecnico}>{technician.Nombre}</option>)}</select></label>
                  <span className="admin-helper">Al asignar un técnico, el reporte pasa a “En proceso”.</span>
                  <button type="button" className="admin-delete-button" onClick={() => handleDelete(item)} disabled={savingKey === `delete-${item.IdIncidencia}`}>{savingKey === `delete-${item.IdIncidencia}` ? 'Eliminando…' : 'Eliminar incidencia'}</button>
                </div>
              </article>
            })}
          </div>
        )}
      </section>
      <p className="footer-note">Los cambios de estado quedan registrados en el historial de la incidencia.</p>
      {lightbox && (
        <div className="image-lightbox" onClick={() => setLightbox(null)}>
          <section className="image-lightbox-content" role="dialog" aria-modal="true" aria-label={`Evidencia: ${lightbox.title}`} onClick={event => event.stopPropagation()}>
            <button className="image-lightbox-close" type="button" onClick={() => setLightbox(null)} aria-label="Cerrar imagen">×</button>
            <img src={lightbox.url} alt={`Evidencia ampliada de ${lightbox.title}`} />
          </section>
        </div>
      )}
    </>
  )
}
