<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// 'role' and 'is_active' are deliberately NOT fillable: a user cannot make themselves admin
// by adding a hidden "role" field to the register or profile form. Only admin code sets them explicitly.
#[Fillable(['name', 'email', 'password', 'id_number', 'department', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /** Reservations this user made as the borrower. */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** Damage reports this user filed (reported_by column). */
    public function damageReports(): HasMany
    {
        return $this->hasMany(DamageReport::class, 'reported_by');
    }

    /** Admin user list: search name/email/ID number, filter by role and active/inactive. */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn (Builder $q, string $term) => $q->where(fn (Builder $q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('id_number', 'like', "%{$term}%")))
            ->when($filters['role'] ?? null, fn (Builder $q, $role) => $q->where('role', $role))
            ->when(in_array($filters['active'] ?? null, ['1', '0'], true), fn (Builder $q) => $q->where('is_active', $filters['active'] === '1'));
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /** Used by the EnsureRole middleware: role:admin or role:admin,faculty */
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role->value, $roles, true);
    }
}
