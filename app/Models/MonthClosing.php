<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthClosing extends Model
{
    protected $fillable = [
        'project_id', 'year', 'month', 'is_closed',
        'closed_by', 'closed_at', 'reopened_by', 'reopened_at',
    ];

    protected $casts = [
        'is_closed'   => 'boolean',
        'closed_at'   => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopenedBy()
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public static function isClosed(int $projectId, int $year, int $month): bool
    {
        return static::where('project_id', $projectId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('is_closed', true)
            ->exists();
    }
}
