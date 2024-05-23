<?php

namespace Modules\HMS\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomTypeRequest extends StoreRoomTypeRequest{
    
    public function rules(): array
    {
        return [
            'property_id' => 'integer',
            'room_type' => 'string',
            'rental_price' => 'integer',
            'hiring_cost' => 'integer',
            'quantity' => 'integer',
            'room_number_start_from' => 'integer',
            'room_prefix'=>'string'
        ];
    }
}
