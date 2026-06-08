<?php

namespace App\Http\Controllers;

use App\Services\ComposerProxyService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ComposerProxyController extends Controller
{
    public function __construct(
        private ComposerProxyService $proxyService,
    ) {}

    public function packagesJson(): JsonResponse
    {
        $data = $this->proxyService->getPackagesJson();

        if (empty($data)) {
            abort(Response::HTTP_BAD_GATEWAY);
        }

        return response()->json($data);
    }

    public function packageMetadata(string $vendor, string $packageName): JsonResponse
    {
        if (str_ends_with($packageName, '~dev')) {
            $packageName = substr($packageName, 0, -4);
        }

        $data = $this->proxyService->getPackageMetadata($vendor, $packageName);

        if ($data === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->json($data);
    }

    public function dist(string $encodedUrl): BinaryFileResponse
    {
        $path = $this->proxyService->getDistFile($encodedUrl);

        if ($path === null) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->download($path, 'package.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }
}
