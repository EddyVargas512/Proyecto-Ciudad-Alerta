import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import StatusBadge from '../components/StatusBadge.jsx'
import { useAuth } from '../context/AuthContext.jsx'
import { apoyarIncidencia, listarIncidencias, mensajeDeError } from '../services/incidencias.js'

function fechaCorta(value) {
  if (!value) return 'Fecha no disponible'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Fecha no disponible' : new Intl.DateTimeFormat('es-CO', { day: 'numeric', month: 'short', year: 'numeric' }).format(date)
}

function prioridadClass(value) {
  const name = (value || '').toLowerCase()
  if (name.includes('alta')) return 'priority-high'
  if (name.includes('media')) return 'priority-medium'
  return 'priority-low'
}

export default function IncidenciasPage() {
  const { user } = useAuth()
  const location = useLocation()
  const [incidencias, setIncidencias] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('Todas')
  const [supportingId, setSupportingId] = useState(null)
  const [lightbox, setLightbox] = useState(null)

  const cargarIncidencias = useCallback(async () => {
    try {
      setIncidencias(await listarIncidencias())
    } catch (requestError) {
      setError(mensajeDeError(requestError))
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => { void Promise.resolve().then(cargarIncidencias) }, [cargarIncidencias])

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

  const statuses = useMemo(() => ['Todas', ...new Set(incidencias.map(item => item.Estado).filter(Boolean))], [incidencias])
  const filtradas = useMemo(() => {
    const term = search.trim().toLocaleLowerCase('es')
    return incidencias.filter(item => {
      const matchesStatus = statusFilter === 'Todas' || item.Estado === statusFilter
      const matchesSearch = !term || [item.Titulo, item.Descripcion, item.UbicacionTexto].some(value => value?.toLocaleLowerCase('es').includes(term))
      return matchesStatus && matchesSearch
    }).sort((a, b) => (b.PuntajePrioridad ?? 0) - (a.PuntajePrioridad ?? 0))
  }, [incidencias, search, statusFilter])
  const pendientes = incidencias.filter(item => item.Estado === 'Pendiente').length
  const seguimiento = incidencias.filter(item => item.Estado === 'En proceso').length
  const aviso = location.state?.notice

  function reintentarCarga() {
    setError('')
    setLoading(true)
    void cargarIncidencias()
  }

  async function handleSupport(item) {
    setSupportingId(item.IdIncidencia)
    setError('')
    try {
      const result = await apoyarIncidencia(item.IdIncidencia)
      setIncidencias(current => current.map(row => row.IdIncidencia === item.IdIncidencia ? { ...row, ...result } : row))
    } catch (requestError) {
      setError(mensajeDeError(requestError))
    } finally {
      setSupportingId(null)
    }
  }

  return (
    <>
      <section className="hero-row">
        <div><p className="eyebrow"> Ciudadanía activa</p><h1>Incidencias de la ciudad</h1><p className="page-subtitle">Revisa los reportes cercanos y apoya los que también afectan a tu comunidad.</p></div>
        <Link className="button" to="/incidencias/nueva"> Reportar incidencia</Link>
      </section>
      {aviso && <div className="notice"><span>✓</span>{aviso}</div>}
      {error && <div className="notice error" role="alert">{error}</div>}

      <section className="stats-grid" aria-label="Resumen de incidencias">
        <div className="stat-card"><span className="stat-icon"></span><div><p className="stat-label">Reportes registrados</p><div className="stat-value">{incidencias.length}</div></div></div>
        <div className="stat-card"><span className="stat-icon amber"></span><div><p className="stat-label">Pendientes</p><div className="stat-value">{pendientes}</div></div></div>
        <div className="stat-card"><span className="stat-icon blue"></span><div><p className="stat-label">En seguimiento</p><div className="stat-value">{seguimiento}</div></div></div>
      </section>

      <section className="panel">
        <div className="panel-toolbar">
          <h2 className="panel-title">Reportes <span className="panel-count">{loading ? 'Cargando…' : `${filtradas.length} de ${incidencias.length}`}</span></h2>
          <div className="filters">
            <label className="search-wrap"><input className="search-input" type="search" placeholder="Buscar reporte…" value={search} onChange={event => setSearch(event.target.value)} aria-label="Buscar incidencias"/></label>
            <select className="filter-select" value={statusFilter} onChange={event => setStatusFilter(event.target.value)} aria-label="Filtrar por estado">{statuses.map(status => <option key={status} value={status}>{status}</option>)}</select>
          </div>
        </div>
        {loading ? <div className="loading-state"><span className="spinner"/>Cargando incidencias…</div> : error && !incidencias.length ? <div className="empty-state"><span className="empty-illustration"></span><h2>No pudimos cargar los reportes</h2><p>{error}</p><button className="button button-secondary" onClick={reintentarCarga}> Intentar de nuevo</button></div> : filtradas.length === 0 ? <div className="empty-state"><span className="empty-illustration"></span><h2>{incidencias.length ? 'No hay resultados' : 'Aún no hay incidencias'}</h2><p>{incidencias.length ? 'Prueba con otro texto o cambia el filtro de estado.' : 'Registra el primer problema que quieras compartir con tu comunidad.'}</p>{!incidencias.length && <Link className="button" to="/incidencias/nueva"> Registrar la primera</Link>}</div> : (
          <div className="incident-list">
            {filtradas.map(item => {
              const isOwnReport = Number(item.IdUsuario) === Number(user?.IdUsuario)
              const isResolved = item.Estado === 'Resuelta'
              return (
                <article className="incident-card citizen-incident" key={item.IdIncidencia}>
                  <div className="incident-main">
                    <div className="incident-topline"><h3 className="incident-title">{item.Titulo}</h3><StatusBadge status={item.Estado}/><span className={`priority-badge ${prioridadClass(item.PrioridadCalculada)}`}>{item.PrioridadCalculada || 'Baja'} prioridad</span></div>
                    <p className="incident-description">{item.Descripcion}</p>
                    <div className="incident-meta">
                      {item.UbicacionTexto && <span className="meta-item">{item.UbicacionTexto}</span>}
                      <span className="meta-item">{fechaCorta(item.created_at)}</span>
                      <span className="meta-item">{item.ReportesCercanos ?? 1} {item.ReportesCercanos === 1 ? 'reporte cercano' : 'reportes cercanos'}</span>
                    </div>
                    {item.evidencias?.length > 0 && <div className="evidence-strip">{item.evidencias.map(photo => <button className="evidence-thumb" key={photo.IdEvidencia} type="button" onClick={() => setLightbox({ url: photo.UrlArchivo, title: item.Titulo })} aria-label={`Ampliar evidencia de ${item.Titulo}`}><img src={photo.UrlArchivo} alt="Evidencia de la incidencia" loading="lazy"/></button>)}</div>}
                  </div>
                  <div className="support-control">
                    <button className={`support-button ${item.ApoyadoPorMi ? 'supported' : ''}`} onClick={() => handleSupport(item)} disabled={isOwnReport || isResolved || supportingId === item.IdIncidencia} aria-pressed={Boolean(item.ApoyadoPorMi)} title={isOwnReport ? 'No puedes apoyar tu propio reporte' : isResolved ? 'El reporte está resuelto' : 'Apoyar este reporte'}>
                      <span>{supportingId === item.IdIncidencia ? 'Guardando…' : item.ApoyadoPorMi ? 'Apoyado' : 'Apoyar'}</span>
                    </button>
                    <strong>{item.CantidadApoyos ?? 0}</strong><small>{item.CantidadApoyos === 1 ? 'apoyo' : 'apoyos'}</small>
                  </div>
                </article>
              )
            })}
          </div>
        )}
      </section>
      <p className="footer-note">Los apoyos y reportes ubicados a menos de 100 m aumentan la prioridad del caso.</p>
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
