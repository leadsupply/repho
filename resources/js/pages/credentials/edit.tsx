import { Form, Head } from '@inertiajs/react';
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
import type { Credential } from '@/types/package';

type PageProps = {
    credential: Pick<Credential, 'id' | 'name' | 'type' | 'base_url'>;
};

export default function CredentialsEdit({ credential }: PageProps) {
    const [type, setType] = useState<string>(credential.type);

    return (
        <>
            <Head title={`Edit ${credential.name}`} />

            <div className="mx-auto max-w-2xl space-y-6 p-4">
                <Heading
                    title={`Edit ${credential.name}`}
                    description="Update credential settings"
                />

                <Form
                    action={`/credentials/${credential.id}`}
                    method="put"
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={credential.name}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="type">Type</Label>
                                <Select
                                    name="type"
                                    defaultValue={credential.type}
                                    onValueChange={setType}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="github">
                                            GitHub
                                        </SelectItem>
                                        <SelectItem value="gitlab">
                                            GitLab
                                        </SelectItem>
                                        <SelectItem value="git">Git</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.type} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="token">Token</Label>
                                <Input
                                    id="token"
                                    name="token"
                                    type="password"
                                    placeholder="Leave blank to keep current"
                                />
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
                                        defaultValue={
                                            credential.base_url ?? ''
                                        }
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
                                {processing ? 'Saving...' : 'Save Changes'}
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CredentialsEdit.layout = {
    breadcrumbs: [
        {
            title: 'Credentials',
            href: '/credentials',
        },
        {
            title: 'Edit Credential',
            href: '#',
        },
    ],
};
