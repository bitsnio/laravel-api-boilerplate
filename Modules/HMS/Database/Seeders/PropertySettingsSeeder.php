<?php

namespace Modules\HMS\Database\Seeders;

use Modules\HMS\App\Utilities\Helper;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpParser\ErrorHandler\Throwing;
use Throwable;

class PropertySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($property_id)
    {
        try{
            $AS1 = DB::table('property_settings')->insert([
                [
                    'property_id'=>$property_id,
                    'key' => 'room_charge_time',
                    'value' => '05:00 PM',
                    'value_type' => 'time',
                    'created_by'=>1
                ],
                [
                    'property_id'=>$property_id,
                    'key' => 'day_start_time',
                    'value' => '05:00 AM',
                    'value_type' => 'time',
                    'created_by'=>1
                ],
                [
                    'property_id'=>$property_id,
                    'key' => 'show_room_name_with_payable_invoice',
                    'value' => 0,
                    'value_type' => 'boolean',
                    'created_by'=>1
                ],
                [
                    'property_id'=>$property_id,
                    'key' => 'show_room_name_with_receivable_invoice',
                    'value' => 0,
                    'value_type' => 'boolean',
                    'created_by'=>1
                ]
            ]);
            return ['success'=>true];
        }
        catch(Throwable $th){
            ['success'=>false,'message'=>$th->getMessage()];
        }
    }
}
