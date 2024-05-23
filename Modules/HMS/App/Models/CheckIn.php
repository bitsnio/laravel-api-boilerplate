<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\HMS\App\Models\CheckedInMembers;
use Modules\HMS\App\Models\AssignedAdditionalServices;

class CheckIn extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['property_id', 'last_check_in_id', 'parent_id', 'check_in_status', 'present_status', 'registeration_number', 'selected_services', 'family_name', 'check_in_type', 'total_persons', 'payment_type', 'bound_country', 'check_in_date', 'check_in_time','expected_check_out_date', 'expected_check_out_time', 'booking_notes','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'check_ins'; // Your table name

    protected $guarded = [];

    public function guests(){
        return $this->hasMany(CheckedInMembers::class,'check_in_id');
    }
    public function property(){
        return $this->belongsTo(Property::class,'property_id')->select('property_name as title', 'property_type','id', 'category');
    }
    public function properties(){
        return $this->belongsTo(Property::class,'property_id')->select('property_name as title','id');
    }

    public function additionalServices(){
       return $this->hasMany(AssignedAdditionalServices::class,'check_in_id');
    }
    public function payables(){
        return $this->hasMany(Payable::class,'check_in_id');
    }
    public function propertyBillings(){
        return $this->hasMany(PropertyBilling::class,'check_in_id');
    }
}
