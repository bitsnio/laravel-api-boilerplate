<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyBilling extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['property_id', 'days', 'uom', 'assigned_additional_service_id', 'check_in_id', 'item_name', 'selling_price', 'quantity', 'total_amount', 'payment_status','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'property_billings'; // Your table name

    protected $guarded = [];

    
}
