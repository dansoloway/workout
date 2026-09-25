<?php

namespace App\Http\Controllers;

use App\Enums\WorkoutStatus;
use App\Http\Requests\UpdateWorkoutItemRequest;
use App\Models\WorkoutItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class WorkoutItemController extends Controller
{
    public function update(UpdateWorkoutItemRequest $request, WorkoutItem $workoutItem): JsonResponse|RedirectResponse
    {
        $completed = $request->boolean('completed');

        $counts = DB::transaction(function () use ($workoutItem, $completed) {
            $workoutItem->update([
                'completed' => $completed,
                'completed_at' => $completed ? now() : null,
            ]);

            return $workoutItem->workout->syncStatus();
        });

        $workout = $workoutItem->workout->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'item' => [
                    'id' => $workoutItem->id,
                    'completed' => $workoutItem->completed,
                ],
                'workout' => [
                    'id' => $workout->id,
                    'status' => $workout->status->value,
                    'completed' => $workout->status === WorkoutStatus::Completed,
                    'done' => $counts['done'],
                    'total' => $counts['total'],
                ],
            ]);
        }

        return back();
    }
}
