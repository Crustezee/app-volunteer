import type { LucideIcon } from 'lucide-react'

import { cn } from '@/lib/utils'

type StatsCardProps = {
  label: string
  value: string
  helper?: string
  icon?: LucideIcon
  tone?: 'green' | 'yellow' | 'dark' | 'neutral'
  className?: string
}

const toneClassName = {
  green: 'bg-primary text-primary-foreground',
  yellow: 'bg-secondary text-secondary-foreground',
  dark: 'bg-deep-green text-primary-foreground',
  neutral: 'bg-muted text-foreground',
}

export function StatsCard({
  label,
  value,
  helper,
  icon: Icon,
  tone = 'neutral',
  className,
}: StatsCardProps) {
  return (
    <article className={cn('rounded-lg border bg-card p-5 shadow-sm', className)}>
      <div className="flex items-start justify-between gap-4">
        <div>
          <p className="text-sm font-medium text-muted-foreground">{label}</p>
          <p className="mt-2 font-heading text-3xl font-extrabold text-foreground">
            {value}
          </p>
        </div>
        {Icon ? (
          <span
            className={cn(
              'flex size-10 shrink-0 items-center justify-center rounded-md',
              toneClassName[tone],
            )}
          >
            <Icon size={19} />
          </span>
        ) : null}
      </div>
      {helper ? <p className="mt-3 text-sm font-semibold text-primary">{helper}</p> : null}
    </article>
  )
}
