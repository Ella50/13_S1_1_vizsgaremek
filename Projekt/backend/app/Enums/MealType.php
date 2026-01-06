<?php

namespace App\Enums;

enum MealType: string
{
    case LEVES = 'Leves';
    case FOETEL = 'Főétel';
    case EGYEB = 'Egyéb';
    
    public function label(): string
    {
        return match($this) {
            self::LEVES => 'Leves',
            self::FOETEL => 'Főétel',
            self::EGYEB => 'Egyéb',
        };
    }
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}