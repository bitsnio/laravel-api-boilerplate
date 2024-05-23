<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class familyGeneratedBill extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [ 'company_id', 'registeration_number', 'family_name', 'check_in_ids', 'total_merged_amount','created_by', 'updated_by', 'deleted_by' ];
    protected $table = 'family_generated_bills'; // Your table name

    protected $guarded = [];

    
}
