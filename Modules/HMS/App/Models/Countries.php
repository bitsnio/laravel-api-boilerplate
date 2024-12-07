<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\HMS\Database\Factories\CountriesFactory;

class Countries extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    protected static function newFactory(): CountriesFactory
    {
        return CountriesFactory::new();
    }
}
