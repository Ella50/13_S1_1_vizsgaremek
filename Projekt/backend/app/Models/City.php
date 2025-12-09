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

    // Kapcsolat County modellel
    public function county()
    {
        return $this->belongsTo(County::class, 'county_id');
    }

    // Kapcsolat User modellel
    public function user()
    {
        return $this->hasMany(User::class, 'city_id');
    }
}