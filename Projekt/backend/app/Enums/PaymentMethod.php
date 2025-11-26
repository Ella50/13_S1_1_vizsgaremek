<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case UTALAS = 'Banki utalás';
    case KARTYA = 'Bankkártya';
    case KESZPENZ = 'Készpénz';


}