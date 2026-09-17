import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import {
    ai,
    contact,
    education,
    experience,
    importMethod,
    projects,
    skills,
    style,
} from '@/routes/profile';
import type { NavItem } from '@/types';

const sectionNavItems: NavItem[] = [
    { title: 'Berufserfahrung', href: experience(), icon: null },
    { title: 'Ausbildung', href: education(), icon: null },
    { title: 'Skills & Sprachen', href: skills(), icon: null },
    { title: 'Projekte', href: projects(), icon: null },
    { title: 'Schreibstil', href: style(), icon: null },
    { title: 'Kontaktdaten', href: contact(), icon: null },
    { title: 'Import aus PDF', href: importMethod(), icon: null },
    { title: 'KI-Zugang', href: ai(), icon: null },
];

export default function ProfileLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="px-4 py-6">
            <Heading
                title="Profil"
                description="Die Datenbasis: einmal gepflegt, immer wieder verwendet."
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-56">
                    <nav
                        className="flex flex-col space-y-1 space-x-0"
                        aria-label="Profil"
                    >
                        {sectionNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-muted': isCurrentOrParentUrl(item.href),
                                })}
                            >
                                <Link href={item.href}>{item.title}</Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1">{children}</div>
            </div>
        </div>
    );
}
