import { Head, Link } from '@inertiajs/react';
import { FolderGit2, KeyRound, Package, ShieldAlert } from 'lucide-react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    type ChartConfig,
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';

type Advisory = {
    id: number;
    package_name: string;
    title: string;
    link: string;
    cve: string | null;
    severity: string | null;
    reported_at: string | null;
};

type PageProps = {
    repositoriesCount: number;
    packagesCount: number;
    credentialsCount: number;
    downloadStats: { date: string; downloads: number }[];
    advisories: Advisory[];
};

const chartConfig = {
    downloads: {
        label: 'Downloads',
        color: 'var(--chart-1)',
    },
} satisfies ChartConfig;

export default function Dashboard({
    repositoriesCount,
    packagesCount,
    credentialsCount,
    downloadStats,
    advisories,
}: PageProps) {
    const totalDownloads = downloadStats.reduce(
        (sum, d) => sum + d.downloads,
        0,
    );

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <Link
                        href="/repositories"
                        className="rounded-xl border border-sidebar-border/70 p-6 transition-colors hover:bg-muted/50 dark:border-sidebar-border"
                    >
                        <div className="flex items-center justify-between">
                            <p className="text-sm font-medium text-muted-foreground">
                                Repositories
                            </p>
                            <FolderGit2 className="size-5 text-muted-foreground" />
                        </div>
                        <p className="mt-2 text-4xl font-bold">
                            {repositoriesCount}
                        </p>
                    </Link>
                    <Link
                        href="/packages"
                        className="rounded-xl border border-sidebar-border/70 p-6 transition-colors hover:bg-muted/50 dark:border-sidebar-border"
                    >
                        <div className="flex items-center justify-between">
                            <p className="text-sm font-medium text-muted-foreground">
                                Packages
                            </p>
                            <Package className="size-5 text-muted-foreground" />
                        </div>
                        <p className="mt-2 text-4xl font-bold">
                            {packagesCount}
                        </p>
                    </Link>
                    <Link
                        href="/credentials"
                        className="rounded-xl border border-sidebar-border/70 p-6 transition-colors hover:bg-muted/50 dark:border-sidebar-border"
                    >
                        <div className="flex items-center justify-between">
                            <p className="text-sm font-medium text-muted-foreground">
                                Credentials
                            </p>
                            <KeyRound className="size-5 text-muted-foreground" />
                        </div>
                        <p className="mt-2 text-4xl font-bold">
                            {credentialsCount}
                        </p>
                    </Link>
                </div>

                <Card className="max-h-[420px] flex-1">
                    <CardHeader>
                        <CardTitle>Downloads</CardTitle>
                        <CardDescription>
                            {totalDownloads.toLocaleString()} downloads in the
                            last 30 days
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ChartContainer
                            config={chartConfig}
                            className="h-[300px] w-full"
                        >
                            <BarChart data={downloadStats}>
                                <CartesianGrid vertical={false} />
                                <XAxis
                                    dataKey="date"
                                    tickLine={false}
                                    tickMargin={10}
                                    tickFormatter={(value: string) => {
                                        const date = new Date(
                                            value + 'T00:00:00',
                                        );
                                        return date.toLocaleDateString(
                                            'en-US',
                                            {
                                                month: 'short',
                                                day: 'numeric',
                                            },
                                        );
                                    }}
                                    interval="preserveStartEnd"
                                />
                                <YAxis
                                    tickLine={false}
                                    axisLine={false}
                                    allowDecimals={false}
                                />
                                <ChartTooltip
                                    content={
                                        <ChartTooltipContent
                                            labelFormatter={(
                                                value,
                                            ) => {
                                                const date = new Date(
                                                    value + 'T00:00:00',
                                                );
                                                return date.toLocaleDateString(
                                                    'en-US',
                                                    {
                                                        weekday: 'short',
                                                        month: 'short',
                                                        day: 'numeric',
                                                    },
                                                );
                                            }}
                                        />
                                    }
                                />
                                <Bar
                                    dataKey="downloads"
                                    fill="var(--color-downloads)"
                                    radius={[4, 4, 0, 0]}
                                />
                            </BarChart>
                        </ChartContainer>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <ShieldAlert className="size-5 text-destructive" />
                            Security Vulnerabilities
                        </CardTitle>
                        <CardDescription>
                            Last 10 security advisories found across packages
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {advisories.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No security vulnerabilities found.
                            </p>
                        ) : (
                            <div className="max-h-[300px] overflow-y-auto">
                                <table className="w-full text-sm">
                                    <thead className="sticky top-0 z-10 bg-card">
                                        <tr className="border-b">
                                            <th className="px-3 py-2 text-left font-medium">
                                                Package
                                            </th>
                                            <th className="px-3 py-2 text-left font-medium">
                                                Vulnerability
                                            </th>
                                            <th className="px-3 py-2 text-left font-medium">
                                                Severity
                                            </th>
                                            <th className="px-3 py-2 text-left font-medium">
                                                Reported
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {advisories.map((advisory) => (
                                            <tr
                                                key={advisory.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-3 py-2 font-mono text-xs">
                                                    {advisory.package_name}
                                                </td>
                                                <td className="px-3 py-2">
                                                    <a
                                                        href={advisory.link}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="hover:underline"
                                                    >
                                                        {advisory.title}
                                                    </a>
                                                    {advisory.cve && (
                                                        <span className="ml-1.5 text-xs text-muted-foreground">
                                                            {advisory.cve}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-3 py-2">
                                                    <SeverityBadge
                                                        severity={
                                                            advisory.severity
                                                        }
                                                    />
                                                </td>
                                                <td className="px-3 py-2 text-xs text-muted-foreground">
                                                    {advisory.reported_at ??
                                                        '-'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function SeverityBadge({ severity }: { severity: string | null }) {
    if (!severity) {
        return <Badge variant="outline">Unknown</Badge>;
    }

    const variant =
        severity === 'critical' || severity === 'high'
            ? 'destructive'
            : severity === 'medium'
              ? 'default'
              : 'secondary';

    return (
        <Badge variant={variant}>
            {severity.charAt(0).toUpperCase() + severity.slice(1)}
        </Badge>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
