import type { UseFetchOptions } from 'nuxt/app'

export const useApi = <T>(
  url: string | (() => string),
  options: UseFetchOptions<T> = {}
) => {
  const config = useRuntimeConfig()

  const baseURL = computed(() => import.meta.server ? config.apiBaseInternal : config.public.apiBase)

  return useFetch<T>(url, {
    baseURL,
    ...(options as Record<string, any>),
  })
}