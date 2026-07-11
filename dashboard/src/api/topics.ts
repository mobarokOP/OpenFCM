import { api } from './client'
import type { Topic } from '@/types'

export const topicsApi = {
  async list(appId: string): Promise<Topic[]> {
    const { data } = await api.get(`/apps/${appId}/topics`)
    // Endpoint may return a bare array or an enveloped list.
    const raw = Array.isArray(data) ? data : data.data ?? []
    return raw as Topic[]
  },
}
