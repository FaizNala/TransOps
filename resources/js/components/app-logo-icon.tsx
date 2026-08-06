import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export default function AppLogoIcon({ className, ...props }: HTMLAttributes<HTMLImageElement>) {
    return <img src="/TransOps-logo.png" alt="TransOps" className={cn('rounded-lg object-contain', className)} {...props} />;
}
