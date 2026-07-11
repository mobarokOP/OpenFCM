import { create } from 'zustand'

const KEY = 'op-selected-app'

interface AppState {
  selectedAppId: string | null
  setSelectedAppId: (id: string | null) => void
}

export const useSelectedApp = create<AppState>((set) => ({
  selectedAppId: localStorage.getItem(KEY),
  setSelectedAppId: (id) => {
    if (id) localStorage.setItem(KEY, id)
    else localStorage.removeItem(KEY)
    set({ selectedAppId: id })
  },
}))
