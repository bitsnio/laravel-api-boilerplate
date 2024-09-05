<?php

namespace Modules\HMS\App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\HMS\App\Models\Property;
use Modules\HMS\App\Models\RoomType;

class RoomList extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = [ 'property_id', 'room_type_id', 'room_number', 'room_status', 'check_in_date', 'check_out_date', 'created_by', 'updated_by', 'deleted_by' ];
    protected $table = 'room_lists';
    // Your table name

    protected $guarded = [];

    protected $dates = [ 'deleted_at' ];

    public function property() {
        return $this->belongsTo( Property::class, 'property_id' )->select( 'property_name as title', 'id' );
    }

    public function roomtypes() {
        return $this->belongsTo( RoomType::class, 'room_type_id' )->select( 'room_type as title', 'id' );
    }

    public function roomtype() {
        return $this->belongsTo( RoomType::class, 'room_type_id' );
    }

}
