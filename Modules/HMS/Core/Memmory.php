<?php
namespace Modules\HMS\Core;

use Modules\HMS\App\Models\PropertySetting;
use Throwable;

class Memmory{
    public static function propertySettings() {
        try{
            $settings = PropertySetting::where('property_id', 0)->get()->toArray();
            return $settings;
        }
        catch(Throwable $e){
            return ['error' => $e->getMessage()];
        }
    }
}