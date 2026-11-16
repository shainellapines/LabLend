<?php

namespace App\Enums;

/**
 * The three user roles. Stored as a string in users.role ('admin', 'faculty', 'student').
 * The User model casts that column to this enum, so $user->role is UserRole::Admin, not a plain string.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Faculty = 'faculty';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Faculty => 'Faculty',
            self::Student => 'Student',
        };
    }

    /** Badge color used by <x-status-badge>. */
    public function color(): string
    {
        return match ($this) {
            self::Admin => 'indigo',
            self::Faculty => 'blue',
            self::Student => 'gray',
        };
    }
}
