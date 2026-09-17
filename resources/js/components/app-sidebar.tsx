import { Link } from '@inertiajs/react';
import {
    BarChart3,
    FileText,
    Inbox,
    ListChecks,
    UserRound,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { index as applications } from '@/routes/applications';
import { index as generator } from '@/routes/generator';
import { experience } from '@/routes/profile';
import { index as queue } from '@/routes/queue';
import { index as statistics } from '@/routes/statistics';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    { title: 'Generator', href: generator(), icon: FileText },
    { title: 'Queue', href: queue(), icon: Inbox },
    { title: 'Bewerbungen', href: applications(), icon: ListChecks },
    { title: 'Statistik', href: statistics(), icon: BarChart3 },
    { title: 'Profil', href: experience(), icon: UserRound },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={generator()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
