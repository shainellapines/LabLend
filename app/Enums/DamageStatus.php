<?php

namespace App\Enums;

/** Damage report flow (FR-D2): open -> under_repair -> resolved. */
enum DamageStatus: string
{
    case Open = 'open';
    case UnderRepair = 'under_repair';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::UnderRepair => 'Under repair',
            self::Resolved => 'Resolved',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'red',
            self::UnderRepair => 'amber',
            self::Resolved => 'green',
        };
    }
}
