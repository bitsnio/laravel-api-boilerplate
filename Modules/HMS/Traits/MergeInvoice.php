<?php

namespace Modules\HMS\Traits;

use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Models\familyGeneratedBill;
use Modules\HMS\App\Models\PropertyBilling;
use Modules\HMS\App\Models\PropertySetting;
use App\Models\User;
use Modules\HMS\App\Utilities\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

trait MergeInvoice{
// private $family_invoices = [];
// private $current_family_invoices = [];
// private $merge_invoices = [];
private $merge_bills = [];
private $check_in_records = [];
private $property_billings = [];
private $user;
private $invoices = [];
private $index = 0;


    public function mergeFamilyBills(Request $request){
        try{
            $user = JWTAuth::parseToken()->authenticate();
            $this->family_invoices = familyGeneratedBill::where('company_id', $user->company_id)
            ->whereNotIn('check_in_ids', function ($query) {
                $query->select(DB::raw(1))
                    ->from('receipts')
                    ->whereRaw('FIND_IN_SET(family_generated_bills.check_in_ids, receipts.check_in_ids) ')
                    ->where('receipt_type', 'receivable')
                    ->where('is_deleted', 0);
            })
            ->join('invoices',DB::raw("FIND_IN_SET(invoices.check_in_id,family_generated_bills.check_in_ids)"),">",DB::raw("'0'"))
            ->where('invoices.invoice_type', 'property_billings')
            ->select(
                'family_generated_bills.id as merge_id', 
                'family_generated_bills.family_name', 
                'family_generated_bills.registeration_number',
                'family_generated_bills.check_in_ids',
                'family_generated_bills.total_merged_amount',
                'family_generated_bills.company_id',
                'invoices.*' 
                )            
            ->get()->toArray();
            $this->mergeGroupBills();
            // return Helper::successResponse($response);
            // dd($response);
        }
        catch(Throwable $th){
            throw new \Exception($th->getMessage());
        }
    }
    public function mergeGroupBills(){
        try{
            $group_records = collect($this->family_invoices)->groupBy('check_in_ids')->toArray();
            $index = 0;
            foreach($group_records as $record){
                $this->current_family_invoices = $record;
                $this->familyDetails($index);
                $identical_records = collect($record)->groupBy('item_name')->map(function ($q){
                    return collect($q)->groupBy('unit_price')->toArray();
                })->toArray();
                foreach($identical_records as $item){
                    $item = collect($item)->flatten(1)->toArray();
                    $this->addRow($item, $index);
                }
                $index++;
            }
        }
        catch(Throwable $th){
            throw new \Exception($th->getMessage());
        }
    }
    // public function familyDetails($index){
    //     try{
    //         $data = collect($this->current_family_invoices)->first();
    //         $min_check_in_date = collect($this->current_family_invoices)->sortBy('check_in_date')->first();
    //         $max_check_out_date = collect($this->current_family_invoices)->sortByDesc('check_out_date')->first();
    //         $this->merge_invoices[$index] =  [
    //             'family_name' => $data['family_name'],
    //             'registeration_number' => $data['registeration_number'],
    //             'check_in_ids' => $data['check_in_ids'],
    //             'property_id' => implode(',', collect($this->current_family_invoices)->pluck('property_id')->unique()->toArray()),
    //             'check_in_date' => $min_check_in_date['check_in_date'],
    //             'check_in_time' => $min_check_in_date['check_in_time'],
    //             'check_out_date' => $max_check_out_date['check_out_date'],
    //             'check_out_time' => $max_check_out_date['check_out_time'],
    //             'total_persons' => collect($this->current_family_invoices)->max('total_persons'),
    //             'total_rooms' => collect($this->current_family_invoices)->max('total_rooms'),
    //             'total_family_discount' =>  collect($this->current_family_invoices)->sum('total_discount'),
    //             'total_family_amount' =>  collect($this->current_family_invoices)->sum('total_amount'),
    //             'invoice_number' => date("Ymd", strtotime($max_check_out_date['check_out_date'])) . str_pad($data['merge_id'], 5, "0", STR_PAD_LEFT),
    //         ]; 
    //     }
    //     catch(Throwable $th){
    //         throw new \Exception($th->getMessage());
    //     }
    // }

