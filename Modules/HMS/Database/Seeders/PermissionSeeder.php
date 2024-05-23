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
            [
                "permission_title" => "create_checkins",
                "permission" => "create",
                "route" => "checkins",
            ],
            [
                "permission_title" => "update_checkins",
                "permission" => "update",
                "route" => "checkins",
            ],
            [
                "permission_title" => "delete_checkins",
                "permission" => "delete",
                "route" => "delete-checkins",
            ],
            [
                "permission_title" => "create_checkouts",
                "permission" => "create",
                "route" => "checkouts",
            ],
            [
                "permission_title" => "create_relocation",
                "permission" => "create",
                "route" => "checkins",
            ],
            [
                "permission_title" => "create_reverse_checkouts",
                "permission" => "create",
                "route" => "reverse-checkouts",
            ],
            [
                "permission_title" => "create_missing_bills",
                "permission" => "create",
                "route" => "generated-bills",
            ],
            [
                "permission_title" => "create_merge_bills",
                "permission" => "create",
                "route" => "store-bills",
            ],
            [
                "permission_title" => "create_batch_invoices",
                "permission" => "create",
                "route" => "receipts-invoices",
            ],
            [
                "permission_title" => "create_process_payments",
                "permission" => "create",
                "route" => "payments",
            ],
            [
                "permission_title" => "create_expense",
                "permission" => "create",
                "route" => "expenses",
            ],
            [
                "permission_title" => "create_additional_services",
                "permission" => "create",
                "route" => "additional-services",
            ],
            [
                "permission_title" => "update_additional_services",
                "permission" => "update",
                "route" => "additional-services",
            ],
            [
                "permission_title" => "delete_additional_services",
                "permission" => "delete",
                "route" => "additional-services",
            ],
            [
                "permission_title" => "create_property_services",
                "permission" => "create",
                "route" => "property-services",
            ],
            [
                "permission_title" => "update_property_services",
                "permission" => "update",
                "route" => "property-services",
            ],
            [
                "permission_title" => "delete_property_services",
                "permission" => "delete",
                "route" => "property-services",
            ],
            [
                "permission_title" => "create_room_types",
                "permission" => "create",
                "route" => "room-types",
            ],
            [
                "permission_title" => "update_room_types",
                "permission" => "update",
                "route" => "room-types",
            ],
            [
                "permission_title" => "delete_room_types",
                "permission" => "delete",
                "route" => "room-types",
            ],
        ]);
    }
}
