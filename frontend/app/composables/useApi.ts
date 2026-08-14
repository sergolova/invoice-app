import type { UseFetchOptions } from 'nuxt/app'
import type { NitroFetchOptions } from 'nitropack'

// To read data when the page loads (SSR, GET)
export const useApi = <T>(url: string | (() => string), options: UseFetchOptions<T> = {}) => {
  const config = useRuntimeConfig()
  const baseURL = computed(() => import.meta.server ? config.apiBaseInternal : config.public.apiBase)

  return useFetch<T>(url, { baseURL, ...(options as Record<string, any>) })
}

// To submit forms and trigger actions on click (PUT, POST, DELETE)
export const $api = <T>(url: string, options: NitroFetchOptions<string> = {}) => {
  const config = useRuntimeConfig()
  const baseURL = import.meta.server ? config.apiBaseInternal : config.public.apiBase

  return $fetch<T>(url, { baseURL, ...options })
}