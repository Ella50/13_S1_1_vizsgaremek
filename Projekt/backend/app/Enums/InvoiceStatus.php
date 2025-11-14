<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case HIANYZIK = 'Hiányzik';
    case GENERATED = 'Generálva';
    case PAID = 'Fizetve';
    case EXPIRED = 'Lejárt';

}