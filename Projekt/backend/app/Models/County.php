<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class County extends Model
{
    use HasFactory;

    protected $table = 'counties';

    protected $fillable = [
        'countyName'
    ];

    // Kapcsolat City modellel
    public function cities()
    {
        return $this->hasMany(City::class, 'county_id');
    }
}