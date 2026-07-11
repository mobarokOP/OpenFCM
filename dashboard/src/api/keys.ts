import { api, unwrap } from './client'
import type { ApiKey } from '@/types'

export const keysApi = {
  async list(appId: string): Promise<ApiKey[]> {
    const { data } = await api.get(`/apps/${appId}/keys`)
    return data.data ?? []
  },
  async create(appId: string, name: string): Promise<ApiKey> {
    const { data } = await api.post(`/apps/${appId}/keys`, { name })
    return unwrap<ApiKey>(data)
  },
  async revoke(appId: string, id: string): Promise<void> {
    await api.delete(`/apps/${appId}/keys/${id}`)
  },
}
