import { api, unwrap } from './client'
import type { Application } from '@/types'

export interface CreateAppPayload {
  name: string
  package_name: string
  fcm_project_id?: string
  fcm_service_account?: string
}

export const appsApi = {
  async list(): Promise<Application[]> {
    const { data } = await api.get('/apps')
    return data.data ?? []
  },
  async get(id: string): Promise<Application> {
    const { data } = await api.get(`/apps/${id}`)
    return unwrap<Application>(data)
  },
  async create(payload: CreateAppPayload): Promise<Application> {
    const body: Record<string, unknown> = { ...payload }
    if (payload.fcm_service_account) {
      try {
        body.fcm_service_account = JSON.parse(payload.fcm_service_account)
      } catch {
        // send as-is; backend will validate
      }
    }
    const { data } = await api.post('/apps', body)
    return unwrap<Application>(data)
  },
  async update(id: string, payload: Partial<CreateAppPayload> & { status?: string; rate_limit?: number }): Promise<Application> {
    const { data } = await api.patch(`/apps/${id}`, payload)
    return unwrap<Application>(data)
  },
  async remove(id: string): Promise<void> {
    await api.delete(`/apps/${id}`)
  },
}
