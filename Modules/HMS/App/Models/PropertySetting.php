<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertySetting extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['property_id', 'key', 'value', 'value_type','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'property_settings'; // Your table name

    protected $guarded = [];

    
}
