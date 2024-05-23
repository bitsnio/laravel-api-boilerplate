<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubModule extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['menu_order', 'main_module_id', 'title', 'route', 'slug', 'icon','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'sub_modules'; // Your table name

    protected $guarded = [];

    
}
