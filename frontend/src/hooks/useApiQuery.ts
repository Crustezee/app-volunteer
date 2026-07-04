import { useCallback, useEffect, useRef, useState } from 'react'

export function useApiQuery<T>(key: string, fetcher: () => Promise<T>, enabled = true) {
  const fetcherRef = useRef(fetcher)
  const [data, setData] = useState<T | null>(null)
  const [loading, setLoading] = useState(enabled)
  const [error, setError] = useState<Error | null>(null)

  useEffect(() => {
    fetcherRef.current = fetcher
  }, [fetcher])

  const reload = useCallback(async () => {
    if (!enabled) {
      setLoading(false)
      return
    }

    setLoading(true)
    setError(null)
    try {
      setData(await fetcherRef.current())
    } catch (caught) {
      setError(caught instanceof Error ? caught : new Error('Data gagal dimuat.'))
    } finally {
      setLoading(false)
    }
  }, [enabled])

  useEffect(() => {
    void Promise.resolve().then(reload)
  }, [key, reload])

  return { data, setData, loading, error, reload }
}
