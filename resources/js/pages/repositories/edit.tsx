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
import type { Repository } from '@/types/package';

type PageProps = {
    repository: Repository;
};

export default function RepositoriesEdit({ repository }: PageProps) {
    const [authType, setAuthType] = useState(repository.auth_type);

    return (
        <>
            <Head title={`Edit ${repository.name}`} />

            <div className="mx-auto max-w-2xl space-y-6 p-4">
                <Heading
                    title={`Edit ${repository.name}`}
                    description="Update repository settings"
                />

                <Form
                    action={`/repositories/${repository.id}`}
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
                                    defaultValue={repository.name}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    name="slug"
                                    defaultValue={repository.slug}
                                    required
                                    pattern="[a-z0-9-]+"
                                />
                                <InputError message={errors.slug} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="auth_type">
                                    Authentication
                                </Label>
                                <Select
                                    name="auth_type"
                                    defaultValue={repository.auth_type}
                                    onValueChange={setAuthType}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            None (Public)
                                        </SelectItem>
                                        <SelectItem value="basic">
                                            Basic Auth
                                        </SelectItem>
                                        <SelectItem value="token">
                                            Token
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.auth_type} />
                            </div>

                            {authType === 'basic' && (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="auth_username">
                                            Username
                                        </Label>
                                        <Input
                                            id="auth_username"
                                            name="auth_username"
                                            defaultValue={
                                                repository.auth_username ?? ''
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.auth_username}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="auth_password">
                                            Password
                                        </Label>
                                        <Input
                                            id="auth_password"
                                            name="auth_password"
                                            type="password"
                                            placeholder="Leave blank to keep current"
                                        />
                                        <InputError
                                            message={errors.auth_password}
                                        />
                                    </div>
                                </>
                            )}

                            {authType === 'token' && (
                                <div className="grid gap-2">
                                    <Label htmlFor="auth_token">Token</Label>
                                    <Input
                                        id="auth_token"
                                        name="auth_token"
                                        type="password"
                                        placeholder="Leave blank to keep current"
                                    />
                                    <InputError message={errors.auth_token} />
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

RepositoriesEdit.layout = {
    breadcrumbs: [
        {
            title: 'Repositories',
            href: '/repositories',
        },
        {
            title: 'Edit Repository',
            href: '#',
        },
    ],
};
