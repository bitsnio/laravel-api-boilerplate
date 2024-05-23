<?php

namespace Modules\HMS\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Support\Facades\DB;


class additionalServices extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($company_id){

        try{
            $AS1 = DB::table('additional_services')->insertGetId([
                'basis_of_application' => 'Guests',
                'cost' => 250,
                'frequency' => 'Daily Basis',
                'selling_price' => 400,
                'company_id'=>$company_id,
                'service_name'=>'breakfast',
                'created_by'=>1
            ]);
            
            $timeRuleForBreakFast = '[
                {
                    "title": "breakfast",
                    "charge_compare_with": "Checkin_Checkout_Time",
                    "from": "06:00 AM",
                    "to": "10:00 AM",
                    "charge_percentage": 100,
                    "apply_on": "Both"
                },
                {
                    "title": "Infants",
                    "charge_compare_with": "Guest Age",
                    "from": "0",
                    "to": "4",
                    "charge_percentage": 0,
                    "apply_on": "Both"
                },
                {
                    "title": "Children",
                    "charge_compare_with": "Guest Age",
                    "from": "5",
                    "to": "12",
                    "charge_percentage": 50,
                    "apply_on": "Both"
                },
                {
                    "title": "Adults",
                    "charge_compare_with": "Guest Age",
                    "from": "13",
                    "to": "100",
                    "charge_percentage": 100,
                    "apply_on": "Both"
                },
                {
                    "title": "Complementory Breakfast",
                    "charge_compare_with": "Number of Guest in Rooms",
                    "from": "0",
                    "to": "2",
                    "charge_percentage": 0,
                    "apply_on": "Both"
                }
                ]';
                $data = json_decode($timeRuleForBreakFast);
                $additional_fields_AS =  ['additional_service_id'=> $AS1,'created_by'=>1];
                $AS = Helper::objectsToArray($data, $additional_fields_AS);
                
                DB::table("billing_time_rules")->insert($AS);
                
                
            $AS2 = DB::table('additional_services')->insertGetId([
                'basis_of_application' => 'Guests',
                'cost' => 250,
                'frequency' => 'Daily Basis',
                'selling_price' => 400,
                'company_id'=>$company_id,
                'service_name'=>'lunch',
                'created_by'=>1
            ]);

            $timeRuleForLunch = '[
                {
                    "title": "Lunch Timinig",
                    "charge_compare_with": "Checkin_Checkout_Time",
                    "from": "12:00 PM",
                    "to": "04:00 PM",
                    "charge_percentage": 100,
                    "apply_on": "Both"
                },
                {
                    "title": "Lunch for Adults",
                    "charge_compare_with": "Guest Age",
                    "from": "13",
                    "to": "100",
                    "charge_percentage": 100,
                    "apply_on": "Both"
                },
                {
                    "title": "Lunch For Infants",
                    "charge_compare_with": "Guest Age",
                    "from": "0",
                    "to": "4",
                    "charge_percentage": 20,
                    "apply_on": "Both"
                },
                {
                    "title": "Lunch For children",
                    "charge_compare_with": "Guest Age",
                    "from": "5",
                    "to": "12",
                    "charge_percentage": 80,
                    "apply_on": "Both"
                }
            ]';
            
            $data = json_decode($timeRuleForLunch);
            $additional_fields_AS2 =  ['additional_service_id'=> $AS2,'created_by'=>1];
            $AS = Helper::objectsToArray($data, $additional_fields_AS2);

            DB::table("billing_time_rules")->insert($AS);

            $AS3 = DB::table('additional_services')->insertGetId([
                'basis_of_application' => 'Guests',
                'cost' => 250,
                'frequency' => 'Daily Basis',
                'selling_price' => 400,
                'company_id'=>$company_id,
                'service_name'=>'Dinner',
                'created_by'=>1
            ]);

            $timeRuleForLunch = '[
                {
                    "title": "Dinner Timinig",
                    "charge_compare_with": "Checkin_Checkout_Time",
                    "from": "06:00 PM",
                    "to": "10:00 PM",
                    "charge_percentage": 100,
                    "apply_on": "Both"
                },
                {
                    "title": "Dinner for Adults",
                    "charge_compare_with": "Guest Age",
                    "from": "13",
                    "to": "100",
                    "charge_percentage": 100,
                    "apply_on": "Both"
                },
                {
                    "title": "Dinner For Infants",
                    "charge_compare_with": "Guest Age",
                    "from": "0",
                    "to": "4",
                    "charge_percentage": 20,
                    "apply_on": "Both"
                },
                {
                    "title": "Dinner For children",
                    "charge_compare_with": "Guest Age",
                    "from": "5",
                    "to": "12",
                    "charge_percentage": 80,
                    "apply_on": "Both"
                }
            ]';
            
            $data = json_decode($timeRuleForLunch);
            $additional_fields_AS3 =  ['additional_service_id'=> $AS3,'created_by'=>1];
            $AS = Helper::objectsToArray($data, $additional_fields_AS3);

            DB::table("billing_time_rules")->insert($AS);


            return ['success'=>true];
                
        }catch(\Throwable $e){
            return ['success'=>false,'message'=>$e->getMessage()];
        }




    }
}