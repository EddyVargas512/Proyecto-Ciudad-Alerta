import { useEffect, useRef, useState } from 'react'
import 'leaflet/dist/leaflet.css'

const BOGOTA = [4.711, -74.0721]
const NOMINATIM = 'https://nominatim.openstreetmap.org'

function wait(milliseconds) {
  return new Promise(resolve => window.setTimeout(resolve, milliseconds))
}

export default function OpenStreetMapPicker({ onLocationSelected }) {
  const mapHost = useRef(null)
  const mapRef = useRef(null)
  const markerRef = useRef(null)
  const putMarkerRef = useRef(null)
  const onSelectRef = useRef(onLocationSelected)
  const lastRequestAt = useRef(0)
  const busyRef = useRef(false)
  const [search, setSearch] = useState('')
  const [results, setResults] = useState([])
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState('Busca una dirección o selecciona un punto directamente en el mapa.')

  useEffect(() => { onSelectRef.current = onLocationSelected }, [onLocationSelected])

  useEffect(() => {
    if (!mapHost.current || mapRef.current) return undefined

    let active = true
    let map

    // Leaflet se carga solo cuando se muestra este componente para reducir la carga inicial.
    async function initializeMap() {
      const leaflet = (await import('leaflet')).default
      if (!active || !mapHost.current) return

      map = leaflet.map(mapHost.current, { scrollWheelZoom: true }).setView(BOGOTA, 13)
      leaflet.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noreferrer">OpenStreetMap contributors</a>',
      }).addTo(map)

      function putMarker(lat, lng) {
        const point = [lat, lng]
        if (markerRef.current) markerRef.current.setLatLng(point)
        else {
          markerRef.current = leaflet.circleMarker(point, {
            radius: 9,
            color: '#ffffff',
            weight: 3,
            fillColor: '#16845b',
            fillOpacity: 1,
          }).addTo(map)
        }
        map.setView(point, Math.max(map.getZoom(), 16))
      }

      async function readJson(url) {
        const delay = 1100 - (Date.now() - lastRequestAt.current)
        if (delay > 0) await wait(delay)
        lastRequestAt.current = Date.now()
        const response = await fetch(url, { headers: { 'Accept-Language': 'es-CO,es;q=0.9' } })
        if (!response.ok) throw new Error('geocoding-failed')
        return response.json()
      }

      async function reverseLookup(lat, lng) {
        if (busyRef.current) return
        busyRef.current = true
        setBusy(true)
        // Las coordenadas se guardan primero; la consulta de dirección puede tardar o fallar.
        const fallbackAddress = `Ubicación seleccionada (${lat.toFixed(6)}, ${lng.toFixed(6)})`
        onSelectRef.current({ address: fallbackAddress, lat, lng })
        setMessage('Punto seleccionado. Buscando la dirección…')

        try {
          const params = new URLSearchParams({ format: 'jsonv2', lat: String(lat), lon: String(lng), zoom: '18', addressdetails: '1' })
          const place = await readJson(`${NOMINATIM}/reverse?${params}`)
          const address = (place.display_name || fallbackAddress).slice(0, 255)
          onSelectRef.current({ address, lat, lng })
          setMessage(`Ubicación seleccionada: ${address}`)
        } catch {
          setMessage('Se guardaron las coordenadas. No se pudo recuperar la dirección; puedes escribirla en el campo de arriba.')
        } finally {
          busyRef.current = false
          setBusy(false)
        }
      }

      map.on('click', event => {
        const { lat, lng } = event.latlng
        putMarker(lat, lng)
        void reverseLookup(lat, lng)
      })

      mapRef.current = map
      putMarkerRef.current = putMarker
    }

    void initializeMap()

    return () => {
      active = false
      map?.remove()
      mapRef.current = null
      markerRef.current = null
      putMarkerRef.current = null
    }
  }, [])

  async function searchAddress() {
    const query = search.trim()
    if (!query || busyRef.current) return

    busyRef.current = true
    setBusy(true)
    setResults([])
    setMessage('Buscando dirección…')

    try {
      // Nominatim busca lugares en OpenStreetMap; esta petición solo ocurre al pulsar Buscar.
      const params = new URLSearchParams({ format: 'jsonv2', q: query, countrycodes: 'co', limit: '5', addressdetails: '1' })
      const places = await (async () => {
        const delay = 1100 - (Date.now() - lastRequestAt.current)
        if (delay > 0) await wait(delay)
        lastRequestAt.current = Date.now()
        const response = await fetch(`${NOMINATIM}/search?${params}`, { headers: { 'Accept-Language': 'es-CO,es;q=0.9' } })
        if (!response.ok) throw new Error('geocoding-failed')
        return response.json()
      })()

      if (!places.length) {
        setMessage('No encontramos esa dirección en Colombia. Prueba con otro nombre o selecciona el punto en el mapa.')
      } else {
        setResults(places)
        setMessage(`Encontramos ${places.length} ${places.length === 1 ? 'opción' : 'opciones'}. Selecciona la ubicación correcta.`)
      }
    } catch {
      setMessage('No se pudo buscar la dirección. Revisa tu conexión o selecciona el punto directamente en el mapa.')
    } finally {
      busyRef.current = false
      setBusy(false)
    }
  }

  function selectPlace(place) {
    const lat = Number(place.lat)
    const lng = Number(place.lon)
    const address = place.display_name.slice(0, 255)
    putMarkerRef.current?.(lat, lng)
    onSelectRef.current({ address, lat, lng })
    setSearch(address)
    setResults([])
    setMessage(`Ubicación seleccionada: ${address}`)
  }

  return (
    <div className="osm-location-picker">
      <div className="osm-search" role="search">
        <input
          aria-label="Buscar dirección en OpenStreetMap"
          value={search}
          onChange={event => setSearch(event.target.value)}
          onKeyDown={event => {
            if (event.key === 'Enter') {
              event.preventDefault()
              void searchAddress()
            }
          }}
          placeholder="Ej. Parque Simón Bolívar, Bogotá"
          maxLength="200"
        />
        <button className="button" type="button" onClick={() => void searchAddress()} disabled={busy || !search.trim()}>{busy ? 'Buscando…' : 'Buscar'}</button>
      </div>
      {results.length > 0 && (
        <ul className="osm-search-results" aria-label="Resultados de búsqueda">
          {results.map(place => (
            <li key={place.place_id}>
              <button type="button" onClick={() => selectPlace(place)}>{place.display_name}</button>
            </li>
          ))}
        </ul>
      )}
      <div ref={mapHost} className="osm-map-canvas" aria-label="Mapa OpenStreetMap: haz clic para marcar la ubicación de la incidencia" />
      <p className="field-hint osm-map-message" role="status">{message}</p>
      <p className="field-hint osm-attribution-note">Mapa de OpenStreetMap. La búsqueda se realiza al pulsar el botón y se limita a Colombia.</p>
    </div>
  )
}
