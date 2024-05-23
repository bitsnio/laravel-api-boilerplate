<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainModule extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['slug', 'title', 'route', 'icon','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'main_modules'; // Your table name

    protected $guarded = [];

    
}
