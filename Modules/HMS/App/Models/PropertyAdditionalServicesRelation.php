<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyAdditionalServicesRelation extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['property_id', 'additional_service_id','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'property_additional_services_relations'; // Your table name

    protected $guarded = [];
    
    public function additionalServices(){
        return $this->belongsTo(AdditionalServices::class,'additional_service_id')->select('service_name as title','id');
    }

    // public function propertyAdditionalServices(){
    //     return $this->belongsTo(AdditionalServices::class,'additional_service_id');
    // }

    
    
}
