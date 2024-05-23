<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\Receipt;
use Modules\HMS\App\Http\Requests\StoreReceiptRequest;
use Modules\HMS\App\Http\Requests\UpdateReceiptRequest;
use Modules\HMS\App\Http\Resources\PayableResource;
use Modules\HMS\App\Http\Resources\PropertyBillingResource;
use Modules\HMS\App\Http\Resources\ReceiptResource;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Models\familyGeneratedBill;
use Modules\HMS\App\Models\Payable;
use Modules\HMS\App\Models\PropertyBilling;
use Modules\HMS\App\Utilities\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ReceiptController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try { 
            $user = JWTAuth::parseToken()->authenticate();
            $query = Receipt::with('payments', 'properties.advancePayments')->where('company_id', $user->company_id);
            if($request->has('receipt_type')){
                $query->where('receipt_type', $request->input('receipt_type'));
            }
            if ($request->has('property_id')) {
                $query->where('property_id', $request->input('property_id'));
            }
            $data = $query->get();
            $data = collect($data->toArray())->map(function ($record) {
                $record['batch_number'] = 'BN-'.str_pad($record['id'], 5, "0", STR_PAD_LEFT).$record['total_merged_amount'];
                if($record['receipt_type'] == 'payable'){
                    $record['advance_amount'] = collect($record['properties']['advance_payments'])->sum('advance_amount');
                    unset($record['properties']['advance_payments']);
                }
                else{
                    unset($record['properties']);
                    // if($record['properties'] == null){
                        //     $record['properties'] = implode(',',CheckIn::with('properties')->whereIn('id', explode(',', $record['check_in_ids']))->get()->pluck('properties')->unique('id')->pluck('title')->toArray());
                    // }
                }
                return $record;
            })->toArray();
            $data =  collect($data)->map(function ($record) {
                $record['batch_date'] = Carbon::parse($record['created_at'])->format('d-m-Y h:i A');
                $check_in_ids = explode(',', $record['check_in_ids']);
                // $response = app(PropertyBillingController::class)->getReceivables($check_in_ids);
                // if(isset($response['data']['error'])){Helper::errorResponse($response['data']['error']);}
                $response = ($record['receipt_type'] == 'payable') ? app(PayableController::class)->getPayables($check_in_ids) : app(PropertyBillingController::class)->getReceivables($check_in_ids);
                if(isset($response['data']['error'])){Helper::errorResponse($response['data']['error']);}
                $record['invoices'] = $response;
                unset($record['date']);
                return $record;
            })->toArray();
            // dd($data);
            return Helper::successResponse(ReceiptResource::collection($data));
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
    public function store(StoreReceiptRequest $request)
    {
        try{
            $receipt = $request->validated();
            $user = JWTAuth::parseToken()->authenticate();
            $checkin_ids = [];
            $payload = [];
            $common_fields = ['receipt_type' => $receipt['receipt_type'], 'company_id' => $user->company_id, 'created_by' => $user->id];
            foreach($receipt['check_in_ids'] as $ids){
                if(!is_numeric($ids)){
                    $checkin_ids[] = explode(",",$ids);
                }
                else{
                    $checkin_ids[] = $ids;
                }
            }
            $checkin_ids = collect($checkin_ids)->flatten(1)->toArray();
            $query = Receipt::select(['check_in_ids', 'company_id', 'receipt_type']);
            if(is_array($checkin_ids)){
                foreach($checkin_ids as $ids){
                    $query->orWhereRaw("FIND_IN_SET($ids,check_in_ids)");
                    $query->where('company_id', $user->company_id);
                    $query->where('receipt_type', $receipt['receipt_type']);
                }
            }
            $res = $query->get()->toArray();
            if(!empty($res)){
                return Helper::errorResponse('Receipts are already generated for some of selected data');
            }
            if(isset($receipt['receipt_type']) && $receipt['receipt_type'] == 'payable'){
                $property_payables = Payable::whereIn('check_in_id', $checkin_ids)->get()->groupBy('property_id')->toArray();
                foreach($property_payables as $payable){
                    $data = [];
                    $data["property_id"] = $payable[0]['property_id'];
                    $data["total_merged_amount"] = collect($payable)->sum('total_amount');
                    $data["check_in_ids"] = implode(',',collect($payable)->pluck('check_in_id')->unique()->toArray());
                    $data['created_at'] = now();
                    $payload[] = $data;
                }
                Payable::whereIn('check_in_id',$checkin_ids)->update(['merged' => 1]);
                $payload = Helper::objectsToArray($payload, $common_fields);
            }
            else if($receipt['receipt_type'] == 'receivable'){
                //property_id 0 indicates the checkins of different properties
                if(!isset($receipt['selected_id']) && $receipt['selected_id'] != null)return Helper::errorResponse('Merge ids not found');
                $data = [];
                $data['property_id'] = 0;
                $data['total_merged_amount'] = PropertyBilling::whereIn('check_in_id', $checkin_ids)->sum('total_amount');
                $data["check_in_ids"] = implode(',',$receipt["check_in_ids"]);
                $payload[] = $data;
                familyGeneratedBill::whereIn('id',$receipt['selected_id'])->update(['merged' => 1]);
                $payload = Helper::objectsToArray($payload, $common_fields);
            }
            else{
                return Helper::errorResponse('Select a valid receipt type');
            }
            DB::table('receipts')->insert($payload);
            // $return = Receipt::with('payments', 'properties')->where('id', $receipt->id)->get();
            return Helper::successResponse(null, count($payload).' Batch created successfully');
        } catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Receipt $receipt)
    {
        try{
            return Helper::successResponse(ReceiptResource::collection($receipt));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Receipt $receipt)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReceiptRequest $request, Receipt $receipt)
    {
        try {
            $data = $request->validated();
            $user = JWTAuth::parseToken()->authenticate();
            $data['updated_by'] = $user->id;
            $receipt->update($data);
            return Helper::successResponse(ReceiptResource::collection($receipt));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Receipt $receipt)
    {
        try{
            $receipt->is_deleted = 1;
            $user = JWTAuth::parseToken()->authenticate();
            $receipt->deleted_by = $user->id;
            $receipt->save();
            return Helper::successResponse('Successfully Deleted', 200);
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    public function getInvoiceReceipts(Request $request){
        try{
            if(!isset($request['receipt_id']) && $request['receipt_id'] == null){
                return Helper::errorResponse('Receipt id not found');
            }
            $record = Receipt::find($request['receipt_id']);
            $ids = explode(',', $record['check_in_ids']);
            if($record['receipt_type'] == 'payable'){
                $response = app(PayableController::class)->getPayables($ids);
                if(isset($response['data']['error'])){Helper::errorResponse($response['data']['error']);}
                return (isset($response['error'])) ? Helper::errorResponse($response['error']) : Helper::successResponse(PayableResource::collection($response['data']));
            }
            else{
                $response = app(PropertyBillingController::class)->getReceivables($ids);
                if(isset($response['data']['error'])){Helper::errorResponse($response['data']['error']);}
                return (isset($response['error'])) ? Helper::errorResponse($response['error']) : Helper::successResponse(PropertyBillingResource::collection($response['data']));
            }
            
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
}
