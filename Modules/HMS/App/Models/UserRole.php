<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\HMS\App\Models\SubModule;

class UserRole extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = [ 'company_id', 'property_id', 'role_name', 'sub_module_id', 'icon', 'created_by', 'updated_by', 'deleted_by' ];
    // protected $table = 'user_roles';
    // Your table name

    protected $guarded = [];

    public function roleWithPermissions() {
        return $this->hasMany( RoleWithPermission::class, 'role_id' );
    }

}
