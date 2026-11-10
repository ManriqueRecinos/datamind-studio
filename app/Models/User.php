<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

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
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Comprueba si el usuario tiene un permiso específico o si es Super Admin.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        if (!$this->role || !$this->role->is_active) {
            return false;
        }

        // El Super Administrador posee acceso a todos los privilegios
        if ($this->role->slug === 'super-admin') {
            return true;
        }

        return $this->role->permissions->contains('slug', $permissionSlug);
    }

    /**
     * Helper para comprobar si el usuario pertenece a uno o más roles.
     */
    public function hasRole(string ...$roles): bool
    {
        if (!$this->role || !$this->role->is_active) {
            return false;
        }

        return in_array($this->role->slug, $roles, true);
    }
}
