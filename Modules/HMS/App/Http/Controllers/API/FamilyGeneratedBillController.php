<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\familyGeneratedBill;
use Modules\HMS\App\Http\Requests\StorefamilyGeneratedBillRequest;
use Modules\HMS\App\Http\Requests\UpdatefamilyGeneratedBillRequest;
use Modules\HMS\App\Http\Resources\BaseResource;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Models\PropertyBilling;
use Modules\HMS\App\Models\Receipt;
use Modules\HMS\Traits\MergeInvoice;
use Modules\HMS\App\Utilities\Helper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class FamilyGeneratedBillController extends Controller
{
    use MergeInvoice;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            $res = $this->mergeRecords();
            return Helper::successResponse($res);
            $user = JWTAuth::parseToken()->authenticate();
            $merge_bills = familyGeneratedBill::where('company_id', $user->company_id)->whereIn('id',[2199, 2197, 2191, 2195, 2188, 2193, 2190, 2198, 2196, 2185, 2186])->get()->toArray();
            $receipts = Receipt::where('company_id', $user->company_id)->where('receipt_type', 'receivable')->get()->pluck('check_in_ids')->toArray();
            
            $records = BaseResource::collection($merge_bills);
            $array = [];
            foreach($records as $record){
                $checkin_ids = explode(',', $record['check_in_ids']);
                $result = app(PropertyBillingController::class)->mergeBill($checkin_ids, $record['id']);
                if(isset($result['error'])){
                    return Helper::errorResponse($result['error']);
                }
                
                $record['property_billings'] = $result['Bill_array'];
                $data['total_rooms'] = collect( $result['Bill_array'])->where('assigned_additional_service_id', 0)->sum('quantity');
                $data['registeration_number'] = $result['registeration_number'];
                // $status = CheckIn::where('registeration_number', $result['registeration_number'])->where('check_in_status', 'active')->get()->toArray();
                $data['family_name'] = $result['family_name'];
                $data['present_status'] = $result['present_status'];
                // $data['total_merged_amount'] = $result['total_merged_amount'];
                $data['invoice_number'] = $result['invoice_number'];
                $data['total_persons'] = $result['total_persons'];
                $data['check_in_date'] = $result['check_in_date'];
                $data['check_in_time'] = $result['check_in_time'];
                // $data['check_out_date'] = (empty($status)) ? $result['check_out_date'] : 'continue';
                $data['check_out_date'] = $result['check_out_date'];
                $data['check_out_time'] = $result['check_out_time'];
                $data['property'] = $result['property'];
                $data['days'] = $result['days'];
                $data['nights'] = $result['nights'];
                $record['details'] = $data;
                $response = app(PropertyBillingController::class)->getReceivables($checkin_ids);
                $record['invoices'] = $response;
                if(isset($response['data']['error'])){Helper::errorResponse($response['data']['error']);}
                $array[] = $record;
            }
            $extraFields = [];
            $extraFields['total_amount'] = collect($array)->sum('total_merged_amount');
            return Helper::successResponse(['data' => $array, 'extra_fields' => $extraFields]);
        }
        catch(Throwable $th){
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorefamilyGeneratedBillRequest $request)
    {
        DB::beginTransaction();
        try{
            if(!isset($request->check_in_id)){
                return Helper::errorResponse('No record found to merge');
            }
            $check_in_ids = $request->check_in_id;
            $query = familyGeneratedBill::select('check_in_ids');
            if(is_array($check_in_ids)){
                foreach($check_in_ids as $ids){
                    $query->orWhereRaw("FIND_IN_SET($ids,check_in_ids)");
                }
            }
            $res = $query->get()->toArray();
            if(!empty($res)){
                return Helper::errorResponse('Some of selectes bills are already merged, please select valid bills and try again');
            }
            $checkin_ids = $request->check_in_id;
            $check_ins = CheckIn::whereIn('id', $checkin_ids)->get()->toArray();
            if(empty($check_ins)){
                return Helper::errorResponse('No record found for against the selected invoices');
            }
            $family_record = collect($check_ins)->groupBy('registeration_number')->toArray();
            $user = JWTAuth::parseToken()->authenticate(); 
            $data['company_id'] = $user->company_id;
            $data['created_by'] = $user->created_by;
            $payload = [];
            foreach($family_record as $record){
                $ids = collect($record)->pluck('id')->toArray();
                $data['check_in_ids'] = implode(',', $ids);
                $result = app(PropertyBillingController::class)->mergeBill($ids);
                if(isset($result['error'])){
                    return Helper::errorResponse($result['error']);
                }
                $data['family_name'] = $result['family_name'];
                $data['registeration_number'] = $result['registeration_number'];
                $data['total_merged_amount'] = $result['total_merged_amount'];
                unset($result['total_merged_amount']);
                $data['created_at'] = now();
                $payload[] = $data;
            }
            DB::table('family_generated_bills')->insert($payload);
            PropertyBilling::whereIn('check_in_id', $checkin_ids)->update(['merged' => 1]);
            DB::commit();
            return Helper::successResponse('successfully merged');
        }
        catch(Throwable $th){
            DB::rollback();
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(familyGeneratedBill $familyGeneratedBill)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(familyGeneratedBill $familyGeneratedBill)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatefamilyGeneratedBillRequest $request, familyGeneratedBill $familyGeneratedBill)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(familyGeneratedBill $familyGeneratedBill)
    {
        //
    }

    public function showBills(Request $request){
        try{
            if(!isset($request->check_in_id)){
                return Helper::errorResponse('no merge bills found against selected record');
            }
            $array = [];
            foreach($request->check_in_id as $data){
                if($data == null){
                    return Helper::errorResponse('request cannot be null');
                }
                $checkin_ids = explode(',', $data);
                $result = app(PropertyBillingController::class)->mergeBill($checkin_ids);
                if(isset($result['error'])){
                    return Helper::errorResponse($result['error']);
                }
                unset($result['total_merged_amount']);
                $array[] = $result;
            }
            return Helper::successResponse($array);
        }
        catch(Throwable $th){
            return Helper::errorResponse($th->getMessage());
        }
    }

    public function mergeFamilyBills(Request $request){
        try{
            $user = JWTAuth::parseToken()->authenticate();
            $family_bills = familyGeneratedBill::where('company_id', $user->company_id)
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
            ->get();
            $response = $this->mergeGroupBills($family_bills);
            return Helper::successResponse($response);
            dd($response);
        }
        catch(Throwable $th){
            throw new Exception($th->getMessage());
        }
    }
    public function mergeGroupBills($records){
        try{
            $group_records = $records->groupBy('check_in_ids')->toArray();
            $merge_invoices = [];
            $i = 0;
            foreach($group_records as $record){
                $merge_invoices[$i] = $this->familyDetails($record);
                $identical_records = collect($record)->groupBy('item_name')->map(function ($q){
                    return collect($q)->groupBy('unit_price')->toArray();
                })->toArray();
                foreach($identical_records as $item){
                    $item = collect($item)->flatten(1)->toArray();
                    $merge_invoices[$i]['invoice'][] = $this->addRow($item);
                }
                $i++;
            }
            return $merge_invoices;
        }
        catch(Throwable $th){
            throw new Exception($th->getMessage());
        }
    }
    public function familyDetails($record){
        try{
            $data = collect($record)->first();
            $min_check_in_date = collect($record)->sortBy('check_in_date')->first();
            $max_check_out_date = collect($record)->sortByDesc('check_out_date')->first();
            return [
                'family_name' => $data['family_name'],
                'registeration_number' => $data['registeration_number'],
                'check_in_ids' => $data['check_in_ids'],
                'property_id' => implode(',', collect($record)->pluck('property_id')->unique()->toArray()),
                'check_in_date' => $min_check_in_date['check_in_date'],
                'check_in_time' => $min_check_in_date['check_in_time'],
                'check_out_date' => $max_check_out_date['check_out_date'],
                'check_out_time' => $max_check_out_date['check_out_time'],
                'total_persons' => collect($record)->max('total_persons'),
                'total_rooms' => collect($record)->max('total_rooms'),
                'total_family_discount' =>  collect($record)->sum('total_discount'),
                'total_family_amount' =>  collect($record)->sum('total_amount'),
                'invoice_number' => date("Ymd", strtotime($max_check_out_date['check_out_date'])) . str_pad($data['merge_id'], 5, "0", STR_PAD_LEFT),
            ];
        }
        catch(Throwable $th){
            throw new Exception($th->getMessage());
        }
    }

    public function addRow($records){
        try{
            $data = collect($records)->first();
            $days_quantity = $this->daysAndQuantity($records);
            return [
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
            throw new Exception($th->getMessage());
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
            throw new Exception($th->getMessage());
        }
    }
}
