<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Staff account or ally (a person who lends cars). Uses the existing `users`
 * table, so current admins keep their email and password (bcrypt hashes are compatible).
 * Allies are not staff: they sign in to the /aliados panel only.
 */
#[Fillable(['first_name', 'last_name', 'username', 'email', 'phone', 'password', 'super_user', 'role_id', 'is_ally'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'password' => 'hashed',
            'super_user' => 'boolean',
            'is_ally' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Cars this ally lends to the rent-a-car. */
    public function ownedCars(): HasMany
    {
        return $this->hasMany(Car::class, 'owner_id');
    }

    /** Super users and users without a role keep full access; otherwise the role decides. */
    public function canAccessModule(string $module): bool
    {
        if ($this->super_user || ! $this->role_id) {
            return true;
        }

        return in_array($module, $this->role?->modules ?? [], true);
    }

    /** Allies only enter the /aliados panel; staff enter the admin panel. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'aliados' ? $this->is_ally : ! $this->is_ally;
    }

    public function getFilamentName(): string
    {
        return trim("{$this->first_name} {$this->last_name}") ?: ($this->username ?: $this->email);
    }
}
