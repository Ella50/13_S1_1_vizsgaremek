<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\RFIDCard;
use App\Models\City;
use App\Models\Class;
use Illuminate\Support\Facades\Hash;

use Illuminate\Notifications\Notifiable;
/*
User::create([
    'name' => 'Teszt User',
    'email' => 'teszt@example.com',
    'password' => Hash::make('1234')
]);*/
class User extends Model
{
    use HasFactory;

     protected $table = 'user'; 
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
        'status',
        'hasDiscount',
        'created_at',
        'updated_at'
    ];

    /* ENUM értékek definiálása
    const USER_TYPE_STUDENT = 'student';
    const USER_TYPE_TEACHER = 'teacher';
    const USER_TYPE_ADMIN = 'admin';
    const USER_TYPE_PARENT = 'parent';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    ENUM opciók
    public static function getUserTypeOptions()
    {
        return [
            self::USER_TYPE_STUDENT => 'Diák',
            self::USER_TYPE_TEACHER => 'Tanár',
            self::USER_TYPE_ADMIN => 'Adminisztrátor',
            self::USER_TYPE_PARENT => 'Szülő'
        ];
    }

    public static function getStatusOptions()
    {
        return [
            self::STATUS_ACTIVE => 'Aktív',
            self::STATUS_INACTIVE => 'Inaktív',
            self::STATUS_SUSPENDED => 'Felfüggesztett'
        ];
    }*/

    // Jelszó titkosítás
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    // Kapcsolatok más modellekkel
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
}