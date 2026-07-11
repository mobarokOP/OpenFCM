import { useState, useRef, useEffect } from 'react'
import { Check, ChevronsUpDown, Plus, LayoutGrid } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { useApps } from '@/hooks/useApps'
import { useSelectedApp } from '@/store/app'
import { cn } from '@/lib/utils'

export function AppSwitcher() {
  const { data: apps = [], isLoading } = useApps()
  const { selectedAppId, setSelectedAppId } = useSelectedApp()
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)
  const navigate = useNavigate()

  useEffect(() => {
    const onClick = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false)
    }
    document.addEventListener('mousedown', onClick)
    return () => document.removeEventListener('mousedown', onClick)
  }, [])

  const current = apps.find((a) => a.id === selectedAppId) ?? apps[0]

  return (
    <div className="relative" ref={ref}>
      <button
        onClick={() => setOpen((o) => !o)}
        className="flex w-full min-w-[180px] items-center justify-between gap-2 rounded-xl border border-input bg-card px-3 py-2 text-sm transition-colors hover:bg-muted"
      >
        <span className="flex items-center gap-2 truncate">
          <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-primary text-xs font-semibold text-primary-foreground">
            {current?.name?.[0]?.toUpperCase() ?? '·'}
          </span>
          <span className="truncate font-medium">
            {isLoading ? 'Loading…' : current?.name ?? 'No application'}
          </span>
        </span>
        <ChevronsUpDown className="h-4 w-4 shrink-0 text-muted-foreground" />
      </button>

      {open && (
        <div className="absolute left-0 top-full z-40 mt-1.5 w-64 rounded-xl border border-border bg-card p-1.5 shadow-xl">
          <p className="px-2 py-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            Applications
          </p>
          <div className="max-h-64 overflow-y-auto">
            {apps.length === 0 && (
              <p className="px-2 py-3 text-sm text-muted-foreground">No applications yet.</p>
            )}
            {apps.map((app) => (
              <button
                key={app.id}
                onClick={() => {
                  setSelectedAppId(app.id)
                  setOpen(false)
                }}
                className={cn(
                  'flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-sm transition-colors hover:bg-muted',
                  app.id === current?.id && 'bg-muted',
                )}
              >
                <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-primary text-xs font-semibold text-primary-foreground">
                  {app.name[0]?.toUpperCase()}
                </span>
                <span className="flex-1 truncate">
                  <span className="block truncate font-medium">{app.name}</span>
                  <span className="block truncate text-xs text-muted-foreground">{app.package_name}</span>
                </span>
                {app.id === current?.id && <Check className="h-4 w-4 text-primary" />}
              </button>
            ))}
          </div>
          <div className="mt-1 border-t border-border pt-1">
            <button
              onClick={() => {
                setOpen(false)
                navigate('/apps')
              }}
              className="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            >
              <LayoutGrid className="h-4 w-4" /> Manage applications
            </button>
            <button
              onClick={() => {
                setOpen(false)
                navigate('/apps?new=1')
              }}
              className="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-sm text-primary transition-colors hover:bg-muted"
            >
              <Plus className="h-4 w-4" /> New application
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
