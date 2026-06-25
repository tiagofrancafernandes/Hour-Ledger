<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SwitchInstructorRequest;
use App\Http\Resources\InstructorStudentLinkResource;
use App\Models\InstructorStudentLink;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstructorStudentLinkController extends Controller
{
    /**
     * List instructor-student links.
     *
     * GET /api/instructor-links
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(
                ['message' => 'Unauthorized'],
                401
            );
        }

        $tenantId = (int)$request->input('tenant_id');

        if (!$user->hasAccessToTenant($tenantId)) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        $query = InstructorStudentLink::forTenant($tenantId)->active();

        $role = $request->input('role');

        if ($role === 'instructor') {
            $query = $query->byInstructor($user->id);
        } elseif ($role === 'student') {
            $query = $query->byStudent($user->id);
        }

        $links = $query
            ->with(['instructor', 'student', 'invitation'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(
            InstructorStudentLinkResource::collection($links)
        );
    }

    /**
     * Show link details.
     *
     * GET /api/instructor-links/{id}
     *
     * @param Request $request
     * @param InstructorStudentLink $link
     *
     * @return JsonResponse
     */
    public function show(Request $request, InstructorStudentLink $link): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(
                ['message' => 'Unauthorized'],
                401
            );
        }

        if (!$user->canAccessTenant($link->tenant_id)) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        if (!$this->canViewLink($user, $link)) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        return response()->json(
            new InstructorStudentLinkResource($link->load(['instructor', 'student', 'invitation']))
        );
    }

    /**
     * Revoke/delete a link.
     *
     * DELETE /api/instructor-links/{id}
     *
     * @param Request $request
     * @param InstructorStudentLink $link
     *
     * @return JsonResponse
     */
    public function destroy(Request $request, InstructorStudentLink $link): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(
                ['message' => 'Unauthorized'],
                401
            );
        }

        if (!$user->canAccessTenant($link->tenant_id)) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        // Only instructor or student (the link participants) can revoke
        if ($link->instructor_id !== $user->id && $link->student_id !== $user->id) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        $link->revoke();

        return response()->json(
            null,
            204
        );
    }

    /**
     * Get current active instructor for a student.
     *
     * GET /api/my-instructor
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getMyInstructor(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(
                ['message' => 'Unauthorized'],
                401
            );
        }

        $tenantId = (int)$request->input('tenant_id');

        if (!$user->hasAccessToTenant($tenantId)) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        // Get active instructor for this student
        $link = InstructorStudentLink::forTenant($tenantId)
            ->byStudent($user->id)
            ->active()
            ->first();

        if (!$link) {
            return response()->json(
                ['message' => 'No active instructor link found'],
                404
            );
        }

        return response()->json(
            new InstructorStudentLinkResource($link->load(['instructor', 'student', 'invitation']))
        );
    }

    /**
     * Switch active instructor for a student.
     *
     * POST /api/my-instructor
     *
     * @param SwitchInstructorRequest $request
     *
     * @return JsonResponse
     */
    public function switchMyInstructor(SwitchInstructorRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(
                ['message' => 'Unauthorized'],
                401
            );
        }

        $tenantId = (int)$request->input('tenant_id');
        $instructorId = (int)$request->input('instructor_id');

        if (!$user->hasAccessToTenant($tenantId)) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        // Verify instructor exists in this tenant
        $instructor = User::find($instructorId);

        if ($instructor === null) {
            return response()->json(
                ['message' => 'Instructor not found'],
                404
            );
        }

        if (!$instructor->canAccessTenant($tenantId)) {
            return response()->json(
                ['message' => 'Instructor is not part of this tenant'],
                422
            );
        }

        // Find active link for this student with this instructor
        $link = InstructorStudentLink::forTenant($tenantId)
            ->byStudent($user->id)
            ->byInstructor($instructorId)
            ->active()
            ->first();

        if (!$link) {
            return response()->json(
                ['message' => 'No active link found with this instructor'],
                404
            );
        }

        // Update user's active instructor
        $user->update([
            'active_instructor_id' => $instructorId,
        ]);

        return response()->json(
            new InstructorStudentLinkResource($link->load(['instructor', 'student', 'invitation']))
        );
    }

    /**
     * Check if user can view link.
     *
     * @param User $user
     * @param InstructorStudentLink $link
     *
     * @return bool
     */
    private function canViewLink(User $user, InstructorStudentLink $link): bool
    {
        // Instructor can view links with their students
        if ($link->instructor_id === $user->id) {
            return true;
        }

        // Student can view links with their instructors
        if ($link->student_id === $user->id) {
            return true;
        }

        return false;
    }
}
