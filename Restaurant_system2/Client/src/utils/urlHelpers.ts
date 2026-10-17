// Centralized API/storage URL helper
// All components should import from here instead of hardcoding localhost

const BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'

/**
 * Convert a relative storage path to an absolute URL using the configured backend
 * e.g. "images/photo.jpg" → "https://one-hotel-backend.onrender.com/storage/images/photo.jpg"
 */
export function storageUrl(path: string | null | undefined): string {
  if (!path) return ''
  const trimmed = path.replace(/^\/+/, '')
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
    return trimmed
  }
  if (trimmed.startsWith('storage/')) {
    return `${BASE_URL}/${trimmed}`
  }
  return `${BASE_URL}/storage/${trimmed}`
}

/**
 * Get the API base URL (without /api suffix)
 */
export const apiBaseUrl = BASE_URL

/**
 * Get the API base URL (with /api suffix)
 */
export const apiUrl = BASE_URL.endsWith('/api') ? BASE_URL : `${BASE_URL}/api`
