<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PackageController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Package::query();

        if ($request->filled('instructor_id')) {
            $query->where('instructor_id', (int) $request->input('instructor_id'));
        }

        if ($request->has('active')) {
            $query->where('active', filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN));
        }

        $packages = $query->orderBy('name')->get();

        return PackageResource::collection($packages);
    }

    public function store(StorePackageRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! isset($data['instructor_id'])) {
            $data['instructor_id'] = $request->user()->id;
        }

        $package = Package::create($data);

        return (new PackageResource($package))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Package $package): PackageResource
    {
        return new PackageResource($package);
    }

    public function update(UpdatePackageRequest $request, Package $package): PackageResource
    {
        $package->update($request->validated());

        return new PackageResource($package->fresh());
    }

    public function destroy(Package $package): JsonResponse
    {
        $package->delete();

        return response()->json(['message' => 'Package deleted successfully.']);
    }
}
