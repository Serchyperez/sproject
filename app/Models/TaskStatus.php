<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskStatus extends Model
{
    protected $fillable = [
        'project_id', 'name', 'color', 'order', 'wip_limit', 'is_default', 'is_done',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_done' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
