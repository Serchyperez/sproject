<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Project extends Model
{
    protected $fillable = [
        'owner_id', 'name', 'slug', 'description', 'methodology',
        'status', 'color', 'cover_image', 'start_date', 'end_date',
        'allow_self_assign',
    ];

    protected $casts = [
        'start_date'       => 'date',
        'end_date'         => 'date',
        'allow_self_assign' => 'boolean',
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

    public function labels()
    {
        return $this->hasMany(Label::class);
    }

    public function monthClosings()
    {
        return $this->hasMany(MonthClosing::class);
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id));
        });
    }
}
