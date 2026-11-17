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

    /**
     * Reports only move forward. Open may go straight to Resolved (e.g. a lost item that was found,
     * or a minor issue fixed on the spot). Staying in the same status is allowed (to update notes).
     */
    public function canMoveTo(self $next): bool
    {
        return $next === $this || match ($this) {
            self::Open => in_array($next, [self::UnderRepair, self::Resolved], true),
            self::UnderRepair => $next === self::Resolved,
            self::Resolved => false,
        };
    }
}
