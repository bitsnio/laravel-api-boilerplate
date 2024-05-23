<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        DB::table('users')->insert([
            'user_type' => 'sa',
            'name' => 'Super Admin',
            'email' => 'super.admin.sa@gmail.com',
            'password' => Hash::make('superadminpassword'),
            'company_id'=>0,
            'main_module_id'=>'1',
            'role'=>1,

        ]);


        DB::table('property_settings')->insert([
            [
                'property_id' => 0,
                'key' => 'compare_with',
                'value' => 'Checkin_Checkout_Time',
                'value_type'=>'time',
                'created_by' => 0,

            ],
            [
                'property_id' => 0,
                'key' => 'compare_with',
                'value' => 'Guest Age',
                'value_type'=>'number',
                'created_by' => 0,

            ],
            [
                'property_id' => 0,
                'key' => 'compare_with',
                'value' => 'Checkin Time',
                'value_type'=>'time',
                'created_by' => 0,
            ],
            [
                'property_id'=>0,
                'key' => 'compare_with',
                'value' => 'Number of Guest in Rooms',
                'value_type' => 'number',
                'created_by'=>1
            ],
            [
                'property_id' => 0,
                'key' => 'compare_with',
                'value' => 'Checkout Time',
                'value_type'=>'time',
                'created_by' => 0,
            ],
            [
                'property_id' => 0,
                'key' => 'basis_of_application',
                'value' => 'Guests',
                'value_type'=>'options',
                'created_by' => 0,
            ],
            [
                'property_id' => 0,
                'key' => 'basis_of_application',
                'value' => 'Room',
                'value_type'=>'options',
                'created_by' => 0,
            ],
            [
                'property_id' => 0,
                'key' => 'basis_of_application',
                'value' => 'Family',
                'value_type'=>'options',
                'created_by' => 0,
            ],
            [
                'property_id'=>0,
                'key' => 'apply_on',
                'value' => 'Cost',
                'value_type' => 'number',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'apply_on',
                'value' => 'Selling',
                'value_type' => 'number',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'apply_on',
                'value' => 'Both',
                'value_type' => 'number',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'checkin_type',
                'value' => 'Single',
                'value_type' => 'string',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'checkin_type',
                'value' => 'Group',
                'value_type' => 'string',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'checkin_type',
                'value' => 'Event',
                'value_type' => 'string',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'payment_type',
                'value' => 'Advance Payment',
                'value_type' => 'string',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'payment_type',
                'value' => 'On Counter',
                'value_type' => 'string',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'payment_type',
                'value' => 'Post Billing',
                'value_type' => 'string',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'frequency',
                'value' => 'Daily Basis',
                'value_type' => 'string',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'frequency',
                'value' => 'Monthly Basis',
                'value_type' => 'string',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'frequency',
                'value' => 'One Time',
                'value_type' => 'string',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'payment_method',
                'value' => 'Bank Transfer',
                'value_type' => 'string',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'payment_method',
                'value' => 'Cash Payment',
                'value_type' => 'string',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'payment_method',
                'value' => 'Cheque Payment',
                'value_type' => 'string',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'property_type',
                'value' => 'All',
                'value_type' => 'string',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'property_type',
                'value' => 'Hotel',
                'value_type' => 'string',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'property_type',
                'value' => 'Hostel',
                'value_type' => 'string',
                'created_by'=>1
            ],
            [
                'property_id'=>0,
                'key' => 'property_type',
                'value' => 'Guest House',
                'value_type' => 'string',
                'created_by'=>1
            ],

        ]);

        DB::table('main_modules')->insert([
            [
                'id'=>1,
                'slug' => 'sa',
                'title' => 'Super Admin',
                'route' => '/',
                'icon' => 'ni-air-baloon',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ],
            [
                'id'=>2,
                'slug' => 'ca',
                'title' => 'Admin',
                'route' => '/',
                'icon' => 'ni-atom',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ],
            [
                'id'=>3,
                'slug' => 'cu',
                'title' => 'Front Desk',
                'route' => '/',
                'icon' => 'ni-shop',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ]
        ]);
        DB::table('sub_modules')->insert([
            [
                'menu_order'=>1,
                'main_module_id' => 1,
                'title' => 'Companies',
                'route' => 'companies',
                'slug' => 'companies',
                'icon' => 'ni-atom',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ],
            [
                'menu_order'=>3,
                'main_module_id' => 1,
                'title' => 'System Modules',
                'route' => 'modules',
                'slug' => 'modules',
                'icon' => 'ni-atom',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ],
            [
                'menu_order'=>4,
                'main_module_id' => 2,
                'title' => 'Users',
                'route' => 'users',
                'slug' => 'users',
                'icon' => 'ni-single-02',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ],
            [
                'menu_order'=>5,
                'main_module_id' => 2,
                'title' => 'Properties',
                'route' => 'properties',
                'slug' => 'properties',
                'icon' => 'ni-building',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ],
            [
                'menu_order'=>6,
                'main_module_id' => 2,
                'title' => 'Room Types',
                'route' => 'room-types',
                'slug' => 'room-types',
                'icon' => 'ni-tag',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ],
            [
                'menu_order'=>7,
                'main_module_id' => 2,
                'title' => 'Room Lists',
                'route' => 'room-lists',
                'slug' => 'room-lists',
                'icon' => 'ni-bullet-list-67',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ],
            [
                'menu_order'=>8,
                'main_module_id' => 3,
                'title' => 'Check Ins',
                'route' => 'check-ins',
                'slug' => 'check-ins',
                'icon' => 'ni-check-bold',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ],
            // [
            //     'menu_order'=>9,
            //     'main_module_id' => 3,
            //     'title' => 'Checked In Details',
            //     'route' => 'checked-in-details',
            //     'slug' => 'checked-in-details',
            //     'icon' => 'ni-calendar-grid-58',
            // 
            //     'created_by' => 1,
            //     'updated_by' => 0,
            //     'deleted_by' => 0,
            // ],
            // [
            //     'menu_order'=>10,
            //     'main_module_id' => 3,
            //     'title' => 'Check Outs',
            //     'route' => 'check-outs',
            //     'slug' => 'check-outs',
            //     'icon' => 'ni-ui-04',
            // 
            //     'created_by' => 1,
            //     'updated_by' => 0,
            //     'deleted_by' => 0,
            // ],
            [
                'menu_order'=>11,
                'main_module_id' => 2,
                'title' => 'Additional Services',
                'route' => 'additional-services',
                'slug' => 'additional-services',
                'icon' => 'ni-zoom-split-in',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ],
            [
                'menu_order'=>12,
                'main_module_id' => 2,
                'title' => 'Payables',
                'route' => 'payables',
                'slug' => 'payables',
                'icon' => 'ni ni-collection',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ],
            [
                'menu_order'=>13,
                'main_module_id' => 2,
                'title' => 'Receivables',
                'route' => 'receivables',
                'slug' => 'receivables',
                'icon' => 'ni ni-money-coins',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ],
            [
                'menu_order'=>2,
                'main_module_id' => 2,
                'title' => 'User Roles',
                'route' => 'roles',
                'slug' => 'user-roles',
                'icon' => 'ni ni-ruler-pencil',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ],
            [
                'menu_order'=>1,
                'main_module_id' => 2,
                'title' => 'Property Settings',
                'route' => 'property-settings',
                'slug' => 'property-settings',
                'icon' => 'ni ni-settings-gear-65',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ],
            [
                'menu_order'=>0,
                'main_module_id' => 2,
                'title' => 'Dashboard',
                'route' => '/dashboard',
                'slug' => 'dashboard',
                'icon' => 'ni ni-chart-bar-32',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ],
            [
                'menu_order'=>14,
                'main_module_id' => 2,
                'title' => 'Expenses',
                'route' => '/expenses',
                'slug' => 'expenses',
                'icon' => 'ni ni-credit-card',

                'created_by' => 1,
                'updated_by' => 0,
                'deleted_by' => 0,
            ]
        ]);
    }
}
