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
        'hasDiabetes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'hasDiscount' => 'boolean',
        'hasDiabetes' => 'boolean',
    ];

    // Jelszó mező átnevezése
    public function getAuthPassword()
    {
        return $this->password;
    }

    // Jelszó visszaállító értesítés küldése
    public function sendPasswordResetNotification($token)
{
    $this->notify(new \App\Notifications\ResetPasswordNotification($token, $this->email));
}

    // Kapcsolatok
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function county()
    {
        return $this->hasOneThrough(
            County::class,
            City::class,
            'id',           // cities tábla elsődleges kulcsa
            'id',           // counties tábla elsődleges kulcsa
            'city_id',      // users tábla külső kulcsa (city_id)
            'county_id'     // cities tábla külső kulcsa (county_id)
        );
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
        return $this->belongsTo(\App\Models\RfidCard::class, 'rfidCard_id');
    }

    /*public function user() HA NEM MŰKÖDNE AZ RFID OLVASÓ, AKKOR EZT VISSZAÁLLÍTANI
    {
        return $this->hasOne(\App\Models\User::class, 'rfidCard_id');
    }*/


    public function userHealthRestrictions()
    {
        return $this->hasMany(UserHealthRestriction::class, 'user_id');
    }

    public function allergens()
    {
        return $this->belongsToMany(Allergen::class, 'userHealthRestrictions', 'user_id', 'allergen_id')
                    ->wherePivotNotNull('allergen_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    // Helper metódusok a dokumentumok típus szerinti lekéréséhez
    public function getDiscountDocument()
    {
        return $this->documents()->where('type', 'discount')->latest()->first();
    }

    public function getDiabetesDocument()
    {
        return $this->documents()->where('type', 'diabetes')->latest()->first();
    }

    public function hasDiscountDocument()
    {
        return $this->documents()->where('type', 'discount')->exists();
    }

    public function hasDiabetesDocument()
    {
        return $this->documents()->where('type', 'diabetes')->exists();
    }




}

