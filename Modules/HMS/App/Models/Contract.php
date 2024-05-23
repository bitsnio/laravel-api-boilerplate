<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [ 'company_id', 'property_id', 'client_name', 'client_contact_details', 'client_address', 'client_city','created_by', 'updated_by', 'deleted_by' ];
    protected $table = 'contracts'; // Your table name

    protected $guarded = [];

    
}
