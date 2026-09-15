<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(BreakSession::class);
    }

    public function getTotalHoursAttribute()
    {
        if (!$this->check_out_time) {
            return 0;
        }
        
        $diff = $this->check_in_time->diffInMinutes($this->check_out_time);
        
        $breakMins = 0;
        foreach ($this->breaks as $break) {
            if ($break->break_end) {
                $breakMins += $break->break_start->diffInMinutes($break->break_end);
            }
        }
        
        return round(($diff - $breakMins) / 60, 2);
    }
}
