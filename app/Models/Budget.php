<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'month',
        'year',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'month'  => 'integer',
        'year'   => 'integer',
    ];

    protected $appends = [
        'spent',
        'percentage',
        'alert',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getSpentAttribute()
    {
        $start = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return (float) Transaction::where('user_id', $this->user_id)
            ->where('category_id', $this->category_id)
            ->where('type', 'gasto')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');
    }

    public function getPercentageAttribute()
    {
        if ((float) $this->amount <= 0) {
            return 0;
        }

        return round($this->spent / (float) $this->amount * 100, 2);
    }

    public function getAlertAttribute()
    {
        if ($this->percentage >= 100) {
            return 'limite_excedido';
        }

        if ($this->percentage >= 80) {
            return 'advertencia';
        }

        return 'normal';
    }
}
