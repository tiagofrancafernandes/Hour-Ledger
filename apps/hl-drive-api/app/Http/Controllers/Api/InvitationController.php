<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Mail\SendInvitationMail;
use App\Models\Invitation;
use App\Models\InstructorStudentLink;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    /**
     * Create a new invitation.
     *
     * POST /api/invitations
     *
     * @param CreateInvitationRequest $request
     *
     * @return JsonResponse
     */
    public function store(CreateInvitationRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(
                ['message' => 'Unauthorized'],
                401
            );
        }

        $tenantId = (int)$request->input('tenant_id');
        $recipientId = $request->input('recipient_id');
        $recipientEmail = $request->input('email');

        // Validate tenant access
        if (!$user->hasAccessToTenant($tenantId)) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        // Resolve recipient (can be existing student or email)
        $studentId = null;

        if ($recipientId) {
            $student = User::find($recipientId);

            if ($student === null) {
                return response()->json(
                    ['message' => 'Recipient user not found'],
                    422
                );
            }

            if (!$student->hasAccessToTenant($tenantId)) {
                return response()->json(
                    ['message' => 'Recipient is not part of this tenant'],
                    422
                );
            }

            $studentId = $recipientId;
            $recipientEmail = null;
        }

        // Check for duplicate pending invitation
        $existingInvitation = Invitation::forTenant($tenantId)
            ->byInstructor($user->id)
            ->pending();

        if ($studentId) {
            $existingInvitation = $existingInvitation->where('student_id', $studentId);
        } else {
            $existingInvitation = $existingInvitation->where('email', $recipientEmail);
        }

        if ($existingInvitation->exists()) {
            return response()->json(
                ['message' => 'A pending invitation already exists for this recipient'],
                409
            );
        }

        // Generate secure token
        $token = hash('sha256', Str::random(32) . now()->timestamp);

        // Create invitation
        $invitation = Invitation::create([
            'tenant_id' => $tenantId,
            'instructor_id' => $user->id,
            'student_id' => $studentId,
            'email' => $recipientEmail,
            'status' => InvitationStatus::PENDING,
            'token' => $token,
            'expires_at' => now()->addDays(7),
        ]);

        // Send email
        if ($recipientEmail) {
            $studentName = null;

            if ($studentId) {
                $student = User::find($studentId);
                $studentName = $student?->name;
            }

            $tenant = Tenant::find($tenantId);
            $tenantName = $tenant?->name ?? 'Hour Ledger';

            Mail::send(new SendInvitationMail(
                recipientEmail: $recipientEmail,
                studentName: $studentName,
                instructorName: $user->name,
                token: $token,
                tenantName: $tenantName,
            ));
        }

        return response()->json(
            new InvitationResource($invitation),
            201
        );
    }

    /**
     * List invitations.
     *
     * GET /api/invitations
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

        $query = Invitation::forTenant($tenantId);

        // Filter by status if provided
        $status = $request->input('status');

        if ($status) {
            $query = $query->byStatus($status);
        }

        // Filter by instructor or student
        $role = $request->input('role');

        if ($role === 'instructor') {
            $query = $query->byInstructor($user->id);
        } elseif ($role === 'student') {
            $query = $query->where(function ($q) use ($user) {
                $q->where('student_id', $user->id)
                    ->orWhere('email', $user->email);
            });
        }

        $invitations = $query
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json(
            InvitationResource::collection($invitations)
        );
    }

    /**
     * Show invitation details.
     *
     * GET /api/invitations/{id}
     *
     * @param Request $request
     * @param Invitation $invitation
     *
     * @return JsonResponse
     */
    public function show(Request $request, Invitation $invitation): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(
                ['message' => 'Unauthorized'],
                401
            );
        }

        if (!$this->canViewInvitation($user, $invitation)) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        return response()->json(
            new InvitationResource($invitation)
        );
    }

    /**
     * Accept invitation.
     *
     * POST /api/invitations/{id}/accept
     *
     * @param Request $request
     * @param Invitation $invitation
     *
     * @return JsonResponse
     */
    public function accept(Request $request, Invitation $invitation): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(
                ['message' => 'Unauthorized'],
                401
            );
        }

        if (!$invitation->isResolvable()) {
            if ($invitation->isExpired()) {
                return response()->json(
                    ['message' => 'Invitation has expired'],
                    410
                );
            }

            return response()->json(
                [
                    'message' => 'Invitation cannot be accepted',
                    'status' => $invitation->status->value,
                ],
                409
            );
        }

        // Validate that user is the intended recipient
        if ($invitation->student_id && $invitation->student_id !== $user->id) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        if ($invitation->email && $invitation->email !== $user->email) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        // Accept invitation
        $invitation->accept();

        // Create link
        InstructorStudentLink::create([
            'tenant_id' => $invitation->tenant_id,
            'instructor_id' => $invitation->instructor_id,
            'student_id' => $user->id,
            'invitation_id' => $invitation->id,
            'status' => 'ACTIVE',
            'access_level' => 'FULL',
        ]);

        return response()->json(
            new InvitationResource($invitation->refresh())
        );
    }

    /**
     * Reject invitation.
     *
     * POST /api/invitations/{id}/reject
     *
     * @param Request $request
     * @param Invitation $invitation
     *
     * @return JsonResponse
     */
    public function reject(Request $request, Invitation $invitation): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(
                ['message' => 'Unauthorized'],
                401
            );
        }

        if (!$invitation->isResolvable()) {
            if ($invitation->isExpired()) {
                return response()->json(
                    ['message' => 'Invitation has expired'],
                    410
                );
            }

            return response()->json(
                [
                    'message' => 'Invitation cannot be rejected',
                    'status' => $invitation->status->value,
                ],
                409
            );
        }

        // Validate that user is the intended recipient
        if ($invitation->student_id && $invitation->student_id !== $user->id) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        if ($invitation->email && $invitation->email !== $user->email) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        // Reject invitation
        $invitation->reject();

        return response()->json(
            new InvitationResource($invitation)
        );
    }

    /**
     * Resend invitation email.
     *
     * POST /api/invitations/{id}/resend
     *
     * @param Request $request
     * @param Invitation $invitation
     *
     * @return JsonResponse
     */
    public function resend(Request $request, Invitation $invitation): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(
                ['message' => 'Unauthorized'],
                401
            );
        }

        if (!$user->hasAccessToTenant($invitation->tenant_id)) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        if ($invitation->instructor_id !== $user->id) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        if (!$invitation->isPending()) {
            return response()->json(
                [
                    'message' => 'Can only resend pending invitations',
                    'status' => $invitation->status->value,
                ],
                409
            );
        }

        if ($invitation->isExpired()) {
            return response()->json(
                ['message' => 'Invitation has expired'],
                410
            );
        }

        if (!$invitation->email) {
            return response()->json(
                ['message' => 'Cannot resend invitation without email'],
                422
            );
        }

        $tenant = Tenant::find($invitation->tenant_id);
        $tenantName = $tenant?->name ?? 'Hour Ledger';

        Mail::send(new SendInvitationMail(
            recipientEmail: $invitation->email,
            studentName: $invitation->student?->name,
            instructorName: $user->name,
            token: $invitation->token,
            tenantName: $tenantName,
        ));

        return response()->json(
            new InvitationResource($invitation)
        );
    }

    /**
     * Delete/revoke invitation.
     *
     * DELETE /api/invitations/{id}
     *
     * @param Request $request
     * @param Invitation $invitation
     *
     * @return JsonResponse
     */
    public function destroy(Request $request, Invitation $invitation): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(
                ['message' => 'Unauthorized'],
                401
            );
        }

        if (!$user->hasAccessToTenant($invitation->tenant_id)) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        if ($invitation->instructor_id !== $user->id) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        $invitation->delete();

        return response()->json(
            null,
            204
        );
    }

    /**
     * Check if user can view invitation.
     *
     * @param User $user
     * @param Invitation $invitation
     *
     * @return bool
     */
    private function canViewInvitation(User $user, Invitation $invitation): bool
    {
        // Instructor can view their own invitations
        if ($invitation->instructor_id === $user->id) {
            return $user->hasAccessToTenant($invitation->tenant_id);
        }

        // Student can view invitations sent to them
        if ($invitation->student_id === $user->id) {
            return $user->hasAccessToTenant($invitation->tenant_id);
        }

        // User with email matching can view invitation sent to their email
        if ($invitation->email === $user->email) {
            return $user->hasAccessToTenant($invitation->tenant_id);
        }

        return false;
    }
}
