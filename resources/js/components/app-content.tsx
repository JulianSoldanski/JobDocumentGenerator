import * as React from 'react';
import { SidebarInset } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = React.ComponentProps<'main'> & {
    variant?: AppVariant;
};

export function AppContent({ variant = 'sidebar', children, ...props }: Props) {
    if (variant === 'sidebar') {
        return <SidebarInset {...props}>{children}</SidebarInset>;
    }

    // Keine feste Höchstbreite: Formulare begrenzen sich in ihren Layouts
    // selbst, der Generator braucht die ganze Breite für das Dokument.
    return (
        <main className="flex h-full w-full flex-1 flex-col gap-4" {...props}>
            {children}
        </main>
    );
}
