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

export default function RepositoriesCreate() {
    const [authType, setAuthType] = useState('none');

    return (
        <>
            <Head title="Create Repository" />

            <div className="mx-auto max-w-2xl space-y-6 p-4">
                <Heading
                    title="Create Repository"
                    description="Create a new Composer package repository"
                />

                <Form
                    action="/repositories"
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
                                    placeholder="My Private Repo"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    name="slug"
                                    placeholder="my-private-repo"
                                    required
                                    pattern="[a-z0-9-]+"
                                />
                                <p className="text-xs text-muted-foreground">
                                    URL-safe identifier. Used in the repository
                                    URL: /repo/{'<slug>'}/packages.json
                                </p>
                                <InputError message={errors.slug} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="auth_type">
                                    Authentication
                                </Label>
                                <Select
                                    name="auth_type"
                                    defaultValue="none"
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
                                            required
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
                                        required
                                    />
                                    <InputError message={errors.auth_token} />
                                </div>
                            )}

                            <Button type="submit" disabled={processing}>
                                {processing
                                    ? 'Creating...'
                                    : 'Create Repository'}
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

RepositoriesCreate.layout = {
    breadcrumbs: [
        {
            title: 'Repositories',
            href: '/repositories',
        },
        {
            title: 'Create Repository',
            href: '/repositories/create',
        },
    ],
};
