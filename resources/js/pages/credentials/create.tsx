import { Form, Head, router } from '@inertiajs/react';
import { CheckCircle2, Github } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type PageProps = {
    githubToken: string | null;
    githubOAuthEnabled: boolean;
};

export default function CredentialsCreate({
    githubToken,
    githubOAuthEnabled,
}: PageProps) {
    const [type, setType] = useState(githubToken ? 'github' : 'github');
    const [useManualToken, setUseManualToken] = useState(false);

    const showGitHubOAuth =
        type === 'github' && githubOAuthEnabled && !useManualToken;
    const hasOAuthToken = showGitHubOAuth && !!githubToken;

    return (
        <>
            <Head title="Add Credential" />

            <div className="mx-auto max-w-2xl space-y-6 p-4">
                <Heading
                    title="Add Credential"
                    description="Add a token to access private repositories"
                />

                <Form
                    action="/credentials"
                    method="post"
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    placeholder="My GitHub Token"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="type">Type</Label>
                                <Select
                                    name="type"
                                    defaultValue={type}
                                    onValueChange={(value) => {
                                        setType(value);
                                        setUseManualToken(false);
                                    }}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="github">
                                            GitHub
                                        </SelectItem>
                                        <SelectItem value="gitlab">
                                            GitLab
                                        </SelectItem>
                                        <SelectItem value="git">
                                            Git
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.type} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="token">Token</Label>

                                {showGitHubOAuth ? (
                                    hasOAuthToken ? (
                                        <div className="space-y-2">
                                            <input
                                                type="hidden"
                                                name="token"
                                                value={githubToken}
                                            />
                                            <div className="flex items-center gap-2 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                                                <CheckCircle2 className="size-4 shrink-0" />
                                                Token obtained from GitHub
                                            </div>
                                            <button
                                                type="button"
                                                className="text-xs text-muted-foreground underline"
                                                onClick={() =>
                                                    setUseManualToken(true)
                                                }
                                            >
                                                Enter token manually instead
                                            </button>
                                        </div>
                                    ) : (
                                        <div className="space-y-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                className="w-full"
                                                onClick={() =>
                                                    router.visit(
                                                        '/github/redirect',
                                                    )
                                                }
                                            >
                                                <Github className="mr-2 size-4" />
                                                Connect with GitHub
                                            </Button>
                                            <button
                                                type="button"
                                                className="text-xs text-muted-foreground underline"
                                                onClick={() =>
                                                    setUseManualToken(true)
                                                }
                                            >
                                                Enter token manually instead
                                            </button>
                                        </div>
                                    )
                                ) : (
                                    <Input
                                        id="token"
                                        name="token"
                                        type="password"
                                        placeholder="ghp_xxxxxxxxxxxx"
                                        required
                                    />
                                )}

                                <InputError message={errors.token} />
                            </div>

                            {type !== 'github' && (
                                <div className="grid gap-2">
                                    <Label htmlFor="base_url">
                                        Base URL (optional)
                                    </Label>
                                    <Input
                                        id="base_url"
                                        name="base_url"
                                        type="url"
                                        placeholder="https://gitlab.example.com"
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Only needed for self-hosted GitLab or
                                        Gitea instances.
                                    </p>
                                    <InputError message={errors.base_url} />
                                </div>
                            )}

                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving...' : 'Add Credential'}
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CredentialsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Credentials',
            href: '/credentials',
        },
        {
            title: 'Add Credential',
            href: '/credentials/create',
        },
    ],
};
