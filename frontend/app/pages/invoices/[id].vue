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
          <p class="text-sm text-gray-500">Створено: {{ formatDateTime(invoice.created_at) }} | Оновлено:
            {{ formatDateTime(invoice.updated_at) }}</p>
        </div>
        <span :class="getStatusBadgeClass(invoice.status)" class="px-3 py-1 rounded-full text-sm font-semibold">
          {{ getStatusLabel(invoice.status) }}
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
      <div v-if="invoice.status !== 'pending'"
           class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-md text-sm">
        Редагування заблоковано, оскільки рахунок має статус <strong>{{ getStatusLabel(invoice.status) }}</strong>
        (доступно лише для {{ getStatusLabel('pending') }}).
      </div>

      <!-- Edit Form -->
      <form @submit.prevent="saveInvoice" class="space-y-4">
        <h2 class="text-lg font-semibold text-gray-800">Редагування фінансових даних</h2>

        <div class="grid grid-cols-3 gap-4">
          <!-- Net Amount -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Сума без ПДВ (Net)</label>
            <input
              v-model="netAmount"
              v-bind="netAmountProps"
              :disabled="invoice.status !== 'pending'"
              type="text"
              inputmode="decimal" step="0.01"
              class="w-full border rounded-md p-2 disabled:bg-gray-100 disabled:text-gray-500"
              :class="{ 'border-red-500': errors.net_amount }"
            />
            <span v-if="errors.net_amount" class="text-xs text-red-600 mt-1 block">{{ errors.net_amount }}</span>
          </div>

          <!-- VAT Amount -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">ПДВ (VAT)</label>
            <input
              v-model="vatAmount"
              v-bind="vatAmountProps"
              :disabled="invoice.status !== 'pending'"
              type="text"
              inputmode="decimal"
              class="w-full border rounded-md p-2 disabled:bg-gray-100 disabled:text-gray-500"
              :class="{ 'border-red-500': errors.vat_amount }"
            />
            <span v-if="errors.vat_amount" class="text-xs text-red-600 mt-1 block">{{ errors.vat_amount }}</span>
          </div>

          <!-- Gross Amount -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Загальна сума (Gross)</label>
            <input
              :value="grossAmount"
              type="text"
              inputmode="decimal"
              class="w-full border rounded-md p-2 bg-gray-100 font-bold text-gray-800"
              disabled
            />
            <span v-if="errors.gross_amount" class="text-red-500 text-xs mt-1 block">
    {{ errors.gross_amount }}
  </span>
          </div>
        </div>

        <!-- Due Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Сплатити до (Due Date)</label>
          <input
            v-model="dueDate"
            v-bind="dueDateProps"
            :disabled="invoice.status !== 'pending'"
            :min="invoice.issue_date"
            type="date"
            class="w-full border rounded-md p-2 disabled:bg-gray-100 disabled:text-gray-500"
            :class="{ 'border-red-500': errors.due_date }"
          />
          <span v-if="errors.due_date" class="text-xs text-red-600 mt-1 block">{{ errors.due_date }}</span>
        </div>

        <!-- System Messages -->
        <div v-if="saveError" class="text-red-600 text-sm">
          {{ saveError }}
        </div>

        <div v-if="saveSuccess && !isGrossAdjusted" class="p-3 bg-green-50 text-green-700 rounded-md text-sm">
          Зміни успішно збережено!
        </div>

        <div v-if="saveSuccess && isGrossAdjusted"
             class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-md text-sm space-y-1">
          <div class="font-semibold">Зміни збережено, але підсумкову суму автоматично скориговано сервером.</div>
          <div>
            Загальна сума змінена з <span class="line-through">{{ adjustedGrossDetails.was }}</span> на
            <strong>{{ adjustedGrossDetails.became }}</strong> відповідно до правил розрахунку ПДВ.
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end">
          <button
            v-if="invoice.status === 'pending'"
            type="submit"
            :disabled="isSubmitting || !meta.valid || !meta.dirty"
            class="bg-blue-600 text-white px-5 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 transition-colors"
          >
            {{ isSubmitting ? 'Збереження...' : 'Зберегти зміни' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { useForm } from 'vee-validate'
  import { toTypedSchema } from '@vee-validate/zod'
  import * as z from 'zod'
  import type { Invoice, UpdateInvoiceDTO } from '~/types/invoice'
  import {
    formatMoneyForApi,
    formatMoneyFromMinor,
    parseApiMoneyToMinor,
    parseMoneyToMinor,
  } from '~/utils/moneyNormalizer'

  // ---------------------------------------------------------
  // Setup & Constants
  // ---------------------------------------------------------

  const route = useRoute()
  const invoiceId = route.params.id as string
  const appLocale = 'uk-UA'
  const MAX_MONEY_MINOR = 999999999999n

  // ---------------------------------------------------------
  // API Data Fetching
  // ---------------------------------------------------------

  const { data, pending, error } = await useApi<{ data: Invoice }>(`/invoices/${invoiceId}`)
  const invoice = computed(() => data.value?.data)

  // ---------------------------------------------------------
  // Validation Schemas
  // ---------------------------------------------------------

  const moneySchema = z
    .string()
    .trim()
    .min(1, 'Вкажіть суму')
    .refine(
      (value) => parseMoneyToMinor(value, appLocale) !== null,
      'Введіть коректну суму',
    )
    .refine(
      (value) => {
        const minor = parseMoneyToMinor(value, appLocale)
        return minor !== null && minor <= MAX_MONEY_MINOR
      },
      'Сума не може перевищувати 9 999 999 999.99',
    )

  const netAmountSchema = moneySchema
    .refine(
      (value) => {
        const minor = parseMoneyToMinor(value, appLocale)

        return minor !== null && minor > 0n
      },
      'Сума має бути більше 0',
    )
    .transform((value) => {
      const minor = parseMoneyToMinor(value, appLocale)!

      return formatMoneyForApi(minor)
    })

  const vatAmountSchema = moneySchema
    .refine(
      (value) => {
        const minor = parseMoneyToMinor(value, appLocale)

        return minor !== null && minor >= 0n
      },
      'ПДВ не може бути від’ємним',
    )
    .transform((value) => {
      const minor = parseMoneyToMinor(value, appLocale)!

      return formatMoneyForApi(minor)
    })

  const grossAmountSchema = moneySchema.transform((value) => {
    const minor = parseMoneyToMinor(value, appLocale)!

    return formatMoneyForApi(minor)
  })

  const invoiceSchema = z
    .object({
      net_amount: netAmountSchema,
      vat_amount: vatAmountSchema,
      gross_amount: grossAmountSchema,
      updated_at: z.string().optional(),
      due_date: z
        .string()
        .min(1, 'Укажіть дату сплати'),
    })
    .superRefine((values, ctx) => {
      const net = parseApiMoneyToMinor(values.net_amount)
      const vat = parseApiMoneyToMinor(values.vat_amount)
      const gross = parseApiMoneyToMinor(values.gross_amount)

      if (net === null || vat === null || gross === null) return

      if (gross !== net + vat) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          path: ['gross_amount'],
          message: 'Загальна сума має дорівнювати сумі без ПДВ + ПДВ',
        })
      }
    })

  // ---------------------------------------------------------
  // Form Initialization
  // ---------------------------------------------------------

  const {
    defineField,
    errors,
    handleSubmit,
    setValues,
    setFieldValue,
    isSubmitting,
    meta,
  } = useForm({
    validationSchema: toTypedSchema(invoiceSchema),
    initialValues: {
      net_amount: '',
      vat_amount: '',
      gross_amount: '',
      due_date: '',
    },
  })

  const [netAmount, netAmountProps] = defineField('net_amount')
  const [vatAmount, vatAmountProps] = defineField('vat_amount')
  const [grossAmount] = defineField('gross_amount')
  const [dueDate, dueDateProps] = defineField('due_date')

  // ---------------------------------------------------------
  // Component State
  // ---------------------------------------------------------

  const saveError = ref('')
  const saveSuccess = ref(false)
  const isGrossAdjusted = ref(false)
  const adjustedGrossDetails = ref({ was: '', became: '' })

  // ---------------------------------------------------------
  // Sync Form with Loaded Invoice
  // ---------------------------------------------------------

  watch(
    invoice,
    (inv) => {
      if (!inv) return

      const net = parseApiMoneyToMinor(inv.net_amount)
      const vat = parseApiMoneyToMinor(inv.vat_amount)
      const gross = parseApiMoneyToMinor(inv.gross_amount)

      setValues({
        net_amount:
          net !== null
            ? formatMoneyFromMinor(net, appLocale)
            : '',

        vat_amount:
          vat !== null
            ? formatMoneyFromMinor(vat, appLocale)
            : '',

        gross_amount:
          gross !== null
            ? formatMoneyFromMinor(gross, appLocale)
            : '',

        due_date: inv.due_date || '',
        updated_at: inv.updated_at,
      })
    },
    { immediate: true },
  )

  // ---------------------------------------------------------
  // Auto-calculate Gross Amount
  // ---------------------------------------------------------

  watch(
    [netAmount, vatAmount],
    ([net, vat]) => {
      const n = parseMoneyToMinor(net, appLocale)
      const v = parseMoneyToMinor(vat, appLocale)

      if (n === null || v === null) {
        setFieldValue('gross_amount', '', true)
        return
      }

      setFieldValue(
        'gross_amount',
        formatMoneyFromMinor(n + v, appLocale),
        true,
      )
    })

  // ---------------------------------------------------------
  // Submit Handler
  // ---------------------------------------------------------

  const saveInvoice = handleSubmit(async (values) => {
    saveError.value = ''
    saveSuccess.value = false
    isGrossAdjusted.value = false

    // Let's save what the front end was thinking before sending the request
    const clientGross = values.gross_amount

    try {
      const body: UpdateInvoiceDTO = values

      // We retrieve the updated invoice object directly from the backend response
      const response = await $api<{ data: Invoice }>(`/invoices/${invoiceId}`, {
        method: 'PUT',
        body,
      })

      const serverGross = response.data.gross_amount

      // A rule of thumb in financial system UX: never change numbers without the user's knowledge!
      // Checking for discrepancies
      if (clientGross !== serverGross) {
        isGrossAdjusted.value = true
        adjustedGrossDetails.value = {
          was: clientGross,
          became: serverGross,
        }
      }

      // Updating the data
      data.value = response

      saveSuccess.value = true
    } catch (err: any) {
      // 409 Conflict — Someone saved it before we did
      if (err.status === 409 || err.statusCode === 409) {
        saveError.value = 'Ці дані застаріли! Інший користувач уже змінив цей обліковий запис. Будь ласка, оновіть сторінку.'
        return
      }

      //  тут також слід врахувати безліч інших малоймовірних сценаріїв:
      // - Втрата сесії або токена під час введення даних (401 Unauthorized)
      // - Видалення або архівування інвойсу іншим користувачем (404 Not Found)
      // - Зависання зовнішніх інтеграцій на бекенді (504 Gateway Timeout)
      // - Обрив інтернет-з'єднання в момент відправлення (Network Error)
      // - непередбачуваний збій в БД. Треба ґарантувати, щоб користувач не побачив SQL-error
      // ...

      saveError.value =
        err.data?.message ||
        'Не вдалося зберегти оновлення'
    }
  })
</script>