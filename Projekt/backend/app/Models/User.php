<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'firstName',
        'lastName',
        'thirdName',
        'city_id',
        'address',
        'email',
        'password',
        'userType',
        'rfidCard_id',
        'class_id',
        'group_id',
        'userStatus',
        'hasDiscount',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'hasDiscount' => 'boolean',
    ];

    // Jelszó mező átnevezése
    public function getAuthPassword()
    {
        return $this->password;
    }

    // Kapcsolatok
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function studentClass()
    {
        return $this->belongsTo(studentClass::class, 'class_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function rfidCard()
    {
        return $this->belongsTo(RfidCard::class);
    }
}

