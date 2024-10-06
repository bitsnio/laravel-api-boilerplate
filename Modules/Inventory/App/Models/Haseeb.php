<?php

namespace Modules\Inventory\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Inventory\Database\factories\HaseebFactory;

class Haseeb extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ["company_name","company_email","company_phone","street_1","street_2","city","state","zip_code","birthday","notes","phone_numbers"];

}
