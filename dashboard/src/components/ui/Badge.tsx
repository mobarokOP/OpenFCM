import type { HTMLAttributes } from 'react'
import { cn } from '@/lib/utils'

type Tone = 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'muted'

const tones: Record<Tone, string> = {
  default: 'bg-muted text-muted-foreground',
  primary: 'bg-accent text-accent-foreground',
  success: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
  warning: 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
  danger: 'bg-red-500/15 text-red-600 dark:text-red-400',
  info: 'bg-sky-500/15 text-sky-600 dark:text-sky-400',
  muted: 'bg-muted text-muted-foreground',
}

interface BadgeProps extends HTMLAttributes<HTMLSpanElement> {
  tone?: Tone
}

export function Badge({ className, tone = 'default', ...props }: BadgeProps) {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
        tones[tone],
        className,
      )}
      {...props}
    />
  )
}

const statusMap: Record<string, Tone> = {
  active: 'success',
  sent: 'success',
  delivered: 'success',
  queued: 'info',
  sending: 'info',
  scheduled: 'warning',
  paused: 'warning',
  failed: 'danger',
  canceled: 'danger',
  disabled: 'danger',
}

export function StatusBadge({ status }: { status: string }) {
  const tone = statusMap[status?.toLowerCase()] ?? 'default'
  return <Badge tone={tone} className="capitalize">{status}</Badge>
}
