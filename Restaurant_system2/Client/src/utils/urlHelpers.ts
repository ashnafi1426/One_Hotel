// Centralized API/storage URL helper
// All components should import from here instead of hardcoding localhost

const BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'

/**
 * Convert a relative storage path to an absolute URL using the configured backend
 * e.g. "images/photo.jpg" → "https://one-hotel-backend.onrender.com/storage/images/photo.jpg"
 */
export function storageUrl(path: string | null | undefined): string {
  if (!path || path === 'null' || path === 'undefined') return ''
  const sanitized = String(path).replace(/\\/g, '/').trim()
  if (!sanitized) return ''
  if (
    sanitized.startsWith('http://') ||
    sanitized.startsWith('https://') ||
    sanitized.startsWith('data:')
  ) {
    return sanitized
  }
  const cleanPath = sanitized.replace(/^\/+/, '')
  const base = BASE_URL.replace(/\/+$/, '')
  if (cleanPath.startsWith('storage/')) {
    return `${base}/${cleanPath}`
  }
  return `${base}/storage/${cleanPath}`
}

/**
 * Get the API base URL (without /api suffix)
 */
export const apiBaseUrl = BASE_URL

/**
 * Get the API base URL (with /api suffix)
 */
export const apiUrl = BASE_URL.endsWith('/api') ? BASE_URL : `${BASE_URL}/api`
