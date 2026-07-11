import { api } from './client'
import type { Device, Paginated } from '@/types'

export interface DeviceQuery {
  page?: number
  segment?: string
  search?: string
}

export const devicesApi = {
  async list(appId: string, query: DeviceQuery = {}): Promise<Paginated<Device>> {
    const { data } = await api.get(`/apps/${appId}/devices`, { params: query })
    return {
      data: data.data ?? [],
      meta: data.meta ?? { page: 1, per_page: 20, total: (data.data ?? []).length },
    }
  },
}
