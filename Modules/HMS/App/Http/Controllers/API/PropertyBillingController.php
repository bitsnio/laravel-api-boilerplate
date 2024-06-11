<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Illuminate\Http\Request;
use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\PropertyBilling;
use Modules\HMS\App\Http\Requests\StorePropertyBillingRequest;
use Modules\HMS\App\Http\Requests\UpdatePropertyBillingRequest;
use Modules\HMS\App\Http\Resources\BaseResource;
use Modules\HMS\App\Http\Resources\PropertyBillingResource;
use Modules\HMS\App\Models\AdvancePayment;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Models\Expense;
use Modules\HMS\App\Models\Property;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Support\Facades\DB;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class PropertyBillingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try { 
            $response = $this->getReceivables(null, $request);
            // dd($response);
            if(isset($response['data']['error'])){Helper::errorResponse($response['data']['error']);}
            return (isset($response['error'])) ? Helper::errorResponse($response['error']):Helper::successResponse(['extra_fields' => $response['extra_fields'], 'data' => PropertyBillingResource::collection($response['data'])]);
            }
            catch (\Throwable $th) {
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
    public function store(StorePropertyBillingRequest $request)
    {
        try{
            
            $propertyBilling = $request->validated();
            $check_in_id =  $propertyBilling['check_in_id'];
            // return $check_in_id;
            $userID = JWTAuth::parseToken()->authenticate();
            $propertyBilling["created_by"] = $userID->id;
            PropertyBilling::create($propertyBilling);

            $d = CheckIn::with(['propertyBillings', 'property', 'guests'])
            ->whereHas('propertyBillings', function($q) use ( $check_in_id) {
                    $q->where('check_in_id', $check_in_id );
                })->get();
            $total_record_function = app(PayableController::class);
            $response_total_record = $total_record_function->totalRecord($d, 'property_billings');

            return Helper::successResponse(BaseResource::make($response_total_record));
        } catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PropertyBilling $propertyBilling)
    {
        try{  
            return Helper::successResponse( PropertyBilling::make($propertyBilling));
        }
            catch (\Throwable $th) {
                return Helper::errorResponse($th->getMessage());
            }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PropertyBilling $propertyBilling)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePropertyBillingRequest $request, PropertyBilling $propertyBilling)
    {
        try 
        { 
            $property_billing_id = $request->receivable;
            $propertyBilling = $request->validate();
            $userID = JWTAuth::parseToken()->authenticate();
            $propertyBilling['updated_by'] = $userID->id;
            PropertyBilling::where('id', $property_billing_id->id)->update($propertyBilling);
            return Helper::successResponse(PropertyBillingResource::make($propertyBilling));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PropertyBilling $propertyBilling)
    {
        try {

            // $propertyBilling->delete();
            if (!$propertyBilling) {
                return Helper::errorResponse('Record not found', 404);
            }
    
            // Set the is_deleted field to 1
            $propertyBilling->is_deleted = 1;
            $userID = JWTAuth::parseToken()->authenticate();
            $propertyBilling->deleted_by = $userID->id;
            $propertyBilling->save();
    
            return Helper::successResponse('Successfully Deleted', 200);
            // return Helper::successResponse('Successfully deleted',404,  response()->noContent());
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
    public function updatePaymentStatus(Request $request){
        try{
            if($request->check_in_id === null){
                return Helper::errorResponse('No checkin ids found');
            }
            $record = PropertyBilling::whereIn('check_in_id', $request->check_in_id)->where('payment_status', 0)->get()->toArray();
            if(empty($record)){
                return Helper::successResponse([], 'Payment status already updated');
            }
            $pendind_record = collect($record)->pluck('check_in_id')->unique()->toArray();
            $user = JWTAuth::parseToken()->authenticate();
            PropertyBilling::whereIn('check_in_id', $pendind_record)->update(['payment_status'=> 1, 'updated_by'=> $user->id]);
            return Helper::successResponse([], count($pendind_record).' Record updated');
        }
        catch(Throwable $th){
            Helper::errorResponse($th->getMessage());
        }
    }


    //merge bills of same family
    public function mergeBill($check_in_ids, $id = 1)
    {
        try {
            $receivable_bill = CheckIn::with(['propertyBillings', 'property', 'guests'])->whereIn('id', $check_in_ids)->where('check_in_status', 'checked_out')
                ->whereHas('propertyBillings', function ($q) {
                    $q->whereColumn('check_in_id', '=', 'check_ins.id');
                })->get();
                $status = collect($receivable_bill->toArray())->where('present_status', 'checked_out')->first();
                $checkin_type = $receivable_bill[0]['check_in_type'];
                $ids_validation =  collect($receivable_bill->toArray())->pluck('registeration_number')->unique()->count();
                if($ids_validation > 1){
                    return ['error' => 'data must be belong to same family'];
                }
                $checkinDates = array_column($receivable_bill->toArray(), 'check_in_date');
                $checkoutDates = array_column($receivable_bill->toArray(), 'check_out_date');
                $start_date = min($checkinDates);
                $end_date = max($checkoutDates);
                $result = app(PayableController::class)->totalRecord($receivable_bill, 'property_billings');
                $records = collect($result)->pluck('property_billings')->toArray();
                $filter_array = [];
                
                // dd($receivable_bill);
            
            foreach ($records as $record) {
                $filter_array[] = collect($record)->groupBy('assigned_additional_service_id')->map(function ($group) use ($start_date, $end_date, $checkin_type) {
                    return $this->createArray($group, $start_date, $end_date, $checkin_type);
                })->values()->toArray();
            }

            //merge bills for rooms and services on basis of assigned_additional_service_id and its name and selling price
            $final_array = collect($filter_array)->flatten(1)->groupBy('assigned_additional_service_id')->map(function ($group) use($start_date, $end_date, $checkin_type) {
                return collect($group)->groupBy('selling_price')->map(function ($item) use($start_date, $end_date, $checkin_type){
                    return $this->createArray($item, $start_date, $end_date, $checkin_type);
                })->values()->toArray();
            })->values()->flatten(1)->groupBy('item_name')->map(function ($group) use ($start_date, $end_date, $checkin_type){
                return collect($group)->groupBy('selling_price')->map(function ($item) use($start_date, $end_date, $checkin_type){
                    return $this->createArray($item, $start_date, $end_date, $checkin_type);
                })->values()->toArray();
            })->flatten(1)->toArray();

            
            $total_bill = collect($final_array)->sum('final_amount');
            $object = [];
            $object['Bill_array'] = $final_array;
            $object['registeration_number'] = collect($receivable_bill)->pluck('registeration_number')->first();
            $object['family_name'] = collect($receivable_bill)->pluck('family_name')->first();
            $object['present_status'] = ($status == null) ? 'continue' : 'checked_out';
            $object['total_persons'] = (strtolower($checkin_type) == 'event') ? collect($receivable_bill)->sum('total_persons') : collect($receivable_bill)->max('total_persons');
            $object['check_in_date'] = $start_date;
            $object['check_in_time'] = collect($receivable_bill)->where('check_in_date',  $start_date)->pluck('check_in_time')->first();
            $object['check_out_date'] = $end_date;
            $object['check_out_time'] = collect($receivable_bill)->where('check_out_date',  $end_date)->pluck('check_out_time')->first();
            $object['property'] = collect($receivable_bill)->pluck('property')->first()->toArray();
            $object['invoice_number'] = date("Ymd", strtotime($object['check_out_date'])) . str_pad($id, 5, "0", STR_PAD_LEFT);
            $days_nights = Helper::calculateDaysNights($object['check_in_date'], $object['check_in_time'], $object['check_out_date'], $object['check_out_time'], $object['property']['id']);
            $object['days'] = $days_nights['days'];
            $object['nights'] = $days_nights['nights'];
            $object['total_merged_amount'] = $total_bill;
            return $object;
        } catch (Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    //function to create merged records array
    private function createArray($array, $start_date, $end_date, $checkin_type){
        return [
            'assigned_additional_service_id' => $array->first()['assigned_additional_service_id'],
            'item_name' => ($array->first()['assigned_additional_service_id'] == 0) ? 'Room Rent' : strtolower($array->first()['item_name']),
            'selling_price' => $array->first()['selling_price'],
            'days' => (strtolower($checkin_type) == 'event') ? $array->first()['days'] : $array->sum('days'),
            'uom' => $array->first()['uom'],
            'total_amount' => $array->sum('total_amount'),
            'billing_rules_discount' => $array->sum('billing_rules_discount'),
            'final_amount' => $array->sum('final_amount'),
            'date' => $start_date.' to '.$end_date,
            'quantity' => (strtolower($checkin_type) == 'event') ? $array->sum('quantity') : $array->first()['quantity'],
        ];
    }

    public function getReceivables($ids = null, $request = null){
        try{
            $user = JWTAuth::parseToken()->authenticate();
            $fields = [];
            $d = CheckIn::with(['propertyBillings', 'property', 'guests'])
            ->whereHas('propertyBillings', function($q) use ($request, $ids){
                $q->whereColumn('check_in_id', 'check_ins.id');
                if($ids == null)$q->where('merged', 0);
                if($request != null && $request->has('payment_status'))$q->where('property_billings.payment_status', $request->input('payment_status'));
                if($request != null && $request->has('property_id'))$q->where('property_billings.property_id', $request->input('property_id'));
            })->whereHas('property', function($q) use($user) {
                $q->where('company_id', '=', $user->company_id);
            });
            
            if($request != null && $request->has('bound_country'))$d->where('bound_country', $request->input('bound_country'));
            if($request != null && $request->has('start_date'))$d->where('check_out_date', '>=' ,$request->input('start_date'));
            if($request != null && $request->has('end_date'))$d->where('check_out_date', '<=' ,$request->input('end_date'));
            if($ids !=null && is_array($ids))$d->whereIn('id', $ids);
            $d = $d->get();
            $fields['total_receivables'] = collect($d->toArray())->pluck('property_billings')->flatten(1)->sum('total_amount');
            if($request != null && $request->has('property_id')) {
                $fields['advance_amount'] = AdvancePayment::where('property_id', $request->input('property_id'))->sum('advance_amount');
            } else{
                $fields['advance_amount'] = Property::where('company_id', $user->company_id)->join('advance_payments', 'properties.id', '=' , 'advance_payments.property_id')->sum('advance_amount');
            }
            $total_record_function = app(PayableController::class);
            $data = $total_record_function->totalRecord($d, 'property_billings');
            return ['data' => $data, 'extra_fields' => $fields];
        }
        catch(Throwable $th){
            return ['error' => $th->getMessage()];
        }
    }


    //function to generate the report for a given date range
    public function generateReport(Request $request){
        try{
            $user = JWTAuth::parseToken()->authenticate();
            if((!isset($request->start_date) || $request->start_date == null) && (!isset($request->end_date) || $request->end_date == null)){
                return Helper::errorResponse('Please select a valid date range to process this request');
            }
            $query = CheckIn::with(['payables', 'propertyBillings', 'guests.roomDetails', 'properties'])
            ->whereHas('properties', function ($q) use ($user) {
                $q->where('company_id', '=', $user->company_id);
                // $q->where('is_deleted', '=', 0);
            });
            $expanses = Expense::where('company_id', $user->company_id);
            if(!isset($request->start_date) || $request->start_date == null){
                $query->where('check_out_date', '<=', $request->end_date);
                $expanses->where('expense_date', '<=', $request->end_date);
            }
            elseif(!isset($request->end_date) || $request->end_date == null){
                $query->where('check_out_date', '>=', $request->start_date);
                $expanses->where('expense_date', '>=', $request->start_date);
            }
            else{
                $query->whereBetween('check_out_date', [$request->start_date, $request->end_date]);
                $expanses->whereBetween('expense_date', [$request->start_date, $request->end_date]);
            }
            $query->where('check_in_status', 'checked_out');
            $total_expanses = $expanses->sum('expense_amount');
            $check_ins = $query->get()->toArray();
            $payable_data = collect($check_ins)->pluck('payables')->flatten(1)->toArray();
            $property_billing_data = collect($check_ins)->pluck('property_billings')->flatten(1)->toArray();
            $total_payables = collect($payable_data)->sum('total_amount');
            $paid_payables = collect($payable_data)->where('payment_status', 1)->sum('total_amount');
            $total_billing = collect($property_billing_data)->sum('total_amount');
            $paid_billing = collect($property_billing_data)->where('payment_status', 1)->sum('total_amount');
            $total_families = collect($check_ins)->unique('registeration_number')->count();
            $total_guests = collect($check_ins)->unique('registeration_number')->pluck('guests')->flatten(1)->count();
            $report = [];
            $report['date_range'] = $request->start_date.' to '.$request->end_date;
            $report['total_payables'] = $total_payables;
            $report['paid_payables'] = $paid_payables;
            $report['total_receivables'] = $total_billing;
            $report['received_receivables'] = $paid_billing;
            $report['total_expanses'] = $total_expanses;
            $report['current_income'] = $paid_billing - $paid_payables - $total_expanses;
            $report['net_income'] = $total_billing - $total_payables - $total_expanses;
            $report['available_balance'] = $paid_billing - $total_expanses;
            $report['net_balance'] = $total_billing - $total_expanses;
            return Helper::successResponse($report);
        }
        catch(Throwable $th){
            return Helper::errorResponse($th->getMessage());
        }
    }
}
