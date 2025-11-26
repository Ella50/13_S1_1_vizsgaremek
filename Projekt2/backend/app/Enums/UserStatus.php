<?php

namespace App\Enums;

enum UserStatus: string
{
    case INACTIVE = 'inactive';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
}