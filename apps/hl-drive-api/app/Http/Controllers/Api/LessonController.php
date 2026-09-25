<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteLessonRequest;
use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Wallet;
use App\Services\LedgerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LessonController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Lesson::with(['instructor', 'student', 'package']);

        if ($request->filled('student_id')) {
            $query->where('student_id', (int) $request->input('student_id'));
        }

        if ($request->filled('instructor_id')) {
            $query->where('instructor_id', (int) $request->input('instructor_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        $lessons = $query->orderBy('scheduled_at', 'desc')->get();

        return LessonResource::collection($lessons);
    }

    public function byStudent(User $student): AnonymousResourceCollection
    {
        $lessons = Lesson::with(['instructor', 'package'])
            ->where('student_id', $student->id)
            ->orderBy('scheduled_at', 'desc')
            ->get();

        return LessonResource::collection($lessons);
    }

    public function byInstructor(User $instructor): AnonymousResourceCollection
    {
        $lessons = Lesson::with(['student', 'package'])
            ->where('instructor_id', $instructor->id)
            ->orderBy('scheduled_at', 'desc')
            ->get();

        return LessonResource::collection($lessons);
    }

    public function store(StoreLessonRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['instructor_id'])) {
            $data['instructor_id'] = $request->user()->id;
        }

        if (empty($data['wallet_id'])) {
            $student = User::find($data['student_id']);

            if ($student && $student->customer_id) {
                $wallet = Wallet::where('client_id', $student->customer_id)->first();

                if ($wallet) {
                    $data['wallet_id'] = $wallet->id;
                }
            }
        }

        $data['status'] = 'scheduled';

        $lesson = Lesson::create($data);
        $lesson->load(['instructor', 'student', 'package']);

        return (new LessonResource($lesson))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Lesson $lesson): LessonResource
    {
        $lesson->load(['instructor', 'student', 'package']);

        return new LessonResource($lesson);
    }

    public function update(UpdateLessonRequest $request, Lesson $lesson): JsonResponse|LessonResource
    {
        if ($lesson->status === 'completed') {
            return response()->json(
                ['message' => 'Cannot update a completed lesson.'],
                422
            );
        }

        $lesson->update($request->validated());
        $lesson->load(['instructor', 'student', 'package']);

        return new LessonResource($lesson);
    }

    public function complete(
        CompleteLessonRequest $request,
        Lesson $lesson,
        LedgerService $ledgerService
    ): JsonResponse|LessonResource {
        if ($lesson->status === 'completed') {
            return response()->json(
                ['message' => 'Lesson is already completed.'],
                422
            );
        }

        $hoursConsumed = $request->input('hours_consumed');

        if ($hoursConsumed === null) {
            $hoursConsumed = round($lesson->duration_minutes / 60, 2);
        }
        $hoursConsumed = (float) $hoursConsumed;

        $walletId = $request->input('wallet_id') ?? $lesson->wallet_id;
        $wallet = null;

        if ($walletId) {
            $wallet = Wallet::find($walletId);
        }

        if (! $wallet) {
            $student = $lesson->student;

            if ($student && $student->customer_id) {
                $wallet = Wallet::where('client_id', $student->customer_id)->first();
            }
        }

        $ledgerEntryId = null;

        if ($wallet) {
            $scheduledDate = $lesson->scheduled_at instanceof Carbon
                ? $lesson->scheduled_at->format('d/m/Y H:i')
                : (string) $lesson->scheduled_at;

            $entry = $ledgerService->addDebit($wallet, [
                'hours' => $hoursConsumed,
                'title' => "Aula de Direção - {$scheduledDate}",
                'description' => $request->input('notes') ?? $lesson->notes ?? 'Aula prática realizada.',
                'reference_date' => now()->toIso8601String(),
            ]);

            $ledgerEntryId = $entry->id;
            $lesson->wallet_id = $wallet->id;
        }

        $lesson->status = 'completed';
        $lesson->completed_at = now();
        $lesson->hours_consumed = $hoursConsumed;
        $lesson->ledger_entry_id = $ledgerEntryId;

        if ($request->filled('notes')) {
            $lesson->notes = (string) $request->input('notes');
        }

        $lesson->save();
        $lesson->load(['instructor', 'student', 'package']);

        return new LessonResource($lesson);
    }

    public function cancel(Lesson $lesson): JsonResponse|LessonResource
    {
        if ($lesson->status === 'cancelled') {
            return response()->json(
                ['message' => 'Lesson is already cancelled.'],
                422
            );
        }

        $lesson->status = 'cancelled';
        $lesson->cancelled_at = now();
        $lesson->save();

        return new LessonResource($lesson);
    }

    public function destroy(Lesson $lesson): JsonResponse
    {
        $lesson->delete();

        return response()->json(['message' => 'Lesson deleted successfully.']);
    }
}
