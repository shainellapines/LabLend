<?php

namespace App\Enums;

enum RoomType: string
{
    case ComputerLab = 'computer_lab';
    case Avr = 'avr';
    case Classroom = 'classroom';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ComputerLab => 'Computer Lab',
            self::Avr => 'AVR',
            self::Classroom => 'Classroom',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ComputerLab => 'azure',
            self::Avr => 'indigo',
            self::Classroom => 'blue',
            self::Other => 'gray',
        };
    }
}
