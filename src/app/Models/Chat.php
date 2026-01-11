<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = [
        'title',
        'is_group',
        'created_by',
    ];

    protected $casts = [
        'is_group' => 'boolean',
    ];

    protected $appends = [
        'notifications_enabled',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'notifications_enabled'])
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getNotificationsEnabledAttribute(): bool
    {
        $userId = auth()->id();

        if (!$userId) {
            return true;
        }

        $user = $this->relationLoaded('users')
            ? $this->users->firstWhere('id', $userId)
            : $this->users()->where('users.id', $userId)->first();

        if (!$user) {
            return true;
        }

        return (bool) ($user->pivot->notifications_enabled ?? true);
    }
}
