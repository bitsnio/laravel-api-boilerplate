<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedBills extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['property_id', 'check_in_id', 'billing_status', 'billing_date','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'generated_bills'; // Your table name

    protected $guarded = [];

    
}
