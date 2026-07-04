import type { ReactNode } from 'react'

type PagePlaceholderProps = {
  eyebrow: string
  title: string
  description: string
  children?: ReactNode
}

export function PagePlaceholder({
  eyebrow,
  title,
  description,
  children,
}: PagePlaceholderProps) {
  return (
    <section className="rounded-lg border bg-card p-6 shadow-sm sm:p-8">
      <p className="text-sm font-bold uppercase text-primary">{eyebrow}</p>
      <h1 className="mt-3 max-w-3xl font-heading text-3xl font-extrabold text-foreground sm:text-5xl">
        {title}
      </h1>
      <p className="mt-4 max-w-2xl text-base leading-7 text-muted-foreground">
        {description}
      </p>
      {children ? <div className="mt-6">{children}</div> : null}
    </section>
  )
}
