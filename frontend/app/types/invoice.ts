export type InvoiceStatus = 'pending' | 'approved' | 'rejected'

export interface Invoice {
  id: string
  number: string
  supplier_name: string
  supplier_tax_id: string
  net_amount: string | number
  vat_amount: string | number
  gross_amount: string | number
  currency: string
  status: InvoiceStatus
  issue_date: string
  due_date: string
  created_at: string
  updated_at: string
}

export interface UpdateInvoiceDTO {
  net_amount: number
  vat_amount: number
  gross_amount: number
  due_date: string
}