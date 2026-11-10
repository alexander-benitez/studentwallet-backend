<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\GoalContribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoalController extends Controller
{
    public function index()
    {
        $goals = Goal::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $goals,
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'target_amount' => 'required|numeric|min:0.01',
            'deadline'      => 'nullable|date|after_or_equal:today',
        ]);

        $goal = Goal::create([
            'user_id'        => Auth::id(),
            'name'           => $validated['name'],
            'target_amount'  => $validated['target_amount'],
            'current_amount' => 0,
            'deadline'       => $validated['deadline'] ?? null,
            'status'         => 'active',
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $goal,
        ], 201);
    }

    public function show($id)
    {
        $goal = Goal::where('user_id', Auth::id())->find($id);

        if (! $goal) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Meta no encontrada',
            ], 404);
        }

        $goal->load('contributions');

        return response()->json([
            'status' => 'success',
            'data'   => $goal,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $goal = Goal::where('user_id', Auth::id())->find($id);

        if (! $goal) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Meta no encontrada',
            ], 404);
        }

        $validated = $request->validate([
            'name'          => 'sometimes|string|max:100',
            'target_amount' => 'sometimes|numeric|min:0.01',
            'deadline'      => 'sometimes|nullable|date',
        ]);

        $goal->update($validated);

        return response()->json([
            'status' => 'success',
            'data'   => $goal,
        ], 200);
    }

    public function destroy($id)
    {
        $goal = Goal::where('user_id', Auth::id())->find($id);

        if (! $goal) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Meta no encontrada',
            ], 404);
        }

        $goal->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Meta eliminada',
        ], 200);
    }

    public function addContribution(Request $request, $id)
    {
        $goal = Goal::where('user_id', Auth::id())->find($id);

        if (! $goal) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Meta no encontrada',
            ], 404);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note'   => 'nullable|string|max:500',
        ]);

        $contribution = GoalContribution::create([
            'goal_id' => $goal->id,
            'user_id' => Auth::id(),
            'amount'  => $validated['amount'],
            'note'    => $validated['note'] ?? null,
        ]);

        $goal->current_amount = $goal->current_amount + $validated['amount'];

        if ($goal->current_amount >= $goal->target_amount && $goal->status === 'active') {
            $goal->status = 'completed';
        }

        $goal->save();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'goal'         => $goal,
                'contribution' => $contribution,
            ],
        ], 201);
    }
}
