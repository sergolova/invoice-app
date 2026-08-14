import type { InvoiceStatus } from '~/types/invoice'

/**
 * Formats a calendar date (without a time). Used for: issue_date, due_date
 * Example: "August 14, 2026"
 */
export const formatDate = (dateStr?: string | null): string => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('uk-UA', {
    timeZone: 'UTC'
  });
};

/**
 * Formats a date and time. Used for: created_at, updated_at
 * Example: "August 14, 2026, 1:18 PM"
 */
export const formatDateTime = (dateStr?: string | null): string => {
  if (!dateStr) return '-';

  return new Date(dateStr).toLocaleString('uk-UA', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    timeZone: 'Europe/Kyiv', // Хардкодинг в рамках тестового завдання
  });
};

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