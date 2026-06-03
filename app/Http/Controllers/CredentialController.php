<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCredentialRequest;
use App\Http\Requests\UpdateCredentialRequest;
use App\Models\Credential;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CredentialController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('credentials/index', [
            'credentials' => Credential::query()
                ->withCount('packages')
                ->latest()
                ->get()
                ->map(fn (Credential $credential) => [
                    'id' => $credential->id,
                    'name' => $credential->name,
                    'type' => $credential->type->value,
                    'base_url' => $credential->base_url,
                    'packages_count' => $credential->packages_count,
                    'created_at' => $credential->created_at->toDateTimeString(),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('credentials/create', [
            'githubToken' => session()->pull('github_token'),
            'githubOAuthEnabled' => filled(config('services.github.client_id')),
        ]);
    }

    public function store(StoreCredentialRequest $request): RedirectResponse
    {
        Credential::create($request->validated());

        return redirect()->route('credentials.index')
            ->with('success', 'Credential created successfully.');
    }

    public function edit(Credential $credential): Response
    {
        return Inertia::render('credentials/edit', [
            'credential' => [
                'id' => $credential->id,
                'name' => $credential->name,
                'type' => $credential->type->value,
                'base_url' => $credential->base_url,
            ],
        ]);
    }

    public function update(UpdateCredentialRequest $request, Credential $credential): RedirectResponse
    {
        $data = $request->safe()->except(['token']);

        if ($request->filled('token')) {
            $data['token'] = $request->input('token');
        }

        $credential->update($data);

        return redirect()->route('credentials.index')
            ->with('success', 'Credential updated successfully.');
    }

    public function destroy(Credential $credential): RedirectResponse
    {
        $credential->delete();

        return redirect()->route('credentials.index')
            ->with('success', 'Credential deleted successfully.');
    }
}
