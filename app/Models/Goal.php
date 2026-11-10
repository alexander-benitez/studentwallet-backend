<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'target_amount',
        'current_amount',
        'deadline',
        'status',
    ];

    protected $casts = [
        'deadline'       => 'date',
        'target_amount'  => 'float',
        'current_amount' => 'float',
    ];

    protected $appends = [
        'progress_percentage',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contributions()
    {
        return $this->hasMany(GoalContribution::class);
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->target_amount > 0) {
            return min(100, round($this->current_amount / $this->target_amount * 100, 2));
        }

        return 0;
    }
}
