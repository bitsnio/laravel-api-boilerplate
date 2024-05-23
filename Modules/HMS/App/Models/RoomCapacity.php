<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomCapacity extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['property_id', 'room_list_id', 'rental_cost', 'selling_price', 'check_in_date', 'check_out_date','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'room_capacities'; // Your table name

    protected $guarded = [];

    
}
