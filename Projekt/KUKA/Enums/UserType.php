<?php

namespace App\Enums;

enum UserType: string
{
    case TANULO = 'Tanuló';
    case KULSOS = 'Külsős';
    case TANAR = 'Tanár';
    case DOLGOZO = 'Dolgozó';
    case ADMIN = 'Admin';
    case KONYHA = 'Konyha';
}