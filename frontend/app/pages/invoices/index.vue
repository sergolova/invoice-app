<template>
  <div class="max-w-6xl mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Спектр Рахунків (Invoices)</h1>
    </div>

    <!-- Error State -->
    <div v-if="error" class="p-4 mb-4 text-red-700 bg-red-100 rounded-lg">
      Помилка завантаження даних: {{ error.message }}
      <button @click="refresh()" class="ml-4 underline">Повторити</button>
    </div>

    <!-- Loading State -->
    <div v-if="pending" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Table -->
    <div v-else-if="invoices && invoices.length" class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Номер</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Постачальник</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Сума (Gross)</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Сплатити до</th>
        </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
        <tr
          v-for="invoice in invoices"
          :key="invoice.id"
          @click="navigateTo(`/invoices/${invoice.id}`)"
          class="hover:bg-gray-50 cursor-pointer transition-colors"
        >
          <td class="px-6 py-4 whitespace-nowrap font-medium text-blue-600">
            {{ invoice.number }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-gray-900">
            {{ invoice.supplier_name }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">
            {{ Number(invoice.gross_amount).toFixed(2) }} {{ invoice.currency }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
              <span :class="getStatusBadgeClass(invoice.status)" class="px-2.5 py-0.5 rounded-full text-xs font-medium">
                {{ getStatusLabel(invoice.status) }}
              </span>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ formatDate(invoice.due_date) }}
          </td>
        </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12 bg-white rounded-lg shadow text-gray-500">
      Рахунків поки немає.
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { Invoice } from '~/types/invoice'
  import { formatDate } from "~/utils/formatters.ts";

  const { data, pending, error, refresh } = await useApi<{ data: Invoice[] }>(`/invoices`)

  const invoices = computed(() => data.value?.data || [])

</script>