<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\HMS\App\Models\RoomType;

class Property extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['company_id', 'category', 'property_name', 'property_type', 'property_email', 'property_phone', 'city', 'postal_code', 'street_address', 'description','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'properties'; // Your table name

    protected $guarded = [];
    
    public function propertyServices(){
        return $this->hasMany(PropertyServices::class,'property_id')->select('service_name as title','id','property_id')->without('property_id');
    }

    
    public function companies()
    {
        return $this->belongsTo(Company::class,'company_id');
    }
    public function rooms()
    {
        return $this->hasMany(RoomType::class,'property_id');
    }
    public function settings()
    {
        return $this->hasMany(PropertySetting::class,'property_id');
    }
    public function payables(){
        return $this->hasMany(Payable::class, 'property_id');
    }
    public function reciveables(){
        return $this->hasMany(Payable::class, 'property_id');
    }
    public function advancePayments(){
        return $this->hasMany(AdvancePayment::class, 'property_id');
    }
}
