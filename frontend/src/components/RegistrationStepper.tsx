import { Check } from 'lucide-react'

import { cn } from '@/lib/utils'

export type RegistrationStep = {
  id: string
  label: string
  description: string
}

type RegistrationStepperProps = {
  steps: RegistrationStep[]
  currentStep: number
  className?: string
}

export function RegistrationStepper({
  steps,
  currentStep,
  className,
}: RegistrationStepperProps) {
  return (
    <ol className={cn('grid gap-3 md:grid-cols-4', className)}>
      {steps.map((step, index) => {
        const isComplete = index < currentStep
        const isActive = index === currentStep

        return (
          <li
            key={step.id}
            className={cn(
              'rounded-lg border bg-card p-4 shadow-sm transition',
              isActive && 'border-primary ring-2 ring-primary/15',
              isComplete && 'border-primary/30 bg-accent',
            )}
          >
            <div className="flex items-center gap-3">
              <span
                className={cn(
                  'flex size-8 shrink-0 items-center justify-center rounded-full border text-sm font-bold',
                  isActive && 'border-primary bg-primary text-primary-foreground',
                  isComplete && 'border-primary bg-primary text-primary-foreground',
                  !isActive && !isComplete && 'border-border bg-muted text-muted-foreground',
                )}
              >
                {isComplete ? <Check size={16} /> : index + 1}
              </span>
              <div>
                <p className="text-sm font-bold text-foreground">{step.label}</p>
                <p className="mt-0.5 text-xs leading-5 text-muted-foreground">
                  {step.description}
                </p>
              </div>
            </div>
          </li>
        )
      })}
    </ol>
  )
}
