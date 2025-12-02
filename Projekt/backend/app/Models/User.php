<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;


    
   /* protected $primaryKey = 'id';
    public $timestamps = true;*/

    protected $hidden = [
        'password',
        'remember_token'
    ];

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
    ];

        protected $casts = [
        'has_discount' => 'boolean',
    ];


    // Jelszó getter átállítása
    public function getAuthPassword()
    {
        return $this->password_hash;
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
}