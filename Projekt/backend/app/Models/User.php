<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'third_name',
        'city_id',
        'address',
        'email',
        'password_hash',
        'user_type',
        'rfid_card_id',
        'class_id',
        'group_id',
        'status',
        'has_discount',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'has_discount' => 'boolean',
    ];

    // Jelszó mező átnevezése
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // Kapcsolatok
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
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



    // Kapcsolatok
    /*
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function studentClass()
    {
        return $this->belongsTo(StudentClass::class, 'class_id');
    }

    public function group() 
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function rfidCard()
    {
        return $this->belongsTo(RfidCard::class, 'rfidCard_id');
    }
    */
