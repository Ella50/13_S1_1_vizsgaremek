<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case GENERATED = 'Generálva';
    case PAID = 'Fizetve';
    case EXPIRED = 'Lejárt';

}