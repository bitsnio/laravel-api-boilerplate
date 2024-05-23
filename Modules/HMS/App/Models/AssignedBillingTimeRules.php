<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignedBillingTimeRules extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['property_service_id', 'apply_on', 'title', 'from', 'to', 'charge_compare_with', 'charge_percentage', 'apply_on','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'assigned_billing_time_rules'; // Your table name

    protected $guarded = [];
}
