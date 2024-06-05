<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = [ 'company_name', 'company_email', 'company_phone', 'country', 'city',  'street_address', 'created_by', 'updated_by', 'deleted_by' ];
    protected $table = 'companies';
    // Your table name

    protected $guarded = [];

    public function properties() {
        return $this->hasMany( Property::class );
    }

    public function contracts() {
        return $this->hasMany( Contract::class );
    }
}
