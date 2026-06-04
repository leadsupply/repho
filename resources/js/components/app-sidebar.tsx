import { Link } from '@inertiajs/react';
import { BookOpen, FolderGit2, KeyRound, LayoutGrid, Package2, Github } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
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
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Repositories',
        href: '/repositories',
        icon: FolderGit2,
    },
    {
        title: 'Packages',
        href: '/packages',
        icon: Package2,
    },
    {
        title: 'Credentials',
        href: '/credentials',
        icon: KeyRound,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Instructions',
        href: '/instructions',
        icon: BookOpen,
    },
    {
        title: 'About...',
        href: 'https://github.com/juanparati/repho',
        icon: Github,
        external: true,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
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
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
