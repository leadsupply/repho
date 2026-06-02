<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GitHubOAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('github_oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('services.github.client_id'),
            'redirect_uri' => route('github.callback'),
            'scope' => 'repo',
            'state' => $state,
        ]);

        return redirect("https://github.com/login/oauth/authorize?{$query}");
    }

    public function callback(Request $request): RedirectResponse
    {
        $storedState = $request->session()->pull('github_oauth_state');

        if (! $storedState || $request->input('state') !== $storedState) {
            return redirect()->route('credentials.create')
                ->with('error', 'Invalid OAuth state. Please try again.');
        }

        if ($request->has('error')) {
            return redirect()->route('credentials.create')
                ->with('error', 'GitHub authorization was denied.');
        }

        $response = Http::acceptJson()->post('https://github.com/login/oauth/access_token', [
            'client_id' => config('services.github.client_id'),
            'client_secret' => config('services.github.client_secret'),
            'code' => $request->input('code'),
        ]);

        $token = $response->json('access_token');

        if (! $token) {
            return redirect()->route('credentials.create')
                ->with('error', 'Failed to obtain token from GitHub.');
        }

        return redirect()->route('credentials.create')
            ->with('github_token', $token);
    }
}
