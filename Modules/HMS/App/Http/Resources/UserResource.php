<?php

namespace Modules\HMS\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\UserRole;
use App\Models\SubModule;

class UserResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->roles,
            // 'sub_module'=>SubModule::select('title','id')->whereRaw('FIND_IN_SET(id,"'.$this->roles->sub_module_id.'")')->get(),
           
        ];      
    }

}
