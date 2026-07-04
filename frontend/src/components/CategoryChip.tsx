import type { Category, EventCategory } from '@/types/migunani'
import { cn } from '@/lib/utils'

type CategoryChipProps = {
  category: Category | EventCategory | 'Semua'
  active?: boolean
  onClick?: () => void
  className?: string
}

export function CategoryChip({
  category,
  active = false,
  onClick,
  className,
}: CategoryChipProps) {
  const label = typeof category === 'string' ? category : category.name
  const style =
    typeof category === 'string'
      ? undefined
      : {
          backgroundColor: active ? category.bgColor : `${category.bgColor}22`,
          color: active ? category.color : undefined,
        }

  const Comp = onClick ? 'button' : 'span'

  return (
    <Comp
      type={onClick ? 'button' : undefined}
      onClick={onClick}
      style={style}
      className={cn(
        'inline-flex w-fit items-center rounded-full border px-3 py-1.5 text-xs font-bold transition',
        active
          ? 'border-transparent shadow-sm'
          : 'border-border bg-card text-muted-foreground hover:border-primary/30 hover:text-foreground',
        onClick && 'cursor-pointer',
        className,
      )}
    >
      {label}
    </Comp>
  )
}
