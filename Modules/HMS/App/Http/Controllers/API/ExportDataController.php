<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\HMS\Exports\ExportData;
use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\HMS\App\Http\Resources\PayableResource;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Models\Payable;
use Modules\HMS\App\Models\PropertyBilling;
use Modules\HMS\App\Models\Receipt;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Throwable;

class ExportDataController extends Controller
{

    public function exportData(Request $request)
    {
        try {
            $request->validate([
                'table_name' => 'required',
                'where' => 'sometimes|string'
            ]);
            
            $table = $request->input('table_name');
            $where = $request->input('where');
            
            $data = $this->$table($where, $table);
            // dd($data);
            
            $export = new ExportData($data);
            return Excel::download($export, "{$table}_export.xlsx");
        } catch (Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }


    private function payables($where, $table)
    {

        $user = JWTAuth::parseToken()->authenticate();

        $query = Payable::query()
            ->join('check_ins', 'payables.check_in_id', '=', 'check_ins.id')
            ->join('properties', 'payables.property_id', '=', 'properties.id')
            ->select(
                [
                    'property_name', 'check_in_id', 'family_name', 'registeration_number', 'bound_country', 'check_in_date', 'check_in_time', 'check_out_date', 'check_out_time', 'item_name', 'cost',
                    'quantity', 'days',
                    'uom', 'total_amount', 'payment_status'
                ]
            )->selectRaw("CONCAT(DATE_FORMAT(check_out_date, '%Y%m%d'), LPAD(check_ins.id, 5, '0')) AS InvoiceNumber");
        $query->where('company_id', $user->company_id);
        $this->add_where_clause($query, $where, $table);

        return $query->get();
    }

    private function property_billings($where, $table)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $query = PropertyBilling::query()
            ->join('check_ins', 'property_billings.check_in_id', '=', 'check_ins.id')
            ->join('properties', 'property_billings.property_id', '=', 'properties.id')
            ->select(
                [
                    'property_name', 'check_in_id', 'family_name', 'registeration_number', 'bound_country', 'check_in_date', 'check_in_time', 'check_out_date', 'check_out_time', 'item_name', 'selling_price',
                    'quantity', 'days',
                    'uom', 'total_amount', 'payment_status'
                ]
            )->selectRaw("CONCAT(DATE_FORMAT(check_out_date, '%Y%m%d'), LPAD(check_ins.id, 5, '0')) AS InvoiceNumber");
        $query->where('company_id', $user->company_id);
        $this->add_where_clause($query, $where, $table);
        return $query->get();
    }


