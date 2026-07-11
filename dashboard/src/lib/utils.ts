import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'
import { format, parseISO, formatDistanceToNow } from 'date-fns'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function formatDate(value?: string | null, pattern = 'MMM d, yyyy') {
  if (!value) return '—'
  try {
    return format(parseISO(value), pattern)
  } catch {
    return '—'
  }
}

export function formatDateTime(value?: string | null) {
  return formatDate(value, 'MMM d, yyyy · HH:mm')
}

export function fromNow(value?: string | null) {
  if (!value) return '—'
  try {
    return formatDistanceToNow(parseISO(value), { addSuffix: true })
  } catch {
    return '—'
  }
}

export function formatNumber(n?: number | null) {
  if (n === null || n === undefined || Number.isNaN(n)) return '0'
  if (Math.abs(n) >= 1_000_000) return (n / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M'
  if (Math.abs(n) >= 1_000) return (n / 1_000).toFixed(1).replace(/\.0$/, '') + 'K'
  return n.toLocaleString()
}

export function formatPercent(n?: number | null) {
  if (n === null || n === undefined || Number.isNaN(n)) return '0%'
  const v = n <= 1 ? n * 100 : n
  return `${v.toFixed(1).replace(/\.0$/, '')}%`
}

export function initials(name?: string | null) {
  if (!name) return '?'
  return name
    .split(' ')
    .map((p) => p[0])
    .filter(Boolean)
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

export async function copyToClipboard(text: string) {
  try {
    await navigator.clipboard.writeText(text)
    return true
  } catch {
    return false
  }
}
