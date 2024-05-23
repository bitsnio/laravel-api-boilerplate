<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvancePayment extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['property_id', 'payment_id', 'advance_amount', 'payment_date', 'payment_reference', 'payment_method','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'advance_payments'; // Your table name

    protected $guarded = [];
}
