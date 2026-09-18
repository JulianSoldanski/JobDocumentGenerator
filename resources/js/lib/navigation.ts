import {
    BarChart3,
    FileText,
    Inbox,
    ListChecks,
    UserRound,
} from 'lucide-react';
import { index as applications } from '@/routes/applications';
import { index as generator } from '@/routes/generator';
import { experience } from '@/routes/profile';
import { index as queue } from '@/routes/queue';
import { index as statistics } from '@/routes/statistics';
import type { NavItem } from '@/types';

/**
 * Die fünf Bereiche — an einer Stelle, damit Kopfzeile und mobiles Menü nicht
 * auseinanderlaufen.
 */
export const mainNavItems: NavItem[] = [
    {
        title: 'Generator',
        href: generator(),
        icon: FileText,
        section: '/generator',
    },
    { title: 'Queue', href: queue(), icon: Inbox, section: '/queue' },
    {
        title: 'Bewerbungen',
        href: applications(),
        icon: ListChecks,
        section: '/applications',
    },
    {
        title: 'Statistik',
        href: statistics(),
        icon: BarChart3,
        section: '/statistics',
    },
    {
        title: 'Profil',
        href: experience(),
        icon: UserRound,
        section: '/profile',
    },
];
