<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleWithPermission extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['company_id', 'role_id', 'permission_id','created_by', 'updated_by', 'deleted_by' ];
    protected $table = 'role_with_permissions'; // Your table name

    protected $guarded = [];
    
    
    public function permissions(){
        return $this->belongsTo(Permission::class,'permission_id')->select('permission_title as title','permission', 'id');
    }
}
