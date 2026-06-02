<?php

use App\Http\Controllers\ComposerController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GitHubOAuthController;
use App\Http\Controllers\InstructionsController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\RepositoryController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

// Composer V2 Repository API (per-repository, with optional auth)
Route::prefix('repo/{repository:slug}')
    ->middleware('repo.auth')
    ->group(function () {
        Route::get('packages.json', [ComposerController::class, 'packagesJson'])
            ->name('composer.packages');
        Route::get('p2/{vendor}/{package}.json', [ComposerController::class, 'packageMetadata'])
            ->name('composer.metadata')
            ->where('package', '[a-z0-9\-_.~]+');
        Route::get('dists/{vendor}/{package}/{version}/{ref}.zip', [ComposerController::class, 'dist'])
            ->name('composer.dist')
            ->where('ref', '[a-f0-9]+');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('instructions', [InstructionsController::class, 'index'])->name('instructions');

    Route::resource('repositories', RepositoryController::class);
    Route::post('repositories/{repository}/packages', [RepositoryController::class, 'attachPackage'])
        ->name('repositories.packages.attach');
    Route::delete('repositories/{repository}/packages/{package}', [RepositoryController::class, 'detachPackage'])
        ->name('repositories.packages.detach');

    Route::resource('credentials', CredentialController::class)->except(['show']);
    Route::get('github/redirect', [GitHubOAuthController::class, 'redirect'])->name('github.redirect');
    Route::get('github/callback', [GitHubOAuthController::class, 'callback'])->name('github.callback');

    Route::resource('packages', PackageController::class);
    Route::post('packages/{package}/sync', [PackageController::class, 'sync'])->name('packages.sync');
    Route::post('packages/{package}/repositories', [PackageController::class, 'attachRepository'])
        ->name('packages.repositories.attach');
    Route::delete('packages/{package}/repositories/{repository}', [PackageController::class, 'detachRepository'])
        ->name('packages.repositories.detach');
});

require __DIR__.'/settings.php';
