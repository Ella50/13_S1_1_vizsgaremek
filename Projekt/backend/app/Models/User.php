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

    // Jelszó visszaállító értesítés küldése
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token, $this->email));
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
