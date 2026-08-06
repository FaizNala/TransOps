import { Link, usePage } from '@inertiajs/react';
import {
    Car,
    ClipboardList,
    FolderGit2,
    Fuel,
    IdCard,
    LayoutGrid,
    MapPin,
    ScrollText,
    Stamp,
    Truck,
    Users,
    Wrench,
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
import { dashboard } from '@/routes';
import { index as approvalsIndex } from '@/routes/approvals';
import { index as auditLogsIndex } from '@/routes/audit-logs';
import { index as bookingsIndex } from '@/routes/bookings';
import { index as driversIndex } from '@/routes/drivers';
import { index as employeesIndex } from '@/routes/employees';
import { index as fuelLogsIndex } from '@/routes/fuel-logs';
import { index as locationsIndex } from '@/routes/locations';
import { index as rentalPartnersIndex } from '@/routes/rental-partners';
import { index as serviceLogsIndex } from '@/routes/service-logs';
import { index as serviceSchedulesIndex } from '@/routes/service-schedules';
import { index as vehiclesIndex } from '@/routes/vehicles';
import type { NavItem } from '@/types';

const platformNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Pemesanan',
        href: bookingsIndex(),
        icon: Car,
    },
];

const masterNavItems: NavItem[] = [
    {
        title: 'Master Lokasi',
        href: locationsIndex(),
        icon: MapPin,
    },
    {
        title: 'Master Karyawan',
        href: employeesIndex(),
        icon: Users,
    },
    {
        title: 'Master Pengemudi',
        href: driversIndex(),
        icon: IdCard,
    },
    {
        title: 'Master Kendaraan',
        href: vehiclesIndex(),
        icon: Truck,
    },
    {
        title: 'Master Partner Sewa',
        href: rentalPartnersIndex(),
        icon: FolderGit2,
    },
];

const monitoringNavItems: NavItem[] = [
    {
        title: 'Pemakaian BBM',
        href: fuelLogsIndex(),
        icon: Fuel,
    },
    {
        title: 'Jadwal Service',
        href: serviceSchedulesIndex(),
        icon: Wrench,
    },
    {
        title: 'Riwayat Service',
        href: serviceLogsIndex(),
        icon: ClipboardList,
    },
    {
        title: 'Audit Log',
        href: auditLogsIndex(),
        icon: ScrollText,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const isApprover = auth.user?.role === 'approver';

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
                {isApprover ? (
                    <>
                        <NavMain items={platformNavItems.slice(0, 1)} />
                        <NavMain
                            items={[
                                {
                                    title: 'Persetujuan',
                                    href: approvalsIndex(),
                                    icon: Stamp,
                                },
                            ]}
                        />
                    </>
                ) : (
                    <>
                        <NavMain items={platformNavItems} />
                        <NavMain
                            label="Monitoring"
                            items={monitoringNavItems}
                        />
                        <NavMain label="Master Data" items={masterNavItems} />
                    </>
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
