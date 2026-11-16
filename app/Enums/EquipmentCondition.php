<?php

namespace App\Enums;

/**
 * Physical condition of an item.
 * equipment.condition and condition_out use good/fair/poor.
 * condition_in (at return) may also be "damaged", which triggers a damage report (FR-R6).
 */
enum EquipmentCondition: string
{
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';
    case Damaged = 'damaged';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'Good',
            self::Fair => 'Fair',
            self::Poor => 'Poor',
            self::Damaged => 'Damaged',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Good => 'green',
            self::Fair => 'blue',
            self::Poor => 'amber',
            self::Damaged => 'red',
        };
    }

    /** Conditions allowed for the equipment record itself and for condition_out (no "damaged"). */
    public static function forEquipment(): array
    {
        return [self::Good, self::Fair, self::Poor];
    }
}
