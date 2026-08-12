<template>
  <div class="max-w-4xl mx-auto p-6">
    <button @click="navigateTo('/invoices')" class="mb-4 text-sm text-blue-600 hover:underline">
      ← Повернутися до списку
    </button>

    <div v-if="pending" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="error" class="p-4 bg-red-100 text-red-700 rounded-lg">
      Помилка завантаження інвойсу: {{ error.message }}
    </div>

    <div v-else-if="invoice" class="bg-white rounded-xl shadow-md p-6 space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-start border-b pb-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Рахунок № {{ invoice.number }}</h1>
          <p class="text-sm text-gray-500">Створено: {{ formatDate(invoice.created_at) }} | Оновлено: {{ formatDate(invoice.updated_at) }}</p>
        </div>
        <span :class="getStatusBadgeClass(invoice.status)" class="px-3 py-1 rounded-full text-sm font-semibold">
          {{ invoice.status }}
        </span>
      </div>

      <!-- General Info -->
      <div class="grid grid-cols-2 gap-4 text-sm bg-gray-50 p-4 rounded-lg">
        <div>
          <span class="text-gray-500 block">Постачальник:</span>
          <strong class="text-gray-800">{{ invoice.supplier_name }}</strong>
        </div>
        <div>
          <span class="text-gray-500 block">ЄДРПОУ / ІПН:</span>
          <strong class="text-gray-800">{{ invoice.supplier_tax_id }}</strong>
        </div>
        <div>
          <span class="text-gray-500 block">Дата виставлення:</span>
          <strong class="text-gray-800">{{ formatDate(invoice.issue_date) }}</strong>
        </div>
        <div>
          <span class="text-gray-500 block">Валюта:</span>
          <strong class="text-gray-800">{{ invoice.currency }}</strong>
        </div>
      </div>

      <!-- Warning if non-editable -->
      <div v-if="invoice.status !== 'pending'" class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-md text-sm">
        Редагування заблоковано, оскільки рахунок має статус <strong>{{ invoice.status }}</strong> (доступно лише для pending).
      </div>

      <!-- Edit Form -->
      <form @submit.prevent="saveInvoice" class="space-y-4">
        <h2 class="text-lg font-semibold text-gray-800">Редагування фінансових даних</h2>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Сума без ПДВ (Net)</label>
            <input
              v-model.number="form.net_amount"
              :disabled="invoice.status !== 'pending'"
              type="number" step="0.01" min="0.01"
              class="w-full border rounded-md p-2 disabled:bg-gray-100 disabled:text-gray-500"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">ПДВ (VAT)</label>
            <input
              v-model.number="form.vat_amount"
              :disabled="invoice.status !== 'pending'"
              type="number" step="0.01" min="0"
              class="w-full border rounded-md p-2 disabled:bg-gray-100 disabled:text-gray-500"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Загальна сума (Gross)</label>
            <input
              :value="calculatedGross"
              type="number" step="0.01"
              class="w-full border rounded-md p-2 bg-gray-100 font-bold text-gray-800"
              disabled
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Сплатити до (Due Date)</label>
          <input
            v-model="form.due_date"
            :disabled="invoice.status !== 'pending'"
            type="date"
            class="w-full border rounded-md p-2 disabled:bg-gray-100 disabled:text-gray-500"
            required
          />
        </div>

        <div v-if="saveError" class="text-red-600 text-sm">
          {{ saveError }}
        </div>

        <div v-if="saveSuccess" class="text-green-600 text-sm">
          Зміни успішно збережено!
        </div>

        <div class="flex justify-end">
          <button
            v-if="invoice.status === 'pending'"
            :disabled="isSaving"
            type="submit"
            class="bg-blue-600 text-white px-5 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            {{ isSaving ? 'Збереження...' : 'Зберегти зміни' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { Invoice, InvoiceStatus } from '~/types/invoice'

  const route = useRoute()
  const invoiceId = route.params.id

  const { data, pending, error, refresh } = await useApi<{ data: Invoice }>(`/invoices/${invoiceId}`)

  const invoice = computed(() => data.value?.data)

  const form = reactive({
    net_amount: 0,
    vat_amount: 0,
    due_date: ''
  })

  const isSaving = ref(false)
  const saveError = ref('')
  const saveSuccess = ref(false)

  // Init form data when invoice is loaded
  watch(invoice, (inv) => {
    if (inv) {
      form.net_amount = Number(inv.net_amount)
      form.vat_amount = Number(inv.vat_amount)
      form.due_date = inv.due_date
    }
  }, { immediate: true })

  // Auto-calculate Gross Amount
  const calculatedGross = computed(() => {
    const net = Number(form.net_amount) || 0
    const vat = Number(form.vat_amount) || 0
    return (net + vat).toFixed(2)
  })

  const saveInvoice = async () => {
    saveError.value = ''
    saveSuccess.value = false
    isSaving.value = true

    try {
      await useApi(`/invoices/${invoiceId}`, {
        method: 'PUT',
        body: {
          net_amount: form.net_amount,
          vat_amount: form.vat_amount,
          gross_amount: Number(calculatedGross.value),
          due_date: form.due_date
        }
      })
      saveSuccess.value = true
      await refresh()
    } catch (err: any) {
      saveError.value = err.data?.message || 'Не вдалося зберегти оновлення'
    } finally {
      isSaving.value = false
    }
  }

  const getStatusBadgeClass = (status?: InvoiceStatus) => {
    switch (status) {
      case 'approved': return 'bg-green-100 text-green-800'
      case 'rejected': return 'bg-red-100 text-red-800'
      default: return 'bg-yellow-100 text-yellow-800'
    }
  }

  const formatDate = (dateStr?: string) => {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('uk-UA')
  }
</script>