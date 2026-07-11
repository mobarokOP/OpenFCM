import { api, unwrap } from './client'
import type { CreateNotificationPayload, NotificationItem, Paginated } from '@/types'

export const notificationsApi = {
  async list(appId: string, page = 1): Promise<Paginated<NotificationItem>> {
    const { data } = await api.get(`/apps/${appId}/notifications`, { params: { page } })
    return {
      data: data.data ?? [],
      meta: data.meta ?? { page: 1, per_page: 20, total: (data.data ?? []).length },
    }
  },
  async get(id: string): Promise<NotificationItem> {
    const { data } = await api.get(`/notifications/${id}`)
    return unwrap<NotificationItem>(data)
  },
  async create(payload: CreateNotificationPayload): Promise<NotificationItem> {
    const { data } = await api.post('/notifications', payload)
    return unwrap<NotificationItem>(data)
  },
  async cancel(id: string): Promise<void> {
    await api.post(`/notifications/${id}/cancel`)
  },
}
