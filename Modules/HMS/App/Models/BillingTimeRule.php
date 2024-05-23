<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingTimeRule extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['additional_service_id', 'apply_on', 'title', 'from', 'to', 'charge_compare_with', 'charge_percentage','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'billing_time_rules'; // Your table name

    protected $guarded = [];
}
