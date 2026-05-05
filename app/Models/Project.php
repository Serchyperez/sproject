<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Project extends Model
{
    protected $fillable = [
        'owner_id', 'name', 'slug', 'description', 'methodology',
        'status', 'color', 'cover_image', 'start_date', 'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Project $project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function projectMembers()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class);
    }

    public function sprints()
    {
        return $this->hasMany(Sprint::class);
    }

    public function taskStatuses()
    {
        return $this->hasMany(TaskStatus::class)->orderBy('order');
    }

    public function activeSprint()
    {
        return $this->hasOne(Sprint::class)->where('status', 'active');
    }
}
