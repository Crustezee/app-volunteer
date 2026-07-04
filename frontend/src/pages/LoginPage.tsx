import { ArrowRight, Building2, HeartHandshake, LoaderCircle, LockKeyhole, Mail } from 'lucide-react'
import { useState } from 'react'
import { Navigate, useNavigate, useSearchParams } from 'react-router-dom'

import { useAuth } from '@/context/AuthContext'
import { ApiError } from '@/lib/api'
import { cn } from '@/lib/utils'

export function LoginPage() {
  const { session, loading, login } = useAuth()
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const next = safeNext(searchParams.get('next'))
  const initialDestination: 'volunteer' | 'organizer' = next?.startsWith('/organizer') ? 'organizer' : 'volunteer'
  const [destination, setDestination] = useState<'volunteer' | 'organizer'>(initialDestination)
  const [email, setEmail] = useState(demoAccounts[initialDestination].email)
  const [password, setPassword] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')

  if (!loading && session) {
    return <Navigate to={next ?? defaultDestination(session.capabilities)} replace />
  }

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault()
    setSubmitting(true)
    setError('')

    try {
      const result = await login(email.trim(), password, destination)
      const preferred = next ?? (destination === 'organizer' ? '/organizer' : '/volunteer/dashboard')
      const allowed = destination === 'organizer'
        ? result.capabilities.organizer
        : result.capabilities.volunteer

      navigate(allowed ? preferred : defaultDestination(result.capabilities), { replace: true })
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.first('email') : 'Login gagal. Coba kembali.')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="flex min-h-[calc(100svh-7rem)] items-center py-8">
      <section className="grid w-full gap-6 lg:grid-cols-[0.85fr_1.15fr]">
        <div className="rounded-lg border bg-deep-green p-8 text-primary-foreground shadow-sm">
          <span className="flex size-14 items-center justify-center rounded-md bg-secondary font-heading text-2xl font-extrabold text-secondary-foreground">
            M
          </span>
          <p className="mt-8 text-sm font-bold uppercase text-primary-foreground/70">Migunani Account</p>
          <h1 className="mt-3 font-heading text-4xl font-extrabold leading-tight sm:text-6xl">
            Masuk dan lanjutkan kontribusimu.
          </h1>
          <p className="mt-5 max-w-xl text-base leading-8 text-primary-foreground/78">
            Akun relawan dan akun organizer dipisahkan agar hak akses, dashboard, dan data operasional tetap jelas.
          </p>
          <div className="mt-8 rounded-md border border-white/15 bg-white/10 p-4 text-sm">
            <p className="font-bold">Akun demo lokal</p>
            <p className="mt-1 text-primary-foreground/75">Relawan: nadira@example.com · password</p>
            <p className="mt-1 text-primary-foreground/75">Organizer: owner@aksaramuda.test · password</p>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="rounded-lg border bg-card p-6 shadow-sm sm:p-8">
          <p className="text-sm font-bold uppercase text-primary">Login</p>
          <h2 className="mt-2 font-heading text-3xl font-extrabold">Pilih area kerja.</h2>

          <div className="mt-6 grid grid-cols-2 gap-2 rounded-md border bg-muted p-1">
            <AreaButton
              active={destination === 'volunteer'}
              icon={<HeartHandshake size={18} />}
              label="Relawan"
              onClick={() => {
                setDestination('volunteer')
                setEmail(demoAccounts.volunteer.email)
              }}
            />
            <AreaButton
              active={destination === 'organizer'}
              icon={<Building2 size={18} />}
              label="Organizer"
              onClick={() => {
                setDestination('organizer')
                setEmail(demoAccounts.organizer.email)
              }}
            />
          </div>

          <label className="mt-6 block">
            <span className="text-sm font-bold">Email</span>
            <span className="relative mt-2 block">
              <Mail className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" size={18} />
              <input
                type="email"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                className="h-12 w-full rounded-md border bg-background pl-10 pr-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"
                autoComplete="email"
                required
              />
            </span>
          </label>

          <label className="mt-4 block">
            <span className="text-sm font-bold">Password</span>
            <span className="relative mt-2 block">
              <LockKeyhole className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" size={18} />
              <input
                type="password"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                className="h-12 w-full rounded-md border bg-background pl-10 pr-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"
                autoComplete="current-password"
                required
              />
            </span>
          </label>

          {error ? <p className="mt-4 rounded-md bg-destructive/10 p-3 text-sm font-semibold text-destructive">{error}</p> : null}

          <button
            type="submit"
            disabled={submitting}
            className="mt-6 inline-flex h-12 w-full items-center justify-center gap-2 rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground transition hover:bg-deep-green disabled:cursor-wait disabled:opacity-60"
          >
            {submitting ? <LoaderCircle className="animate-spin" size={18} /> : <ArrowRight size={18} />}
            {submitting ? 'Memproses...' : `Masuk sebagai ${destination === 'organizer' ? 'Organizer' : 'Relawan'}`}
          </button>
        </form>
      </section>
    </div>
  )
}

const demoAccounts = {
  volunteer: { email: 'nadira@example.com' },
  organizer: { email: 'owner@aksaramuda.test' },
}

function AreaButton({ active, icon, label, onClick }: { active: boolean; icon: React.ReactNode; label: string; onClick: () => void }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={cn(
        'inline-flex h-11 items-center justify-center gap-2 rounded-sm text-sm font-bold',
        active ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground',
      )}
    >
      {icon}
      {label}
    </button>
  )
}

function safeNext(value: string | null) {
  return value?.startsWith('/') && !value.startsWith('//') ? value : null
}

function defaultDestination(capabilities: { volunteer: boolean; organizer: boolean }) {
  return capabilities.volunteer ? '/volunteer/dashboard' : capabilities.organizer ? '/organizer' : '/'
}
