<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [ 'company_id', 'property_id', 'expense_type', 'title', 'expense_amount', 'payment_method', 'payment_reference', 'expense_date','created_by', 'updated_by', 'deleted_by' ];
    protected $table = 'expenses'; // Your table name

    protected $guarded = [];

    
}
