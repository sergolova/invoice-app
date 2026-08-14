const getDecimalSeparator = (locale: string): string => {
  const parts = new Intl.NumberFormat(locale).formatToParts(1.1)

  return parts.find(part => part.type === 'decimal')?.value ?? '.'
}

/**
 * This is a simplified parsing algorithm.
 */
export const parseMoneyToMinor = (value: unknown, locale: string,): bigint | null => {
  if (value === null || value === undefined) {
    return null
  }

  let str = String(value)

  str = str.replace(/[ \t]/g, '')

  if (str === '') {
    return null
  }

  const decimalSeparator = getDecimalSeparator(locale)

  if (decimalSeparator !== '.') {
    if (str.includes('.')) {
      return null
    }

    str = str.replace(decimalSeparator, '.')
  }

  if (!/^-?\d+(?:\.\d{0,2})?$/.test(str)) {
    return null
  }

  const negative = str.startsWith('-')

  if (negative) {
    str = str.slice(1)
  }

  const separatorIndex = str.indexOf('.')

  let units: string
  let fraction: string

  if (separatorIndex === -1) {
    units = str
    fraction = ''
  } else {
    units = str.slice(0, separatorIndex)
    fraction = str.slice(separatorIndex + 1)
  }

  const cents = fraction.padEnd(2, '0')

  let result = BigInt(units) * 100n + BigInt(cents)

  if (negative) {
    result = -result
  }

  return result
}

export const formatMoneyFromMinor = (value: bigint, locale: string): string => {
  const negative = value < 0n
  const absolute = negative ? -value : value
  const units = absolute / 100n
  const cents = (absolute % 100n).toString().padStart(2, '0')
  const decimalSeparator = getDecimalSeparator(locale)

  return `${negative ? '-' : ''}${units}${decimalSeparator}${cents}`
}

/**
 * Converts cents to a canonical decimal string for the API. Always uses "." regardless of locale. 125050n -> "1250.50"
 */
export const formatMoneyForApi = (value: bigint): string => {
  const negative = value < 0n
  const absolute = negative ? -value : value

  const units = absolute / 100n
  const cents = (absolute % 100n).toString().padStart(2, '0')

  return `${negative ? '-' : ''}${units}.${cents}`
}

export const parseApiMoneyToMinor = (value: string): bigint | null => {
  if (!/^-?\d+(?:\.\d{2})$/.test(value)) {
    return null
  }

  const isNegative = value.startsWith('-')
  const cleanValue = isNegative ? value.slice(1) : value
  const [units = '0', cents = '0'] = cleanValue.split('.')
  const minor = BigInt(units) * 100n + BigInt(cents)

  return isNegative ? -minor : minor
}