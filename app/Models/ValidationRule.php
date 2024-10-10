<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ValidationRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'module',
        'model',
        'method',
        'field_name',
        'rules',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $table = 'validation_rules';
}
