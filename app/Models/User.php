<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    public const ROLE_CUSTOMER = 0;
    public const ROLE_ADMIN = 1;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'integer',
            'is_admin' => 'boolean',
        ];
    }

    public function hasRole(string $role): bool
    {
        return match ($role) {
            'admin' => (bool) $this->is_admin || (int) $this->role === self::ROLE_ADMIN,
            'customer' => ! $this->isAdmin(),
            default => false,
        };
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin || (int) $this->role === self::ROLE_ADMIN;
    }
}