    public function addRow($records, $index){
        try{
            $data = collect($records)->first();
            $days_quantity = $this->daysAndQuantity($records);
            $this->merge_invoices[$index]['invoice'][] = [
                'item_name' => $data['item_name'],
                'unit_price' => $data['unit_price'],
                'uom' => $data['uom'],
                'days' => $days_quantity['days'],
                'quantity' => $days_quantity['quantity'],
                'total_amount' => collect($records)->sum('total_amount'),
                'billing_rule_discount' => collect($records)->sum('total_discount'),
            ];
        }
        catch(Throwable $th){
            throw new \Exception($th->getMessage());
        }
    }
    public function daysAndQuantity($data){
        try{
            $days = 0;
            $quantity = 0;
            $group_record = collect($data)->groupBy('check_in_date')->toArray();
            foreach($group_record as $record){
                $days += $record[0]['days'];
                $quantity += $record[0]['quantity'];
            }
            return ['days' => $days, 'quantity' => $quantity];
        }
        catch(Throwable $th){
            throw new \Exception($th->getMessage());
        }
    }

    public function mergeRecords(){
        $user = JWTAuth::parseToken()->authenticate();
        $query = DB::select("SELECT 
        f.created_at as merging_date,
        f.family_name,
        billing_id,
        property_name as title,
        property_type,
        category as property_category,
        p_id as property_id,
        f.check_in_ids,
        f.total_merged_amount,
        f.company_id,
        f.id as merge_id,
        assigned_additional_service_id,
        bound_country,
        f.company_id,
        f.registeration_number,
        CASE
            WHEN present_status = 'checked_out' THEN present_status
            ELSE present_status
        END AS present_status,
        min(check_in_date_time) as check_in_date_time,
        max(check_out_date_time) as check_out_date_time,
        check_in_id,
        selling_price,
        uom,
        f.check_in_ids,
        discount,
        CASE
        WHEN MIN(billing_id) THEN item_name
        END AS item_name,
        item_name,
        IF (count(distinct check_in_date) > 1 OR count(distinct check_in_time) > 1, max(quantity),SUM(quantity)) as quantity,
        IF (count(distinct check_in_date) > 1 OR count(distinct check_in_time) > 1, SUM(service_duration),max(service_duration)) as `days`,
        IF (count(distinct check_in_date) > 1, SUM(stay_duration),max(stay_duration)) as stay_duration,
        max(total_persons) as total_persons,
        SUM(total_amount) as total_amount,
        billing_rules_discount    
    FROM
    (SELECT 

        b.id as billing_id,
        c.family_name,
        c.registeration_number,
        c.present_status,
        c.bound_country,
        concat(c.check_in_date, ' ', c.check_in_time) as check_in_date_time,
        concat(c.check_out_date, ' ', c.check_out_time) as check_out_date_time,
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
    GROUP BY 
    CASE
        WHEN b.assigned_additional_service_id = 0 THEN CONCAT(b.check_in_id, '_', b.assigned_additional_service_id, '_',  b.selling_price)
        WHEN b.assigned_additional_service_id != 0 THEN CONCAT(b.check_in_id, '_', b.assigned_additional_service_id)
        ELSE CONCAT(b.check_in_id, '_',  b.assigned_additional_service_id, '_', b.selling_price)
    END
    ) invoice
    -- b.check_in_id, b.assigned_additional_service_id, b.selling_price) inovice
    JOIN
        family_generated_bills f ON FIND_IN_SET(check_in_id, f.check_in_ids) > 0 AND f.merged = 0
        where total_amount > 0
    GROUP BY CASE
        WHEN assigned_additional_service_id = 0 THEN CONCAT(f.check_in_ids, '_', assigned_additional_service_id)
        WHEN assigned_additional_service_id != 0 THEN CONCAT(f.check_in_ids, '_', invoice.item_name)
        ELSE CONCAT(f.check_in_ids, '_',  invoice.item_name)
    END
    ");
    $records = collect($query)->where('company_id', $user->company_id)->groupBy('check_in_ids')->toArray();
    $invoices = [];
    $i = 0;
    foreach ($records as $record){
        $details = [];
        $property = [];
        $firstRecord = get_object_vars($record[0]);
        $property['title'] = $firstRecord['title'];
        $property['id'] = $firstRecord['property_id'];
        $property['category'] = $firstRecord['property_category'];
        $property['property_type'] = $firstRecord['property_type'];
        $invoices[$i]['id'] = $firstRecord['merge_id'];
        $invoices[$i]['company_id'] = $firstRecord['company_id'];
        $invoices[$i]['registeration_number'] = $firstRecord['registeration_number'];
        $invoices[$i]['family_name'] = $firstRecord['family_name'];
        $invoices[$i]['check_in_ids'] = $firstRecord['check_in_ids'];
        $invoices[$i]['total_merged_amount'] = $firstRecord['total_merged_amount'];
        $invoices[$i]['check_out_date'] = date('d-m-Y',strtotime((collect($record)->max('check_out_date_time'))));
        $invoices[$i]['merge_date'] = ($firstRecord['merging_date'] == null) ? 'Not Available' : date('m-d-y h:i A',strtotime($firstRecord['merging_date']));
        $details['total_rooms'] = collect( $record)->where('stay_duration','!=', null)->pluck('quantity')->first();
        $details['registeration_number'] = $firstRecord['registeration_number'];
        $details['family_name'] = $firstRecord['family_name'];
        $details['present_status'] = $firstRecord['present_status'];
        $details['invoice_number'] = date("Ymd", strtotime(date('d-m-Y',strtotime((collect($record)->max('check_out_date_time')))))) . str_pad($firstRecord['merge_id'], 5, "0", STR_PAD_LEFT);
        $details['total_persons'] = $firstRecord['total_persons'];
        $details['check_in_date'] = date('d-m-Y',strtotime((collect($record)->min('check_in_date_time'))));
        $details['check_in_time'] = date('h:i A',strtotime((collect($record)->min('check_in_date_time'))));
        $details['check_out_date'] = date('d-m-Y',strtotime((collect($record)->max('check_out_date_time'))));
        $details['check_out_time'] = date('h:i A',strtotime((collect($record)->max('check_out_date_time'))));
        $details['property'] = $property;
        $details['days'] = collect( $record)->where('stay_duration','!=', null)->pluck('stay_duration')->first();
        $details['nights'] = $details['days'];
        $invoices[$i]['details'] = $details;
        $invoices[$i]['property_billings'] = collect($record)->sortBy('assigned_additional_service_id')->flatten(1)->map(function ($q) use ($record){
            return [
                'assigned_additional_service_id' => $q->assigned_additional_service_id,
                'item_name' => ($q->assigned_additional_service_id == 0) ? 'Room Rent' : $q->item_name,
                'selling_price' => $q->selling_price,
                'days' => $q->days,
                'uom' => $q->uom,
                'final_amount' => $q->total_amount,
                'billing_rules_discount' => $q->billing_rules_discount,
                'total_amount' => $q->total_amount + $q->billing_rules_discount,
                'date' => date('d-m-Y',strtotime((collect($record)->min('check_in_date_time')))).' to '.date('d-m-Y',strtotime((collect($record)->max('check_out_date_time')))),
                'quantity' => $q->quantity,
            ];
        })->toArray();
        $invoices[$i]['invoices'] = [];
        $i++;
    }
    $extraFields = [];
    $extraFields['total_amount'] = collect($records)->flatten(1)->sum('total_amount');
    return ['data' => $invoices, 'extra_fields' => $extraFields];
    }