    private function batch_export($where, $table)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $query = PropertyBilling::query()
            ->join('check_ins', 'property_billings.check_in_id', '=', 'check_ins.id')
            ->join('properties', 'property_billings.property_id', '=', 'properties.id')
            ->select(
                [
                    'property_name', 'check_in_id', 'family_name', 'registeration_number', 'bound_country', 'check_in_date', 'check_in_time', 'check_out_date', 'check_out_time', 'item_name', 'selling_price',
                    'quantity', 'days',
                    'uom', 'total_amount', 'payment_status'
                ]
            )->selectRaw("CONCAT(DATE_FORMAT(check_out_date, '%Y%m%d'), LPAD(check_ins.id, 5, '0')) AS InvoiceNumber");
        $query->where('company_id', $user->company_id);
        $this->add_where_clause($query, $where, $table);
        return $query->get();
    }

    private function check_ins($where, $table)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $query = CheckIn::query()
            ->join('checked_in_members', 'check_ins.id', '=', 'checked_in_members.check_in_id')
            ->join('properties', 'check_ins.property_id', '=', 'properties.id')
            ->join('room_lists', 'checked_in_members.room_number', '=', 'room_lists.id')
            ->join('property_services', function ($join) {
                $join->whereRaw('FIND_IN_SET(property_services.id, check_ins.selected_services) > 0');
            })
            ->select(
                [
                    'property_name', 'family_name', 'registeration_number', 'bound_country', 'service_name as additional_services', 'check_ins.check_in_date', 'check_ins.check_in_time', 'check_ins.check_out_date', 'check_ins.check_out_time', 'guest_name', 'date_of_birth',
                    'room_lists.room_number', 'cnic_passport_number', 'visa_expiry'
                ]
            )
            ->selectSub(function ($query) {
                $query->selectRaw('COUNT(DISTINCT checked_in_members.room_number)')->from('checked_in_members')
                    ->whereColumn('check_ins.id', '=', 'checked_in_members.check_in_id');
            }, 'family_rooms');
        $query->where('company_id', $user->company_id);
        $this->add_where_clause($query, $where, $table);

        return $query->get();
    }

    private function export_active_checkins($where, $table)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $query = CheckIn::query()
            ->join('properties', 'check_ins.property_id', '=', 'properties.id')
            ->join('property_services', function ($join) {
                $join->whereRaw('FIND_IN_SET(property_services.id, check_ins.selected_services) > 0');
            })
            ->select(
                'check_ins.property_id',
                'properties.property_name',
                'check_ins.family_name',
                'check_ins.registeration_number',
                'service_name as additional_services',
                'check_ins.check_out_date',
                'check_ins.check_out_time'
            );
            $query->where('company_id', $user->company_id);
            $this->add_where_clause($query, $where, 'check_ins');
            return $query->get();
        } catch (Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
    private function add_where_clause(&$query, $where, $table)
    {

        if (!empty($where)) {
            $where = (!is_array($where)) ? json_decode($where) : $where;

            foreach ($where as $field => $value) {
                if ($field == 'bound_country' && $table != 'check_ins') {
                    $query->where('check_ins' . "." . $field, $value);
                } else {
                    $query->where($table . "." . $field, $value);
                }
            }
        }
    }

    private function processQuery($ids = null)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $ids = ($ids == null) ? "" : "WHERE check_in_id IN ($ids)";
        $query = DB::select("SELECT 
       
        f.family_name,
        f.registeration_number,
        property_name,
        property_type,
        category as property_category,
        f.check_in_ids as merge_ids,
        bound_country,
        CASE
            WHEN present_status = 'checked_out' THEN present_status
            ELSE present_status
        END AS present_status,
        min(check_in_date) as check_in_date,
        CASE
            WHEN min(check_in_date) THEN check_in_time
        END AS check_in_time,
        max(check_out_date) as check_out_date,
        CASE
            WHEN min(check_out_date) THEN check_out_time
        END AS check_out_time,
        f.created_at as merging_date,
        check_in_id,
        selling_price,
        uom,
        f.check_in_ids,
        CASE
        WHEN MIN(billing_id) THEN item_name
        END AS item_name,
        item_name,
        IF (count(distinct check_in_date) > 1, max(quantity),SUM(quantity)) as quantity,
        IF (count(distinct check_in_date) > 1, SUM(service_duration),max(service_duration)) as `days`,
        IF (count(distinct check_in_date) > 1, SUM(stay_duration),max(stay_duration)) as stay_duration,
        max(total_persons) as total_persons,
        SUM(total_amount) as total_amount
         
    FROM
    (SELECT 

        b.id as billing_id,
        c.family_name,
        c.registeration_number,
        c.present_status,
        c.bound_country,
        c.check_in_date,
        c.check_in_time,
        c.check_out_date,
        c.check_out_time,
        property_name,
        property_type,
        p.category,
        p.id as p_id,
        b.uom,
        b.assigned_additional_service_id,
        b.check_in_id,
        CASE
        WHEN b.discount = 0 THEN b.item_name
        END AS item_name,
        b.selling_price,
        CASE
            WHEN b.assigned_additional_service_id = 0 THEN b.days
        END AS stay_duration,
        b.discount,
        SUM(CASE
        WHEN b.days > 0  THEN b.quantity
        END) AS quantity,
        SUM(CASE
            WHEN b.quantity > 0  THEN b.days
        END) AS service_duration,
        c.total_persons,
        SUM(CASE
            WHEN total_amount <= 0 THEN total_amount
        END) AS billing_rules_discount,
        SUM(total_amount) as total_amount
        
    FROM
        property_billings b 
            JOIN
        check_ins c ON b.check_in_id = c.id
            JOIN
        properties p ON b.property_id = p.id
        $ids
    GROUP BY 
    CASE
        WHEN b.assigned_additional_service_id = 0 THEN CONCAT(b.check_in_id, '_', b.assigned_additional_service_id, '_',  b.selling_price)
        WHEN b.assigned_additional_service_id != 0 THEN CONCAT(b.check_in_id, '_', b.assigned_additional_service_id)
        ELSE CONCAT(b.check_in_id, '_',  b.assigned_additional_service_id, '_', b.selling_price)
    END
    ) invoice
    -- b.check_in_id, b.assigned_additional_service_id, b.selling_price) inovice
    JOIN
        family_generated_bills f ON FIND_IN_SET(check_in_id, f.check_in_ids) > 0 AND merged = 1 AND f.company_id = $user->company_id
        where total_amount > 0
    GROUP BY CASE
        WHEN assigned_additional_service_id = 0 THEN CONCAT(f.check_in_ids, '_', assigned_additional_service_id)
        WHEN assigned_additional_service_id != 0 THEN CONCAT(f.check_in_ids, '_', invoice.item_name)
        ELSE CONCAT(f.check_in_ids, '_',  invoice.item_name)
    END
    ");
        return $query;
    }

    public function export_batch($where, $table = null)
    {
        try {

            if (!empty($where)) {
                $where = (!is_array($where)) ? json_decode($where) : $where;
                $results =[];
                if (isset($where->batch_id)) {
                    $receipt = Receipt::where('id', $where->batch_id)->first();

                    if ($receipt == null) throw new \Exception('No records found against the selected batch');


                   $results = $this->processQuery($receipt['check_in_ids']);
                }
            } else {
                $results = $this->processQuery(null);
            }

            return Checkin::hydrate($results);

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
