<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckOut extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['property_id', 'check_in_id', 'guest_name', 'room_number', 'payment_status', 'payment_mathod', 'check_out_date', 'check_out_time','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'check_outs'; // Your table name

    protected $guarded = [];
}
