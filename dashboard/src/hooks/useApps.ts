import { useEffect } from 'react'
import { useQuery } from '@tanstack/react-query'
import { appsApi } from '@/api/apps'
import { useSelectedApp } from '@/store/app'

export function useApps() {
  return useQuery({ queryKey: ['apps'], queryFn: appsApi.list })
}

/**
 * Returns the selected app id, auto-selecting the first available app
 * when none is chosen yet.
 */
export function useCurrentApp() {
  const { data: apps, isLoading } = useApps()
  const { selectedAppId, setSelectedAppId } = useSelectedApp()

  useEffect(() => {
    if (!apps || apps.length === 0) return
    const valid = selectedAppId && apps.some((a) => a.id === selectedAppId)
    if (!valid) setSelectedAppId(apps[0].id)
  }, [apps, selectedAppId, setSelectedAppId])

  const current =
    apps?.find((a) => a.id === selectedAppId) ?? apps?.[0] ?? null

  return { apps: apps ?? [], appId: current?.id ?? null, app: current, isLoading }
}
