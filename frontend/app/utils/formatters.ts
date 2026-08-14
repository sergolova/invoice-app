import type { InvoiceStatus } from '~/types/invoice'

export const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('uk-UA', { timeZone: 'UTC' })
}

export const getStatusBadgeClass = (status?: InvoiceStatus | string): string => {
  switch (status) {
    case 'approved':
      return 'bg-green-100 text-green-800'
    case 'rejected':
      return 'bg-red-100 text-red-800'
    case 'pending':
    default:
      return 'bg-yellow-100 text-yellow-800'
  }
}


export const getStatusLabel = (status?: InvoiceStatus | string): string => {
  switch (status) {
    case 'approved':
      return 'Схвалено'
    case 'rejected':
      return 'Вiдхилено'
    case 'pending':
    default:
      return 'Очiкує'
  }
}