<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\HMS\App\Models\RoomList;

class CheckedInMembers extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['property_id', 'check_in_id', 'guest_name', 'date_of_birth', 'room_number', 'cnic_passport_number', 'visa_expiry', 'customer_type', 'customer_city', 'customer_province', 'cutomer_postal_code', 'customer_home_address','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'checked_in_members'; // Your table name

    protected $guarded = [];

    public function rooms(){
        return $this->belongsTo(RoomList::class,'room_number');
    }
    public function roomDetails(){
        return $this->belongsTo(RoomList::class,'room_number')->select('room_number as title', 'id');
    }
}
