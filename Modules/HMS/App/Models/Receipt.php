<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [ 'company_id', 'property_id', 'check_in_ids', 'total_merged_amount', 'receipt_type','created_by', 'updated_by', 'deleted_by' ];
    protected $table = 'receipts'; // Your table name

    protected $guarded = [];

    
    public function payments(){
        return $this->hasMany(Payment::class,'receipt_id');
    }
    public function advancePayments(){
        return $this->hasMany(AdvancePayment::class,'property_id');
    }
    public function properties(){
        return $this->belongsTo(Property::class,'property_id')->select('property_name as title','id');
    }
}
