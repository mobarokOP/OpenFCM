import { useEffect } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { ArrowLeft, Trash2, Copy } from 'lucide-react'
import { appsApi } from '@/api/apps'
import { getErrorMessage } from '@/api/client'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Input, Label, Textarea } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Skeleton } from '@/components/ui/Skeleton'
import { StatusBadge } from '@/components/ui/Badge'
import { copyToClipboard, formatDate } from '@/lib/utils'

interface SettingsForm {
  name: string
  package_name: string
  fcm_project_id: string
  status: string
  rate_limit: number
  fcm_service_account: string
}

export default function AppSettings() {
  const { id = '' } = useParams()
  const navigate = useNavigate()
  const qc = useQueryClient()

  const { data: app, isLoading } = useQuery({
    queryKey: ['apps', id],
    queryFn: () => appsApi.get(id),
    enabled: !!id,
  })

  const { register, handleSubmit, reset } = useForm<SettingsForm>()

  useEffect(() => {
    if (app) {
      reset({
        name: app.name,
        package_name: app.package_name,
        fcm_project_id: app.fcm_project_id ?? '',
        status: app.status,
        rate_limit: app.rate_limit ?? 0,
        fcm_service_account: '',
      })
    }
  }, [app, reset])

  const update = useMutation({
    mutationFn: (v: SettingsForm) =>
      appsApi.update(id, {
        name: v.name,
        package_name: v.package_name,
        fcm_project_id: v.fcm_project_id,
        status: v.status,
        rate_limit: Number(v.rate_limit) || 0,
        ...(v.fcm_service_account ? { fcm_service_account: v.fcm_service_account } : {}),
      }),
    onSuccess: () => {
      toast.success('Settings saved')
      qc.invalidateQueries({ queryKey: ['apps'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not save settings')),
  })

  const remove = useMutation({
    mutationFn: () => appsApi.remove(id),
    onSuccess: () => {
      toast.success('Application deleted')
      qc.invalidateQueries({ queryKey: ['apps'] })
      navigate('/apps')
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not delete application')),
  })

  return (
    <>
      <button
        onClick={() => navigate('/apps')}
        className="mb-4 inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
      >
        <ArrowLeft className="h-4 w-4" /> Back to applications
      </button>

      <PageHeader
        title={app?.name ?? 'Application settings'}
        description={app?.package_name}
        actions={app && <StatusBadge status={app.status} />}
      />

      {isLoading ? (
        <div className="space-y-4">
          <Skeleton className="h-64 w-full" />
        </div>
      ) : !app ? (
        <Card>
          <CardContent className="py-12 text-center text-muted-foreground">Application not found.</CardContent>
        </Card>
      ) : (
        <form onSubmit={handleSubmit((v) => update.mutate(v))} className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>General</CardTitle>
              <CardDescription>Basic application identity.</CardDescription>
            </CardHeader>
            <CardContent className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <Label htmlFor="name">App name</Label>
                <Input id="name" {...register('name')} />
              </div>
              <div>
                <Label htmlFor="package_name">Package name</Label>
                <Input id="package_name" {...register('package_name')} />
              </div>
              <div>
                <Label htmlFor="status">Status</Label>
                <Select id="status" {...register('status')}>
                  <option value="active">Active</option>
                  <option value="paused">Paused</option>
                  <option value="disabled">Disabled</option>
                </Select>
              </div>
              <div>
                <Label>Application ID (App ID)</Label>
                <div className="flex gap-2">
                  <Input readOnly value={app.id} className="font-mono text-xs" />
                  <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    onClick={async () => {
                      await copyToClipboard(app.id)
                      toast.success('App ID copied')
                    }}
                  >
                    <Copy className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Firebase Cloud Messaging</CardTitle>
              <CardDescription>Credentials used to deliver via FCM HTTP v1.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <Label htmlFor="fcm_project_id">Firebase project ID</Label>
                <Input id="fcm_project_id" {...register('fcm_project_id')} />
              </div>
              <div>
                <Label htmlFor="fcm_service_account">Replace service account JSON (optional)</Label>
                <Textarea
                  id="fcm_service_account"
                  rows={5}
                  placeholder="Paste new service-account JSON to rotate credentials"
                  className="font-mono text-xs"
                  {...register('fcm_service_account')}
                />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Rate limits</CardTitle>
              <CardDescription>Maximum notifications dispatched per minute for this app.</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="max-w-xs">
                <Label htmlFor="rate_limit">Messages / minute</Label>
                <Input id="rate_limit" type="number" min={0} {...register('rate_limit')} />
                <p className="mt-1 text-xs text-muted-foreground">Set 0 for unlimited (subject to FCM quotas).</p>
              </div>
            </CardContent>
            <CardFooter className="justify-between">
              <p className="text-xs text-muted-foreground">Created {formatDate(app.created_at)}</p>
              <Button type="submit" loading={update.isPending}>
                Save changes
              </Button>
            </CardFooter>
          </Card>

          <Card className="border-red-500/30">
            <CardHeader>
              <CardTitle className="text-red-600 dark:text-red-400">Danger zone</CardTitle>
              <CardDescription>Deleting an application removes all its devices, users and logs.</CardDescription>
            </CardHeader>
            <CardFooter>
              <Button
                type="button"
                variant="danger"
                loading={remove.isPending}
                onClick={() => {
                  if (confirm(`Delete "${app.name}"? This cannot be undone.`)) remove.mutate()
                }}
              >
                <Trash2 className="h-4 w-4" /> Delete application
              </Button>
            </CardFooter>
          </Card>
        </form>
      )}
    </>
  )
}
