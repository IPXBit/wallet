<?php

namespace Modules\AppUser\App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\HasPermissions;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\Traits\JWTSubject as JWTSubjectTrait;

class AppUsers extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles , HasPermissions  ;



    protected $guarded = [];

    protected $hidden = [
        'password',
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->account_number = AppUsers::generateAccountNumber();
        });
    }

    public static function generateAccountNumber()
    {
        do {
            $account_number = rand(1000000000, 9999999999); // Generate a random 10-digit number
        } while (self::where('account_number', $account_number)->exists());

        return $account_number;
    }
  public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    protected $casts = [
        'otp'=>'integer',
        'type'=>'integer',
        'status'=>'integer',
    ];
   public function orders(){
    return $this->hasMany(Order::class,'user_id');
   }

   public function address()
   {
        return $this->hasOne(Address::class,"user_id");
   }
  
   public function cards()
   {
        return $this->hasOne(Card::class,"app_user_id");
   }

   public function country()
   {
        return $this->belongsTo(Country::class);
   }
   public function wishlists()
    {
        return $this->belongsToMany(SubService::class, 'wishlists', 'user_id', 'sub_service_id'); 
    }
   public function  getNameAttribute()
   {
        return $this->first_name .' '.$this->last_name;
   }
   public function memberships()
   {
       return $this->belongsToMany(Package::class,'memberships','user_id','package_id');
   }

//    public function favorites()
//    {
//         return $this->belongsToMany(Post::class,"favorits","user_id","post_id");
//    }
}

