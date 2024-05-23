<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['company_id', 'sub_module_id', 'permission_title','created_by', 'updated_by', 'deleted_by' ];
    protected $table = 'permissions'; // Your table name

    protected $guarded = [];
    
    
}
