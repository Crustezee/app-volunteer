import { AlertCircle, LoaderCircle, RefreshCw } from 'lucide-react'

export function LoadingState({ label = 'Memuat data...' }: { label?: string }) {
  return (
    <div className="flex min-h-52 items-center justify-center rounded-lg border bg-card p-8 text-muted-foreground shadow-sm">
      <LoaderCircle className="mr-3 animate-spin" size={20} />
      <span className="text-sm font-semibold">{label}</span>
    </div>
  )
}

export function ErrorState({
  error,
  onRetry,
}: {
  error: Error
  onRetry?: () => void
}) {
  return (
    <div className="rounded-lg border border-destructive/30 bg-card p-6 shadow-sm">
      <div className="flex items-start gap-3">
        <AlertCircle className="mt-0.5 shrink-0 text-destructive" size={20} />
        <div>
          <h2 className="font-heading text-xl font-extrabold">Data tidak dapat dimuat</h2>
          <p className="mt-1 text-sm text-muted-foreground">{error.message}</p>
        </div>
      </div>
      {onRetry ? (
        <button
          type="button"
          onClick={onRetry}
          className="mt-5 inline-flex h-10 items-center gap-2 rounded-md border bg-card px-4 text-sm font-bold hover:bg-muted"
        >
          <RefreshCw size={16} />
          Coba lagi
        </button>
      ) : null}
    </div>
  )
}

export function EmptyState({ title, description }: { title: string; description: string }) {
  return (
    <div className="rounded-lg border bg-card p-8 text-center shadow-sm">
      <h2 className="font-heading text-2xl font-extrabold">{title}</h2>
      <p className="mt-2 text-sm text-muted-foreground">{description}</p>
    </div>
  )
}
