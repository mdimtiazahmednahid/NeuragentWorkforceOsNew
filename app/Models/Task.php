<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected static function booted()
    {
        static::addGlobalScope('role_access', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                if (!in_array($user->role, ['SUPER_ADMIN', 'ADMIN', 'MANAGER', 'LEAD'])) {
                    $builder->where(function ($query) use ($user) {
                        $query->where('assignee_id', $user->id)
                              ->orWhere('created_by', $user->id)
                              ->orWhere('reviewer_id', $user->id);
                    });
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'start_date' => 'date',
            'completed_date' => 'datetime',
            'dependencies' => 'array',
        ];
    }
    
    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }
    
    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function getTaskIdAttribute(): string
    {
        $prefix = 'TSK';
        
        if ($this->project_id && $this->project) {
            $cleaned = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $this->project->name));
            $prefix = substr($cleaned, 0, 3);
            if (strlen($prefix) < 3) {
                $prefix = str_pad($prefix, 3, 'X');
            }
        }
        
        return $prefix . '-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }
}
