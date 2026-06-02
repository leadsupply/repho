<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use App\Models\DownloadStatistic;
use App\Models\Package;
use App\Models\Repository;
use App\Models\SecurityAdvisory;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $startDate = now()->subDays(29)->startOfDay();

        $downloads = DownloadStatistic::query()
            ->where('date', '>=', $startDate)
            ->select(DB::raw('date, SUM(downloads) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->all();

        $chartData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $chartData[] = [
                'date' => $date,
                'downloads' => (int) ($downloads[$date] ?? 0),
            ];
        }

        $advisories = SecurityAdvisory::query()
            ->with('package:id,name')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn (SecurityAdvisory $advisory) => [
                'id' => $advisory->id,
                'package_name' => $advisory->package->name,
                'title' => $advisory->title,
                'link' => $advisory->link,
                'cve' => $advisory->cve,
                'severity' => $advisory->severity,
                'reported_at' => $advisory->reported_at?->toDateTimeString(),
            ]);

        return Inertia::render('dashboard', [
            'repositoriesCount' => Repository::count(),
            'packagesCount' => Package::count(),
            'credentialsCount' => Credential::count(),
            'downloadStats' => $chartData,
            'advisories' => $advisories,
        ]);
    }
}
