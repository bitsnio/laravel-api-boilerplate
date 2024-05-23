<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyServices extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['property_id','service_name', 'basis_of_application', 'frequency', 'cost', 'selling_price',  'created_by', 'updated_by', 'deleted_by'];
    protected $table = 'property_services'; // Your table name

    protected $guarded = [];

    
    public function propertyServiceRules()
    {
        return $this->hasMany(PropertyServiceRules::class,'property_service_id');
    }
}
