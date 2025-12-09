<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'className',
        
    ];


    public function students()
    {
        return $this->hasMany(User::class, 'class_id')->where('userType', 'Tanuló');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'class_id');
    }

        public function getDisplayNameAttribute()
    {
        return $this->className;
    }

    public function getStudentCountAttribute()
    {
        return $this->students()->count();
    }
}