<?php

namespace Modules\HMS\Database\Seeders;

use Modules\HMS\App\Models\SubModule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Throwable;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      DB::table('permissions')->insert([
        //Permissions for Company
        ['name' => 'viewAny Company' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'view Company' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'create Company' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'update Company' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'delete Company' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'restore Company' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'forceDelete Company' , 'guard_name' => 'api', 'created_at' => now()],

        //Permissions for Property
        ['name' => 'viewAny Property' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'view Property' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'create Property' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'update Property' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'delete Property' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'restore Property' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'forceDelete Property' , 'guard_name' => 'api', 'created_at' => now()],

        //Permissions for Additional Services
        ['name' => 'viewAny AdditionalServices' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'view AdditionalServices' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'create AdditionalServices' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'update AdditionalServices' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'delete AdditionalServices' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'restore AdditionalServices' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'forceDelete AdditionalServices' , 'guard_name' => 'api', 'created_at' => now()],

        //Permissions for Property Services
        ['name' => 'viewAny PropertyServices' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'view PropertyServices' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'create PropertyServices' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'update PropertyServices' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'delete PropertyServices' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'restore PropertyServices' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'forceDelete PropertyServices' , 'guard_name' => 'api', 'created_at' => now()],

        //Permissions for Room Type
        ['name' => 'viewAny RoomType' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'view RoomType' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'create RoomType' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'update RoomType' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'delete RoomType' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'restore RoomType' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'forceDelete RoomType' , 'guard_name' => 'api', 'created_at' => now()],
 
        //Permissions for Room List
        ['name' => 'viewAny RoomList' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'view RoomList' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'create RoomList' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'update RoomList' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'delete RoomList' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'restore RoomList' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'forceDelete RoomList' , 'guard_name' => 'api', 'created_at' => now()],

        //Permissions for Property Setting
        ['name' => 'viewAny PropertySetting' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'view PropertySetting' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'create PropertySetting' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'update PropertySetting' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'delete PropertySetting' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'restore PropertySetting' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'forceDelete PropertySetting' , 'guard_name' => 'api', 'created_at' => now()],

        //Permissions for CheckIn
        ['name' => 'viewAny CheckIn' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'view CheckIn' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'create CheckIn' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'update CheckIn' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'delete CheckIn' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'restore CheckIn' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'forceDelete CheckIn' , 'guard_name' => 'api', 'created_at' => now()],

        //Permissions for CheckOut
        ['name' => 'viewAny CheckOut' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'view CheckOut' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'create CheckOut' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'update CheckOut' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'delete CheckOut' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'restore CheckOut' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'forceDelete CheckOut' , 'guard_name' => 'api', 'created_at' => now()],

        //Permissions for Payable
        ['name' => 'viewAny Payable' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'view Payable' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'create Payable' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'update Payable' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'delete Payable' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'restore Payable' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'forceDelete Payable' , 'guard_name' => 'api', 'created_at' => now()],

        //Permissions for Property Billing
        ['name' => 'viewAny PropertyBilling' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'view PropertyBilling' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'create PropertyBilling' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'update PropertyBilling' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'delete PropertyBilling' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'restore PropertyBilling' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'forceDelete PropertyBilling' , 'guard_name' => 'api', 'created_at' => now()],

        //Permissions for Payment
        ['name' => 'viewAny Payment' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'view Payment' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'create Payment' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'update Payment' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'delete Payment' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'restore Payment' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'forceDelete Payment' , 'guard_name' => 'api', 'created_at' => now()],

        //Permissions for Advance Payment
        ['name' => 'viewAny AdvancePayment' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'view AdvancePayment' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'create AdvancePayment' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'update AdvancePayment' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'delete AdvancePayment' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'restore AdvancePayment' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'forceDelete AdvancePayment' , 'guard_name' => 'api', 'created_at' => now()],

        //Permissions for Expense
        ['name' => 'viewAny Expense' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'view Expense' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'create Expense' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'update Expense' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'delete Expense' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'restore Expense' , 'guard_name' => 'api', 'created_at' => now()],
        ['name' => 'forceDelete Expense' , 'guard_name' => 'api', 'created_at' => now()],
    ]);

    DB::table('module_has_permissions')->insert([
        //Permissions for Company
        ['name' => 'viewAny Company' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'view Company' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'create Company' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'update Company' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'delete Company' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'restore Company' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'forceDelete Company' , 'module' => 'hms', 'created_at' => now()],

        //Permissions for Property
        ['name' => 'viewAny Property' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'view Property' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'create Property' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'update Property' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'delete Property' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'restore Property' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'forceDelete Property' , 'module' => 'hms', 'created_at' => now()],

        //Permissions for Additional Services
        ['name' => 'viewAny AdditionalServices' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'view AdditionalServices' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'create AdditionalServices' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'update AdditionalServices' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'delete AdditionalServices' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'restore AdditionalServices' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'forceDelete AdditionalServices' , 'module' => 'hms', 'created_at' => now()],

        //Permissions for Property Services
        ['name' => 'viewAny PropertyServices' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'view PropertyServices' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'create PropertyServices' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'update PropertyServices' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'delete PropertyServices' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'restore PropertyServices' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'forceDelete PropertyServices' , 'module' => 'hms', 'created_at' => now()],

        //Permissions for Room Type
        ['name' => 'viewAny RoomType' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'view RoomType' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'create RoomType' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'update RoomType' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'delete RoomType' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'restore RoomType' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'forceDelete RoomType' , 'module' => 'hms', 'created_at' => now()],

        //Permissions for Room List
        ['name' => 'viewAny RoomList' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'view RoomList' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'create RoomList' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'update RoomList' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'delete RoomList' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'restore RoomList' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'forceDelete RoomList' , 'module' => 'hms', 'created_at' => now()],

        //Permissions for Property Setting
        ['name' => 'viewAny PropertySetting' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'view PropertySetting' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'create PropertySetting' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'update PropertySetting' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'delete PropertySetting' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'restore PropertySetting' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'forceDelete PropertySetting' , 'module' => 'hms', 'created_at' => now()],

        //Permissions for CheckIn
        ['name' => 'viewAny CheckIn' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'view CheckIn' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'create CheckIn' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'update CheckIn' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'delete CheckIn' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'restore CheckIn' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'forceDelete CheckIn' , 'module' => 'hms', 'created_at' => now()],

        //Permissions for CheckOut
        ['name' => 'viewAny CheckOut' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'view CheckOut' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'create CheckOut' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'update CheckOut' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'delete CheckOut' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'restore CheckOut' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'forceDelete CheckOut' , 'module' => 'hms', 'created_at' => now()],

        //Permissions for Payable
        ['name' => 'viewAny Payable' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'view Payable' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'create Payable' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'update Payable' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'delete Payable' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'restore Payable' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'forceDelete Payable' , 'module' => 'hms', 'created_at' => now()],

        //Permissions for Property Billing
        ['name' => 'viewAny PropertyBilling' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'view PropertyBilling' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'create PropertyBilling' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'update PropertyBilling' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'delete PropertyBilling' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'restore PropertyBilling' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'forceDelete PropertyBilling' , 'module' => 'hms', 'created_at' => now()],

        //Permissions for Payment
        ['name' => 'viewAny Payment' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'view Payment' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'create Payment' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'update Payment' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'delete Payment' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'restore Payment' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'forceDelete Payment' , 'module' => 'hms', 'created_at' => now()],

        //Permissions for Advance Payment
        ['name' => 'viewAny AdvancePayment' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'view AdvancePayment' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'create AdvancePayment' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'update AdvancePayment' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'delete AdvancePayment' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'restore AdvancePayment' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'forceDelete AdvancePayment' , 'module' => 'hms', 'created_at' => now()],

        //Permissions for Expense
        ['name' => 'viewAny Expense' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'view Expense' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'create Expense' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'update Expense' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'delete Expense' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'restore Expense' , 'module' => 'hms', 'created_at' => now()],
        ['name' => 'forceDelete Expense' , 'module' => 'hms', 'created_at' => now()],
    ]);

    DB::table('route_has_permissions')->insert([
        //Permissions for Company
        ['permission' => 'view Company' , 'route' => 'companies', 'method' => 'GET', 'created_at' => now()],
        ['permission' => 'create Company' , 'route' => 'companies', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'update Company' , 'route' => 'companies', 'method' => 'PUT', 'created_at' => now()],
        ['permission' => 'delete Company' , 'route' => 'companies', 'method' => 'DELETE', 'created_at' => now()],
        ['permission' => 'restore Company' , 'route' => 'restore-companies', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'forceDelete Company' , 'route' => 'force-delete-companies', 'method' => 'POST', 'created_at' => now()],

        //Permissions for Property
        ['permission' => 'view Property' , 'route' => 'properties', 'method' => 'GET', 'created_at' => now()],
        ['permission' => 'create Property' , 'route' => 'properties', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'update Property' , 'route' => 'properties', 'method' => 'PUT', 'created_at' => now()],
        ['permission' => 'delete Property' , 'route' => 'properties', 'method' => 'DELETE', 'created_at' => now()],
        ['permission' => 'restore Property' , 'route' => 'restore-properties', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'forceDelete Property' , 'route' => 'force-delete-properties', 'method' => 'POST', 'created_at' => now()],

        //Permissions for Additional Services
        ['permission' => 'view AdditionalServices' , 'route' => 'additional-services', 'method' => 'GET', 'created_at' => now()],
        ['permission' => 'create AdditionalServices' , 'route' => 'additional-services', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'update AdditionalServices' , 'route' => 'additional-services', 'method' => 'PUT', 'created_at' => now()],
        ['permission' => 'delete AdditionalServices' , 'route' => 'additional-services', 'method' => 'DELETE', 'created_at' => now()],
        ['permission' => 'restore AdditionalServices' , 'route' => 'restore-additional-services', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'forceDelete AdditionalServices' , 'route' => 'force-delete-additional-services', 'method' => 'POST', 'created_at' => now()],

        //Permissions for Property Services
        ['permission' => 'view PropertyServices' , 'route' => 'property-services', 'method' => 'GET', 'created_at' => now()],
        ['permission' => 'create PropertyServices' , 'route' => 'property-services', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'update PropertyServices' , 'route' => 'property-services', 'method' => 'PUT', 'created_at' => now()],
        ['permission' => 'delete PropertyServices' , 'route' => 'property-services', 'method' => 'DELETE', 'created_at' => now()],
        ['permission' => 'restore PropertyServices' , 'route' => 'restore-property-services', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'forceDelete PropertyServices' , 'route' => 'force-delete-property-services', 'method' => 'POST', 'created_at' => now()],

        //Permissions for Room Type
        ['permission' => 'view RoomType' , 'route' => 'room-types', 'method' => 'GET', 'created_at' => now()],
        ['permission' => 'create RoomType' , 'route' => 'room-types', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'update RoomType' , 'route' => 'room-types', 'method' => 'PUT', 'created_at' => now()],
        ['permission' => 'delete RoomType' , 'route' => 'room-types', 'method' => 'DELETE', 'created_at' => now()],
        ['permission' => 'restore RoomType' , 'route' => 'restore-room-types', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'forceDelete RoomType' , 'route' => 'force-delete-room-types', 'method' => 'POST', 'created_at' => now()],

        //Permissions for Room List
        ['permission' => 'view RoomList' , 'route' => 'room-lists', 'method' => 'GET', 'created_at' => now()],
        ['permission' => 'create RoomList' , 'route' => 'room-lists', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'update RoomList' , 'route' => 'room-lists', 'method' => 'PUT', 'created_at' => now()],
        ['permission' => 'delete RoomList' , 'route' => 'room-lists', 'method' => 'DELETE', 'created_at' => now()],
        ['permission' => 'restore RoomList' , 'route' => 'restore-room-lists', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'forceDelete RoomList' , 'route' => 'force-delete-room-lists', 'method' => 'POST', 'created_at' => now()],

        //Permissions for Property Setting
        ['permission' => 'view PropertySetting' , 'route' => 'property-settings', 'method' => 'GET', 'created_at' => now()],
        ['permission' => 'create PropertySetting' , 'route' => 'property-settings', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'update PropertySetting' , 'route' => 'property-settings', 'method' => 'PUT', 'created_at' => now()],
        ['permission' => 'delete PropertySetting' , 'route' => 'property-settings', 'method' => 'DELETE', 'created_at' => now()],
        ['permission' => 'restore PropertySetting' , 'route' => 'restore-property-settings', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'forceDelete PropertySetting' , 'route' => 'force-delete-property-settings', 'method' => 'POST', 'created_at' => now()],

        //Permissions for CheckIn
        ['permission' => 'view CheckIn' , 'route' => 'check-ins', 'method' => 'GET', 'created_at' => now()],
        ['permission' => 'create CheckIn' , 'route' => 'check-ins', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'update CheckIn' , 'route' => 'check-ins', 'method' => 'PUT', 'created_at' => now()],
        ['permission' => 'delete CheckIn' , 'route' => 'check-ins', 'method' => 'DELETE', 'created_at' => now()],
        ['permission' => 'restore CheckIn' , 'route' => 'restore-check-ins', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'forceDelete CheckIn' , 'route' => 'force-delete-check-ins', 'method' => 'POST', 'created_at' => now()],

        //Permissions for CheckOut
        ['permission' => 'view CheckOut' , 'route' => 'check-outs', 'method' => 'GET', 'created_at' => now()],
        ['permission' => 'create CheckOut' , 'route' => 'check-outs', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'update CheckOut' , 'route' => 'check-outs', 'method' => 'PUT', 'created_at' => now()],
        ['permission' => 'delete CheckOut' , 'route' => 'check-outs', 'method' => 'DELETE', 'created_at' => now()],
        ['permission' => 'restore CheckOut' , 'route' => 'restore-check-outs', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'forceDelete CheckOut' , 'route' => 'force-delete-check-outs', 'method' => 'POST', 'created_at' => now()],

        //Permissions for Payable
        ['permission' => 'view Payable' , 'route' => 'payables', 'method' => 'GET', 'created_at' => now()],
        ['permission' => 'create Payable' , 'route' => 'payables', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'update Payable' , 'route' => 'payables', 'method' => 'PUT', 'created_at' => now()],
        ['permission' => 'delete Payable' , 'route' => 'payables', 'method' => 'DELETE', 'created_at' => now()],
        ['permission' => 'restore Payable' , 'route' => 'restore-payables', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'forceDelete Payable' , 'route' => 'force-delete-payables', 'method' => 'POST', 'created_at' => now()],

        //Permissions for Property Billing
        ['permission' => 'view PropertyBilling' , 'route' => 'receivables', 'method' => 'GET', 'created_at' => now()],
        ['permission' => 'create PropertyBilling' , 'route' => 'receivables', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'update PropertyBilling' , 'route' => 'receivables', 'method' => 'PUT', 'created_at' => now()],
        ['permission' => 'delete PropertyBilling' , 'route' => 'receivables', 'method' => 'DELETE', 'created_at' => now()],
        ['permission' => 'restore PropertyBilling' , 'route' => 'restore-receivables', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'forceDelete PropertyBilling' , 'route' => 'force-delete-receivables', 'method' => 'POST', 'created_at' => now()],

        //Permissions for Payment
        ['permission' => 'view Payment' , 'route' => 'payments', 'method' => 'GET', 'created_at' => now()],
        ['permission' => 'create Payment' , 'route' => 'payments', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'update Payment' , 'route' => 'payments', 'method' => 'PUT', 'created_at' => now()],
        ['permission' => 'delete Payment' , 'route' => 'payments', 'method' => 'DELETE', 'created_at' => now()],
        ['permission' => 'restore Payment' , 'route' => 'restore-payments', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'forceDelete Payment' , 'route' => 'force-delete-payments', 'method' => 'POST', 'created_at' => now()],

        //Permissions for Advance Payment
        ['permission' => 'view AdvancePayment' , 'route' => 'advance-payments', 'method' => 'GET', 'created_at' => now()],
        ['permission' => 'create AdvancePayment' , 'route' => 'advance-payments', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'update AdvancePayment' , 'route' => 'advance-payments', 'method' => 'PUT', 'created_at' => now()],
        ['permission' => 'delete AdvancePayment' , 'route' => 'advance-payments', 'method' => 'DELETE', 'created_at' => now()],
        ['permission' => 'restore AdvancePayment' , 'route' => 'restore-advance-payments', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'forceDelete AdvancePayment' , 'route' => 'force-delete-advance-payments', 'method' => 'POST', 'created_at' => now()],

        //Permissions for Expense
        ['permission' => 'view Expense' , 'route' => 'expenses', 'method' => 'GET', 'created_at' => now()],
        ['permission' => 'create Expense' , 'route' => 'expenses', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'update Expense' , 'route' => 'expenses', 'method' => 'PUT', 'created_at' => now()],
        ['permission' => 'delete Expense' , 'route' => 'expenses', 'method' => 'DELETE', 'created_at' => now()],
        ['permission' => 'restore Expense' , 'route' => 'restore-expenses', 'method' => 'POST', 'created_at' => now()],
        ['permission' => 'forceDelete Expense' , 'route' => 'force-delete-expenses', 'method' => 'POST', 'created_at' => now()],
    ]);
    }
}
