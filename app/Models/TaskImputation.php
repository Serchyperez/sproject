<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskImputation extends Model
{
    protected $fillable = ['task_id', 'user_id', 'hours', 'date', 'description'];

    protected $casts = [
        'date' => 'date',
        'hours' => 'float',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
