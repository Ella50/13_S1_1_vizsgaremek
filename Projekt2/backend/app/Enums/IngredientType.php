<?php

namespace App\Enums;

enum IngredientType: string
{
    case HUS= 'Húsféle';
    case GABONA = 'Gabonaféle';
    case TEJTERMEK = 'Tejtermék';
    case ZOLDSEG = 'Zöldség';
    case EGYEB = 'Egyeb';

}