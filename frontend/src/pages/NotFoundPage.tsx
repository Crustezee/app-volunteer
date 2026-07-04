import { Link } from 'react-router-dom'

import { PagePlaceholder } from '@/pages/PagePlaceholder'

export function NotFoundPage() {
  return (
    <PagePlaceholder
      eyebrow="404"
      title="Halaman tidak ditemukan."
      description="URL ini belum tersedia di Migunani."
    >
      <Link
        to="/"
        className="inline-flex h-11 items-center rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground transition hover:bg-deep-green"
      >
        Kembali ke Discover
      </Link>
    </PagePlaceholder>
  )
}
