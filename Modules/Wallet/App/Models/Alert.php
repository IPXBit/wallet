<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\AppUser\App\Models\AppUsers;

class Alert extends Model
{
    use HasFactory;
    protected $guarded =[];
      public function user()
    {
        return $this->belongsTo(AppUsers::class);
    }

    // public function subService()
    // {
    //     return $this->belongsTo(SubService::class);
    // }
}
