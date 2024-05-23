<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['property_id', 'receipt_id', 'total_amount', 'paid_amount', 'payment_date', 'payment_status', 'payment_reference', 'payment_method','created_by', 'updated_by', 'deleted_by' ];
    protected $table = 'payments'; // Your table name

    protected $guarded = [];
    
    
}
