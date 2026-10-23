import { createApp } from 'vue'
import { createPinia, setActivePinia } from 'pinia'
import { useThemeStore } from './stores/theme'
import { useLanguageStore } from './stores/language'
import './assets/main.css'
import './styles/dark-mode.css'
import App from './App.vue'
import router from './router'

const initialLoader = document.getElementById('initial-loader')

const app = createApp(App)
const pinia = createPinia()

setActivePinia(pinia)
app.use(pinia)
app.use(router)

const themeStore = useThemeStore(pinia)
themeStore.initTheme()

const languageStore = useLanguageStore(pinia)
app.config.globalProperties.$t = (key: string, fallback?: string) => languageStore.t(key, fallback)
app.config.globalProperties.t = (key: string, fallback?: string) => languageStore.t(key, fallback)

router.isReady().then(() => {
  app.mount('#app')

  if (initialLoader) {
    initialLoader.style.opacity = '0'
    setTimeout(() => {
      initialLoader.remove()
    }, 200)
  }

  // Defer WebSocket plugin so it doesn't contend with initial page render & LCP
  if (typeof window !== 'undefined') {
    if ('requestIdleCallback' in window) {
      window.requestIdleCallback(() => {
        import('./plugins/echo')
      })
    } else {
      setTimeout(() => {
        import('./plugins/echo')
      }, 300)
    }
  }
})
