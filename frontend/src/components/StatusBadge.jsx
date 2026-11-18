export default function StatusBadge({ status }) {
  const normalized = (status || '').toLowerCase()
  let style = 'badge-default'

  if (normalized.includes('pend')) {
    style = 'badge-pendiente'
  } else if (normalized.includes('proceso') || normalized.includes('asign')) {
    style = 'badge-en-proceso'
  } else if (normalized.includes('resuel') || normalized.includes('cerrad')) {
    style = 'badge-resuelta'
  }

  return <span className={`badge ${style}`}>{status || 'Sin estado'}</span>
}
