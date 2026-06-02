import { Head, Link, router } from '@inertiajs/react';
import { KeyRound, Pencil, Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Credential } from '@/types/package';

type PageProps = {
    credentials: Credential[];
};

export default function CredentialsIndex({ credentials }: PageProps) {
    return (
        <>
            <Head title="Credentials" />

            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Credentials"
                        description="Manage tokens for GitHub, GitLab, and Git repositories"
                    />
                    <Button asChild>
                        <Link href="/credentials/create">
                            <Plus className="mr-2 size-4" />
                            Add Credential
                        </Link>
                    </Button>
                </div>

                {credentials.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-12 text-center">
                        <KeyRound className="mb-4 size-12 text-muted-foreground" />
                        <h3 className="text-lg font-medium">
                            No credentials yet
                        </h3>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Add credentials to access private repositories.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/credentials/create">
                                <Plus className="mr-2 size-4" />
                                Add Credential
                            </Link>
                        </Button>
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
                                        Type
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        Base URL
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
                                {credentials.map((cred) => (
                                    <tr
                                        key={cred.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {cred.name}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant="secondary">
                                                {
                                                    {
                                                        github: 'GitHub',
                                                        gitlab: 'GitLab',
                                                        git: 'Git',
                                                    }[cred.type]
                                                }
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {cred.base_url ?? '-'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {cred.packages_count ?? 0}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-1">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/credentials/${cred.id}/edit`}
                                                    >
                                                        <Pencil className="size-3" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        router.delete(
                                                            `/credentials/${cred.id}`,
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="size-3" />
                                                </Button>
                                            </div>
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

CredentialsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Credentials',
            href: '/credentials',
        },
    ],
};
