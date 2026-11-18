import { useCallback, useEffect, useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import OpenStreetMapPicker from '../components/OpenStreetMapPicker.jsx'
import { useAuth } from '../context/AuthContext.jsx'
import { crearIncidencia, listarCatalogo, mensajeDeError } from '../services/incidencias.js'

const catalogDefinitions = [
  {
    field: 'IdCategoria',
    resource: 'categorias',
    label: 'Categoría',
    allowedNames: ['1 - Máxima prioridad', '2 - Alta prioridad', '3 - Prioridad media', '4 - Baja prioridad', '5 - Mínima prioridad'],
    name: item => item.NombreCategoria,
  },
  { field: 'IdZona', resource: 'zonas', label: 'Zona', allowedNames: ['Rural', 'Urbano'], name: item => item.NombreZona },
]

export default function IncidenciaFormPage() {
  const { user } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState({ Titulo: '', Descripcion: '', UbicacionTexto: '', Latitud: '', Longitud: '', IdCategoria: '', IdZona: '' })
  const photoUrls = useRef(new Set())
  const [options, setOptions] = useState({})
  const [photos, setPhotos] = useState([])
  const [loadingOptions, setLoadingOptions] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    Promise.all(catalogDefinitions.map(async catalog => {
      try {
        const items = await listarCatalogo(catalog.resource)
        return [catalog.field, items.filter(item => catalog.allowedNames.includes(item[catalog.field === 'IdCategoria' ? 'NombreCategoria' : 'NombreZona']))]
      } catch {
        return [catalog.field, []]
      }
    })).then(entries => { if (active) setOptions(Object.fromEntries(entries)) })
      .finally(() => { if (active) setLoadingOptions(false) })

    return () => { active = false }
  }, [])

  useEffect(() => () => photoUrls.current.forEach(url => URL.revokeObjectURL(url)), [])

  const onLocationSelected = useCallback(({ address, lat, lng }) => {
    setForm(current => ({ ...current, UbicacionTexto: address, Latitud: String(lat), Longitud: String(lng) }))
  }, [])

  function handleChange(event) {
    const { name, value } = event.target
    setForm(current => ({ ...current, [name]: value }))
  }

  function handlePhotos(event) {
    const selected = Array.from(event.target.files ?? [])
    const combined = [...photos, ...selected].slice(0, 5)
    const tooLarge = combined.find(item => (item.file ?? item).size > 5 * 1024 * 1024)

    if (tooLarge) {
      setError(`La foto “${(tooLarge.file ?? tooLarge).name}” supera el límite de 5 MB.`)
      return
    }

    setError('')
    setPhotos(combined.map(item => {
      if (item.preview) return item
      const file = item
      const preview = URL.createObjectURL(file)
      photoUrls.current.add(preview)
      return { file, preview }
    }))
    event.target.value = ''
  }

  function removePhoto(index) {
    URL.revokeObjectURL(photos[index].preview)
    photoUrls.current.delete(photos[index].preview)
    setPhotos(current => current.filter((_, currentIndex) => currentIndex !== index))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    if (photos.length === 0) {
      setError('Toma al menos una foto para adjuntar la evidencia del reporte.')
      return
    }

    setSubmitting(true)
    setError('')

    const payload = new FormData()
    payload.append('Titulo', form.Titulo)
    payload.append('Descripcion', form.Descripcion)
    payload.append('UbicacionTexto', form.UbicacionTexto)
    payload.append('Latitud', form.Latitud)
    payload.append('Longitud', form.Longitud)
    payload.append('IdCategoria', form.IdCategoria)
    payload.append('IdZona', form.IdZona)
    photos.forEach(photo => payload.append('Evidencias[]', photo.file))

    try {
      await crearIncidencia(payload)
      navigate('/incidencias', { state: { notice: 'Tu reporte quedó registrado como Pendiente.' } })
    } catch (requestError) {
      setError(mensajeDeError(requestError))
    } finally {
      setSubmitting(false)
    }
  }

  function renderCatalog(catalog) {
    const items = options[catalog.field] ?? []
    return (
      <div className="field" key={catalog.field}>
        <label htmlFor={catalog.field}>{catalog.label} <span>· requerido</span></label>
        {items.length ? (
          <select id={catalog.field} name={catalog.field} value={form[catalog.field]} onChange={handleChange} required>
            <option value="">Selecciona una opción</option>
            {items.map(item => <option key={item[catalog.field]} value={item[catalog.field]}>{catalog.name(item)}</option>)}
          </select>
        ) : (
          <input id={catalog.field} name={catalog.field} type="number" min="1" step="1" value={form[catalog.field]} onChange={handleChange} placeholder="ID del registro" required />
        )}
        {!loadingOptions && !items.length && <p className="field-hint">No hay opciones cargadas. Escribe el ID de un registro existente.</p>}
      </div>
    )
  }

  return (
    <div className="form-wrap">
      <Link className="back-link" to="/incidencias"> Volver a incidencias</Link>
      <section className="hero-row">
        <div><p className="eyebrow">Reporte ciudadano · {user?.Nombre}</p><h1>Nueva incidencia</h1><p className="page-subtitle">Cuéntanos qué sucede. El reporte quedará pendiente para que el equipo lo revise.</p></div>
      </section>
      {error && <div className="notice error form-error" role="alert">{error}</div>}
      <form className="form-card" onSubmit={handleSubmit}>
        <div className="form-card-head"><h2>Información del reporte</h2><p>Tu incidencia se registrará como <strong>Pendiente</strong>. El equipo de administración actualizará su estado.</p></div>
        <div className="form-body">
          <div className="field full"><label htmlFor="Titulo">Título <span>· requerido</span></label><input id="Titulo" name="Titulo" maxLength="150" value={form.Titulo} onChange={handleChange} placeholder="Ej. Alumbrado público fuera de servicio" required /></div>
          <div className="field full"><label htmlFor="Descripcion">Descripción <span>· requerido</span></label><textarea id="Descripcion" name="Descripcion" maxLength="10000" value={form.Descripcion} onChange={handleChange} placeholder="Describe qué ocurre y cualquier detalle que ayude a atender el reporte…" required /></div>
          <div className="field full"><label htmlFor="UbicacionTexto">Dirección o lugar <span>· requerido</span></label><input id="UbicacionTexto" name="UbicacionTexto" maxLength="255" value={form.UbicacionTexto} onChange={handleChange} placeholder="Barrio, calle o punto de referencia" required/><OpenStreetMapPicker onLocationSelected={onLocationSelected}/></div>
          <div className="field"><label htmlFor="Latitud">Latitud <span>· requerido</span></label><input id="Latitud" name="Latitud" type="number" min="-90" max="90" step="any" value={form.Latitud} onChange={handleChange} placeholder="4.7110" required/><p className="field-hint">Se completa al buscar una dirección o marcar el punto en el mapa.</p></div>
          <div className="field"><label htmlFor="Longitud">Longitud <span>· requerido</span></label><input id="Longitud" name="Longitud" type="number" min="-180" max="180" step="any" value={form.Longitud} onChange={handleChange} placeholder="-74.0721" required/><p className="field-hint">Se completa al buscar una dirección o marcar el punto en el mapa.</p></div>
          {catalogDefinitions.map(renderCatalog)}
          <div className="field full">
            <label htmlFor="EvidenciaCamara">Evidencia fotográfica <span>· requerida, máximo 5 fotos</span></label>
            <div className="photo-capture-actions">
              <label className={`photo-upload camera-capture${photos.length >= 5 ? ' photo-upload-disabled' : ''}`} htmlFor="EvidenciaCamara"><span>Tomar una foto <small>Abre la cámara del dispositivo</small></span><input id="EvidenciaCamara" type="file" accept="image/jpeg,image/png,image/webp" capture="environment" disabled={photos.length >= 5} onChange={handlePhotos} /></label>
            </div>
            {photos.length === 0 && <p className="field-hint">Debes tomar al menos una foto para enviar el reporte. Puedes agregar hasta 5.</p>}
            {photos.length > 0 && <div className="photo-previews">{photos.map((photo, index) => <div className="photo-preview" key={`${photo.file.name}-${index}`}><img src={photo.preview} alt={`Vista previa ${index + 1}`} /><button type="button" onClick={() => removePhoto(index)} aria-label={`Quitar ${photo.file.name}`}>×</button></div>)}</div>}
          </div>
        </div>
        <div className="form-actions"><Link className="button button-secondary" to="/incidencias">Cancelar</Link><button className="button" type="submit" disabled={submitting}>{submitting ? 'Enviando reporte…' : <> Enviar reporte</>}</button></div>
      </form>
      <p className="footer-note">Al enviar el reporte, tu nombre se registra automáticamente como autor.</p>
    </div>
  )
}
