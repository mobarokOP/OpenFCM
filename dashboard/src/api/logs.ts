import { api } from './client'
import type { DeliveryLog, Paginated } from '@/types'

export interface LogQuery {
  page?: number
  status?: string
}

export const logsApi = {
  async list(appId: string, notificationId: string, query: LogQuery = {}): Promise<Paginated<DeliveryLog>> {
    const { data } = await api.get(`/apps/${appId}/notifications/${notificationId}/logs`, { params: query })
    return {
      data: data.data ?? [],
      meta: data.meta ?? { page: 1, per_page: 20, total: (data.data ?? []).length },
    }
  },
}
