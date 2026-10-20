import { storageUrl } from '@/utils/urlHelpers'

/**
 * Clean SVG fallback icons encoded as Data URIs.
 * These load instantaneously, never fail over the network, and look ultra-modern.
 */
function createSvgDataUri(bgColor: string, iconSvg: string): string {
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100">
    <rect width="100" height="100" rx="20" fill="${bgColor}"/>
    <g transform="translate(22, 22)" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
      ${iconSvg}
    </g>
  </svg>`
  return `data:image/svg+xml;utf8,${encodeURIComponent(svg)}`
}

const FALLBACK_SVGS: Record<string, string> = {
  pizza: createSvgDataUri(
    '#ea580c',
    `<path d="M28 2 L54 50 A56 56 0 0 1 2 50 Z" />
     <circle cx="28" cy="24" r="3" fill="#ffffff" />
     <circle cx="20" cy="38" r="3" fill="#ffffff" />
     <circle cx="36" cy="38" r="3" fill="#ffffff" />`
  ),
  burger: createSvgDataUri(
    '#d97706',
    `<path d="M6 22 A22 22 0 0 1 50 22 Z" />
     <rect x="4" y="26" width="48" height="6" rx="3" fill="#ffffff" />
     <path d="M4 36 L12 40 L20 36 L28 40 L36 36 L44 40 L52 36" />
     <path d="M6 44 H50 A6 6 0 0 1 44 54 H12 A6 6 0 0 1 6 44 Z" />`
  ),
  salad: createSvgDataUri(
    '#16a34a',
    `<path d="M6 28 A22 22 0 0 0 50 28 Z" />
     <path d="M14 24 Q28 6 42 24" />
     <circle cx="28" cy="18" r="4" fill="#ffffff" />
     <circle cx="18" cy="22" r="3" fill="#ffffff" />
     <circle cx="38" cy="22" r="3" fill="#ffffff" />`
  ),
  pasta: createSvgDataUri(
    '#eab308',
    `<path d="M8 30 A20 20 0 0 0 48 30 Z" />
     <path d="M16 26 Q22 14 28 26 T40 26" />
     <path d="M12 22 Q24 10 36 22" />
     <path d="M28 4 L28 14" />`
  ),
  steak: createSvgDataUri(
    '#dc2626',
    `<path d="M10 20 C6 34 16 50 36 50 C48 50 54 40 50 26 C46 12 26 8 10 20 Z" />
     <ellipse cx="28" cy="28" rx="6" ry="4" fill="#ffffff" opacity="0.4" />`
  ),
  fries: createSvgDataUri(
    '#f59e0b',
    `<path d="M12 26 L16 52 H40 L44 26 Z" />
     <rect x="18" y="10" width="5" height="18" rx="2" fill="#ffffff" />
     <rect x="26" y="6" width="5" height="22" rx="2" fill="#ffffff" />
     <rect x="34" y="12" width="5" height="16" rx="2" fill="#ffffff" />`
  ),
  drink: createSvgDataUri(
    '#0284c7',
    `<path d="M12 12 L18 52 H38 L44 12 Z" />
     <path d="M8 12 H48" />
     <path d="M28 4 L38 2" />
     <line x1="28" y1="4" x2="28" y2="30" />`
  ),
  dessert: createSvgDataUri(
    '#ec4899',
    `<path d="M8 44 L48 44 L48 32 L8 32 Z" />
     <path d="M14 32 L14 22 L42 22 L42 32" />
     <circle cx="28" cy="14" r="5" fill="#ffffff" />`
  ),
  default: createSvgDataUri(
    '#4f46e5',
    `<circle cx="28" cy="28" r="22" />
     <path d="M18 28 H38" />
     <path d="M28 18 V38" />`
  ),
}

/**
 * High-quality curated Unsplash food photos as first-line fallbacks
 */
const CURATED_FOOD_PHOTOS: Record<string, string> = {
  burger: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=160&auto=format&fit=crop&q=80',
  salad: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=160&auto=format&fit=crop&q=80',
  pizza: 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=160&auto=format&fit=crop&q=80',
  pasta: 'https://images.unsplash.com/photo-1621996346565-e3d5d6281228?w=160&auto=format&fit=crop&q=80',
  fries: 'https://images.unsplash.com/photo-1576107232684-1279f3908594?w=160&auto=format&fit=crop&q=80',
  steak: 'https://images.unsplash.com/photo-1600891964599-f61ba0e24092?w=160&auto=format&fit=crop&q=80',
  chicken: 'https://images.unsplash.com/photo-1532550907401-a500c9a57435?w=160&auto=format&fit=crop&q=80',
  cake: 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=160&auto=format&fit=crop&q=80',
  juice: 'https://images.unsplash.com/photo-1613478223719-2ab802602423?w=160&auto=format&fit=crop&q=80',
  salmon: 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=160&auto=format&fit=crop&q=80',
  rice: 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=160&auto=format&fit=crop&q=80',
}

export function detectFoodCategory(name: string = ''): string {
  const n = name.toLowerCase()
  if (n.includes('burger') || n.includes('beef') || n.includes('sandwich')) return 'burger'
  if (n.includes('pizza') || n.includes('calzone') || n.includes('margherita')) return 'pizza'
  if (n.includes('salad') || n.includes('caesar') || n.includes('greens')) return 'salad'
  if (n.includes('pasta') || n.includes('spaghetti') || n.includes('alfredo') || n.includes('penne')) return 'pasta'
  if (n.includes('steak') || n.includes('meat') || n.includes('ribeye') || n.includes('beef')) return 'steak'
  if (n.includes('fries') || n.includes('chips') || n.includes('potato')) return 'fries'
  if (n.includes('chicken') || n.includes('wings') || n.includes('poultry')) return 'chicken'
  if (n.includes('cake') || n.includes('dessert') || n.includes('chocolate') || n.includes('ice cream')) return 'dessert'
  if (n.includes('juice') || n.includes('drink') || n.includes('beverage') || n.includes('coffee') || n.includes('tea')) return 'drink'
  if (n.includes('salmon') || n.includes('fish') || n.includes('seafood')) return 'salmon'
  return 'default'
}

/**
 * Resolves an item image URL accurately from database storage path or fallback.
 */
export function resolveFoodImageUrl(imagePath?: string | null, itemName: string = ''): string {
  if (imagePath && typeof imagePath === 'string') {
    const trimmed = imagePath.trim()
    if (trimmed && trimmed !== 'null' && trimmed !== 'undefined') {
      if (trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('data:')) {
        return trimmed
      }
      return storageUrl(trimmed)
    }
  }

  const category = detectFoodCategory(itemName)
  return CURATED_FOOD_PHOTOS[category] || FALLBACK_SVGS[category] || FALLBACK_SVGS.default
}

/**
 * Event handler for img @error: gracefully swaps to zero-fail SVG.
 */
export function onFoodImageError(event: Event, itemName: string = ''): void {
  const img = event.target as HTMLImageElement
  if (!img) return
  img.onerror = null // Prevent infinite loop
  const category = detectFoodCategory(itemName)
  img.src = FALLBACK_SVGS[category] || FALLBACK_SVGS.default
}
