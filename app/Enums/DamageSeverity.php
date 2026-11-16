<?php

namespace App\Enums;

/**
 * FR-D3: a "major" report puts the item under maintenance; a "lost" report retires it.
 */
enum DamageSeverity: string
{
    case Minor = 'minor';
    case Major = 'major';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Minor => 'Minor',
            self::Major => 'Major',
            self::Lost => 'Lost',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Minor => 'amber',
            self::Major => 'red',
            self::Lost => 'red-solid',
        };
    }
}
