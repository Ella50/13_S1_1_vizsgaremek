<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case FOLYAMATBAN = 'Folyamatban';
    case BEFEJEZVE = 'Befejezve';
    case VISSZAUTASITVA = 'Visszautasítva';

}