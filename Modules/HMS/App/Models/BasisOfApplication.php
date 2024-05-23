<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasisOfApplication extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['basis_of_application', 'frequancy','created_by', 'updated_by', 'deleted_by'];
    protected $table = 'basis_of_applications'; // Your table name

    protected $guarded = [];
}
