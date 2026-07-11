import { Routes, Route, Navigate } from 'react-router-dom'
import { ProtectedRoute } from '@/components/ProtectedRoute'
import { AppLayout } from '@/components/layout/AppLayout'
import Login from '@/pages/Login'
import Register from '@/pages/Register'
import Overview from '@/pages/Overview'
import Apps from '@/pages/Apps'
import AppSettings from '@/pages/AppSettings'
import Notifications from '@/pages/Notifications'
import NotificationDetail from '@/pages/NotificationDetail'
import UsersPage from '@/pages/Users'
import Devices from '@/pages/Devices'
import Segments from '@/pages/Segments'
import Topics from '@/pages/Topics'
import Analytics from '@/pages/Analytics'
import Logs from '@/pages/Logs'
import Keys from '@/pages/Keys'
import SettingsPage from '@/pages/Settings'

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register />} />

      <Route
        element={
          <ProtectedRoute>
            <AppLayout />
          </ProtectedRoute>
        }
      >
        <Route path="/" element={<Overview />} />
        <Route path="/apps" element={<Apps />} />
        <Route path="/apps/:id/settings" element={<AppSettings />} />
        <Route path="/notifications" element={<Notifications />} />
        <Route path="/notifications/:id" element={<NotificationDetail />} />
        <Route path="/users" element={<UsersPage />} />
        <Route path="/devices" element={<Devices />} />
        <Route path="/segments" element={<Segments />} />
        <Route path="/topics" element={<Topics />} />
        <Route path="/analytics" element={<Analytics />} />
        <Route path="/logs" element={<Logs />} />
        <Route path="/keys" element={<Keys />} />
        <Route path="/settings" element={<SettingsPage />} />
      </Route>

      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
