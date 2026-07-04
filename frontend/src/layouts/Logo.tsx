import { Link } from 'react-router-dom'

type LogoProps = {
  to?: string
}

export function Logo({ to = '/' }: LogoProps) {
  return (
    <Link to={to} className="flex items-center gap-3" aria-label="Migunani home">
      <span className="flex size-10 items-center justify-center overflow-hidden rounded-md bg-primary shadow-sm">
        <img src="/logo-mark.svg" alt="" className="size-full object-cover" />
      </span>
      <span className="leading-none">
        <span className="block font-heading text-lg font-extrabold text-foreground">
          Migunani
        </span>
        <span className="block text-xs font-medium text-muted-foreground">
          Volunteer marketplace by Wish Me Luck Team
        </span>
      </span>
    </Link>
  )
}
