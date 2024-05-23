<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\HMS\App\Models\AssignedBillingTimeRules;

class AssignedAdditionalServices extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['property_id', 'check_in_id','service_name', 'basis_of_application', 'frequency', 'cost', 'selling_price',  'created_by', 'updated_by', 'deleted_by'];
    protected $table = 'assigned_additional_services'; // Your table name
    
    protected $guarded = [];

    public function billingRules(){
        return $this->hasMany(AssignedBillingTimeRules::class,'property_service_id');
     }
}
