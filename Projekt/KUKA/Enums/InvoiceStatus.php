<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case PENDING = 'Függőben lévő';
    case GENERATED = 'Generálva';
    case PAID = 'Fizetve';
    case EXPIRED = 'Lejárt';

}