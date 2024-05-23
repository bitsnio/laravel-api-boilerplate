<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\HMS\App\Models\Property;

class RoomType extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['property_id','room_prefix', 'room_type','hiring_cost','rental_price' ,'quantity', 'room_number_start_from','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'room_types'; // Your table name

    protected $guarded = [];

    

    public function property(){
        return $this->belongsTo(Property::class,'property_id')->select('property_name as title','id');
    }


}
