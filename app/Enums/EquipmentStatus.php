<?php

namespace App\Enums;

/**
 * Stored status of an item (FR-E6). Only "available" items can be reserved.
 * "Borrowed" / "Reserved" are NOT stored here: they are computed from reservations.
 */
enum EquipmentStatus: string
{
    case Available = 'available';
    case UnderMaintenance = 'under_maintenance';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::UnderMaintenance => 'Under maintenance',
            self::Retired => 'Retired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'green',
            self::UnderMaintenance => 'amber',
            self::Retired => 'gray',
        };
    }
}
