import { Head, Link, router } from '@inertiajs/react';
import { FolderGit2, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Repository } from '@/types/package';

type PageProps = {
    repositories: Repository[];
};

export default function RepositoriesIndex({ repositories }: PageProps) {
    return (
        <>
            <Head title="Repositories" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Repositories"
                        description="Manage your Composer repositories"
                    />
                    <Button asChild>
                        <Link href="/repositories/create">
                            <Plus className="mr-2 size-4" />
                            Add Repository
                        </Link>
                    </Button>
                </div>

                {repositories.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-12 text-center">
                        <FolderGit2 className="mb-4 size-12 text-muted-foreground" />
                        <h3 className="text-lg font-medium">
                            No repositories yet
                        </h3>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create your first repository to get started.
                        </p>
                    </div>
                ) : (
                    <div className="rounded-lg border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-3 text-left font-medium">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Slug
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Auth
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Packages
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {repositories.map((repo) => (
                                    <tr
                                        key={repo.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3">
                                            <Link
                                                href={`/repositories/${repo.id}`}
                                                className="font-medium hover:underline"
                                            >
                                                {repo.name}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3 font-mono text-xs text-muted-foreground">
                                            {repo.slug}
                                        </td>
                                        <td className="px-4 py-3">
                                            <AuthBadge type={repo.auth_type} />
                                        </td>
                                        <td className="px-4 py-3">
                                            {repo.packages_count ?? 0}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {repo.slug !== 'default' && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        router.delete(
                                                            `/repositories/${repo.id}`,
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="size-3" />
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}

function AuthBadge({ type }: { type: Repository['auth_type'] }) {
    const config = {
        none: { label: 'Public', variant: 'secondary' as const },
        basic: { label: 'Basic Auth', variant: 'default' as const },
        token: { label: 'Token', variant: 'default' as const },
    };
    const { label, variant } = config[type];
    return <Badge variant={variant}>{label}</Badge>;
}

RepositoriesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Repositories',
            href: '/repositories',
        },
    ],
};
