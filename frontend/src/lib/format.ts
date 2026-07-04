const dateFormatter = new Intl.DateTimeFormat('id-ID', {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
})

const weekdayFormatter = new Intl.DateTimeFormat('id-ID', {
  weekday: 'long',
})

const numberFormatter = new Intl.NumberFormat('id-ID')

export function formatDate(date: string) {
  return dateFormatter.format(new Date(date))
}

export function formatWeekday(date: string) {
  return weekdayFormatter.format(new Date(date))
}

export function formatNumber(value: number) {
  return numberFormatter.format(value)
}

export function formatEventTime(startTime: string, endTime: string) {
  return `${startTime}-${endTime} WIB`
}

export function getFillPercentage(registered: number, quota: number) {
  if (quota <= 0) {
    return 0
  }

  return Math.min(Math.round((registered / quota) * 100), 100)
}
