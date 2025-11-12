<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Players extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'age',
        'points',
        'address',
    ];
    protected $table = 'testing_players';
    /**
     * Get the table name for the model.
     *
     * @return string
     */
    public function getTable()
    {
        if (App::environment('testing')) {
            return 'testing_players';
        }

        return 'players';
    }
}
