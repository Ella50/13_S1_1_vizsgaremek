<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHealthRestriction extends Model
{
    use HasFactory;
    protected $table = 'userHealthRestrictions';

    protected $fillable = [
        'user_id',
        'allergen_id',
    ];
    public $timestamps = false;

    public function allergen()
    {
        return $this->belongsTo(Allergen::class, 'allergen_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}