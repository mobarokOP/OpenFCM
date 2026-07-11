import { api, unwrap } from './client'
import type { Segment, SegmentFilter } from '@/types'

export interface SegmentPayload {
  name: string
  type: 'dynamic' | 'static'
  filters: SegmentFilter[]
}

export const segmentsApi = {
  async list(appId: string): Promise<Segment[]> {
    const { data } = await api.get(`/apps/${appId}/segments`)
    return data.data ?? []
  },
  async create(appId: string, payload: SegmentPayload): Promise<Segment> {
    const { data } = await api.post(`/apps/${appId}/segments`, payload)
    return unwrap<Segment>(data)
  },
  async update(appId: string, id: string, payload: SegmentPayload): Promise<Segment> {
    const { data } = await api.patch(`/apps/${appId}/segments/${id}`, payload)
    return unwrap<Segment>(data)
  },
  async remove(appId: string, id: string): Promise<void> {
    await api.delete(`/apps/${appId}/segments/${id}`)
  },
}
