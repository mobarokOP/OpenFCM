import { AppWindow, Plus } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import { Button } from '@/components/ui/Button'

export function NoAppSelected() {
  const navigate = useNavigate()
  return (
    <Card>
      <EmptyState
        icon={AppWindow}
        title="No application selected"
        description="Create your first application to start registering devices and sending push notifications."
        action={
          <Button onClick={() => navigate('/apps?new=1')}>
            <Plus className="h-4 w-4" /> Create application
          </Button>
        }
      />
    </Card>
  )
}
