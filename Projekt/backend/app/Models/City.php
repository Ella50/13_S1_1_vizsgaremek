<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;
    protected $table = 'cities';

    protected $fillable = [
        'cityName',
        'zipCode',
        'county_id'
    ];

    public function county()
    {
        return $this->belongsTo(County::class, 'county_id');
    }

    public function user()
    {
        return $this->hasMany(User::class, 'city_id');
    }
}