<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalContribution;
use App\Models\Transaction;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function backup(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'message' => 'Respaldo generado correctamente',
            'data' => [
                'user' => new UserResource($user),
                'categories' => Category::where('user_id', $user->id)->get(),
                'transactions' => Transaction::where('user_id', $user->id)->get(),
                'budgets' => Budget::where('user_id', $user->id)->get(),
                'goals' => Goal::where('user_id', $user->id)->get(),
                'contributions' => GoalContribution::where('user_id', $user->id)->get(),
            ],
        ], 200);
    }
}
