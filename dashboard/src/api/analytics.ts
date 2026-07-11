import { api, unwrap } from './client'
import type { Analytics, AnalyticsOverview } from '@/types'

export const analyticsApi = {
  async overview(appId: string): Promise<AnalyticsOverview> {
    const { data } = await api.get(`/apps/${appId}/analytics/overview`)
    return unwrap<AnalyticsOverview>(data)
  },
  async range(appId: string, from: string, to: string): Promise<Analytics> {
    const { data } = await api.get(`/apps/${appId}/analytics`, { params: { from, to } })
    return unwrap<Analytics>(data)
  },
}