    public function mergeBills(){
        try{
            $this->user = JWTAuth::parseToken()->authenticate();
            $company_id = $this->user->company_id;
            $this->merge_bills = familyGeneratedBill::where('company_id', $company_id)->where('merged', 0)->get()->toArray();
            $check_in_ids = collect($this->merge_bills)->pluck('check_in_ids')->map(function ($q){
                return explode(',', $q);
            })->flatten()->toArray();
            $this->property_billings = PropertyBilling::whereIn('check_in_id', $check_in_ids)->get()->toArray();
            $this->check_in_records = CheckIn::with(['properties'])->whereIn('id', $check_in_ids)
            ->whereHas('properties', function ($q) use ($company_id) {
                $q->where('company_id', '=', $company_id);
                $q->where('is_deleted', '=', 0);
            })->get()->toArray();
            
            $this->create_merge_invoices();
            dd(100);
        }
        catch(Throwable $th){
            throw new \Exception($th->getMessage());
        }
    }

    public function create_merge_invoices(){
        try{
            foreach($this->merge_bills as $bill){
                $this->familyRecords($bill);
                $this->invoice_data($bill);
                // dd($bill);
            }
            dd($this->invoices);
        }
        catch(Throwable $th){
            throw new \Exception($th->getMessage());
        }
    }
    private function familyRecords($bill){
        try{
           
            $ids = explode(',', $bill['check_in_ids']);
            $data = collect($this->check_in_records)->first();
            $rooms = collect($this->property_billings)->whereIn('check_in_id', $ids)->where('assigned_additional_service_id', 0)->groupBy('check_in_id')->map(function ($r){
                return ['quantity' => $r->sum('quantity')];
            })->flatten(1)->max();
            $min_check_in_date = collect($this->check_in_records)->whereIn('id', $ids)->sortBy('check_in_date')->first();
            $max_check_out_date = collect($this->check_in_records)->whereIn('id', $ids)->sortByDesc('check_out_date')->first();
            $this->invoices[$this->index] =  [
                'family_name' => $data['family_name'],
                'registeration_number' => $data['registeration_number'],
                'check_in_ids' => $bill['check_in_ids'],
                'property_id' => implode(',', collect($this->check_in_records)->whereIn('id', $ids)->pluck('property_id')->unique()->toArray()),
                'check_in_date' => $min_check_in_date['check_in_date'],
                'check_in_time' => $min_check_in_date['check_in_time'],
                'check_out_date' => $max_check_out_date['check_out_date'],
                'check_out_time' => $max_check_out_date['check_out_time'],
                'total_persons' => collect($this->check_in_records)->whereIn('id', $ids)->max('total_persons'),
                'total_rooms' => $rooms,
                'total_family_discount' =>  collect($this->property_billings)->whereIn('check_in_id', $ids)->where('total_amount', '<', 0)->sum('total_discount'),
                'total_family_amount' =>  collect($this->property_billings)->whereIn('check_in_id', $ids)->sum('total_amount'),
                'invoice_number' => date("Ymd", strtotime($max_check_out_date['check_out_date'])) . str_pad($bill['id'], 5, "0", STR_PAD_LEFT),
            ];
        }
        catch(Throwable $th){
            throw new \Exception($th->getMessage());
        }
    }
    private function invoice_data($bill){
        try{
            $ids = explode(',', $bill['check_in_ids']);
            $bills = collect($this->property_billings)->whereIn('check_in_id', $ids)->groupBy('check_in_id')->toArray();
            foreach ($bills as $data){
                $count = collect($data)->groupBy('property_id')->count();
                $this->invoices[$this->index]['invoice'] = collect($data)->groupBy('assigned_additional_service_id')->map(function ($f){
                    return [
                        'item_name' => $f->where('discount', 0)->pluck('item_name')->first(),
                        'unit_price' => $f->pluck('selling_price')->first(),
                        'uom' => $f->pluck('uom')->first(),
                        'days' => $f->sum('days'),
                        'quantity' => $f->sum('quantity'),
                        'total_amount' => $f->sum('total_amount'),
                    ];
                })->groupBy(['item_name', 'selling_price'])->flatten(1)->map(function ($m) use ($count){
                    return [
                        'item_name' => $m->pluck('item_name')->first(),
                        'unit_price' => $m->pluck('unit_price')->first(),
                        'days' => ($count > 1) ? $m->sum('days') : $m->pluck('days')->max(),
                        'quantity' => ($count > 1) ? $m->pluck('quantity')->max() : $m->sum('quantity'),
                        'uom' => $m->pluck('uom')->first(),
                        'total_amount' => $m->sum('total_amount'),
                    ];
                })->toArray();
            }
            $this->index++;
        }
        catch(Throwable $th){
            throw new \Exception($th->getMessage());
        }
    }
}