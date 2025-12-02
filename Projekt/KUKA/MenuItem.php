<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $table = 'menuItem';

    protected $fillable = [
        'soup',
        'optionA',
        'optionB',
        'day'
    ];
    
    public function soup()
    {
        return $this->belongsTo(Meal::class, 'soup');
    }

    public function optionA()
    {
        return $this->belongsTo(Meal::class, 'optionA');
    }

    public function optionB()
    {
        return $this->belongsTo(Meal::class, 'optionB');
    }
}