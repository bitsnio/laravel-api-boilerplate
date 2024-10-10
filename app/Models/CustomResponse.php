<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomResponse extends Model
{
    use HasFactory;
    protected $table = 'checked_in_members';
}
