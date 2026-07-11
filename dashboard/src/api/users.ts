import { api } from './client'
import type { AppUser, Paginated } from '@/types'

export interface UserQuery {
  page?: number
  search?: string
}

export const usersApi = {
  async list(appId: string, query: UserQuery = {}): Promise<Paginated<AppUser>> {
    const { data } = await api.get(`/apps/${appId}/users`, { params: query })
    return {
      data: data.data ?? [],
      meta: data.meta ?? { page: 1, per_page: 20, total: (data.data ?? []).length },
    }
  },
}
