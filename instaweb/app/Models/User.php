<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'grace_period_ends_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'grace_period_ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function isSubscribed(): bool
    {
        return $this->subscribed('default');
    }

    public function inGracePeriod(): bool
    {
        return $this->grace_period_ends_at && $this->grace_period_ends_at->isFuture();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
