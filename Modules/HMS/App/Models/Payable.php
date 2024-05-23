<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payable extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['property_id', 'days', 'assigned_additional_service_id', 'check_in_id', 'uom', 'item_name', 'cost', 'quantity', 'total_amount', 'payment_status','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'payables'; // Your table name

    protected $guarded = [];

    
}
