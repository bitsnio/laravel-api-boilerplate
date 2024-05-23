<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\GeneratedBills;
use Modules\HMS\App\Http\Requests\StoreGeneratedBillsRequest;
use Modules\HMS\App\Http\Requests\UpdateGeneratedBillsRequest;
use Modules\HMS\App\Http\Resources\GeneratedBillsResource;
use Modules\HMS\App\Http\Resources\ReCheckInForMissingBillsResource;
use Modules\HMS\App\Http\Resources\ReCheckInResourceForMissingBills;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Models\Payable;
use Modules\HMS\App\Utilities\Helper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class GeneratedBillsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            return Helper::successResponse(GeneratedBillsResource::collection(GeneratedBills::all()));
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
    public function store(StoreGeneratedBillsRequest $request)
    {
        try
        {
            $generatedBills = $request->validated();
            $response = $this->generateMissingBills($generatedBills);
            return $response;
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(GeneratedBills $generatedBills)
    {
        try{
            return Helper::successResponse(GeneratedBillsResource::make($generatedBills));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GeneratedBills $generatedBills)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGeneratedBillsRequest $request, GeneratedBills $generatedBills)
    {
        try{
            $generated_bills_id = $request->generated_bills;
            $generatedBills = $request->validate();
            $userID = JWTAuth::parseToken()->authenticate();
            $generatedBills['updated_by'] = $userID->id;
            GeneratedBills::where('id', $generated_bills_id->id)->update($generatedBills);
            return Helper::successResponse(GeneratedBillsResource::make($generatedBills));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GeneratedBills $generatedBills)
    {
        try{
            if (!$generatedBills) {
                return Helper::errorResponse('Record not found', 404);
            }
            $generatedBills->is_deleted = 1;
            $userId = JWTAuth::parseToken()->authenticate();
            $generatedBills->deleted_by = $userId->id;
            $generatedBills->save();
            return Helper::successResponse('Successfully Deleted', 200);
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
    public function generateMissingBills($generatedBills){
        DB::beginTransaction();
        try{
            // $check_in_data =CheckIn::with('guests')
            $user= JWTAuth::parseToken()->authenticate();
            $company_id = $user->company_id;
            $check_in_data = CheckIn::with(['guests','property'])
            ->whereHas('property', function($q) use($company_id, $generatedBills) {
                $q->where('company_id', '=', $company_id);
            })
            ->whereBetween('check_in_date',[$generatedBills['start_date'], $generatedBills['end_date']])
            ->where('check_in_status','active');
            if(isset($generatedBills['property_id']) && $generatedBills['property_id'] != null){
                $check_in_data->where('property_id', $generatedBills['property_id']);
            }
            if(isset($generatedBills['checkin_ids']) && (is_array($generatedBills['checkin_ids'])) && (!empty($generatedBills['checkin_ids']))){
                $check_in_data->whereIn('id', $generatedBills['checkin_ids']);
            }
            $check_in_data = $check_in_data->get()->toArray();
            if(empty($check_in_data)){
                return Helper::errorResponse('No active checkins found in given range '.$generatedBills['start_date'].' to '.$generatedBills['end_date']);
            }
            $newCheckInData = $this->unsetReCheckInData($check_in_data);
            $billing_record_ids = collect($check_in_data)->pluck('id');
            $check_out_data = [];
            if(empty($check_in_data)){
                return Helper::errorResponse("No Active Checkin Exists in given range",400);
            }
            $carbonDate = Carbon::parse($generatedBills['end_date']);
            // Add one day to the date as new checkin date will be then next day of generated bill date
            $increasedDate = $carbonDate->addDay();
            // Format the result in the desired format
            $new_checkin_date = $increasedDate->format('Y-m-d');
            foreach ($check_in_data as  $record) {
                $check_out_data['check_in_data'][] = [
                    'check_in_id' => $record['id'],
                    'guests' => collect($record['guests'])->pluck('id')->toArray(),
                ];
            }
            $check_out_data['check_out_type'] = 'billing';
            $check_out_data['check_out_date'] = $generatedBills['end_date'];
            $check_out_data['check_out_time'] = '11:59 PM';
            // return $billing_record_ids;
            // dd($check_out_data);
            $response = app(CheckOutController::class)->checkOut($check_out_data);
            if(isset($response['error'])){
                return Helper::errorResponse($response);
            }
            foreach($newCheckInData as $checkIn){
                $checkIn[0]['check_in_date'] = $new_checkin_date;
                $checkIn[0]['check_in_time'] = '12:00 AM';//as new checkin time will be the next day standard start time
                $previous_checkin_id = $checkIn[0]['guestDetails'][0]['check_in_id'];
                $responseCheckIn = app(CheckInController::class)->checkIn($checkIn[0],'re_checkin',$previous_checkin_id);
                if(isset($responseCheckIn['error'])){
                    return Helper::errorResponse($responseCheckIn['error']);
                }
            }
            $payable = Payable::whereIn('check_in_id', $billing_record_ids)
            ->groupBy('check_in_id')
            ->selectRaw('check_in_id, SUM(total_amount) as total_amount')
            ->get()
            ->toArray();
            $payable['total_families'] = count($billing_record_ids);
            $payable['total_bill'] = array_sum(array_column($payable, 'total_amount'));
            DB::commit();
            return Helper::successResponse($payable, 200);
        }
        catch(Throwable $th){
            DB::rollBack();
            return Helper::errorResponse($th->getMessage());
        }
    }

    public function unsetReCheckInData($data){
        $i = 0;
        foreach($data as $record){
            unset($data[$i]['property']);
            unset($data[$i]['last_check_in_id']);
            $unsetRecord[0] = $data[$i];
            $data[$i] = Helper::unsetFields($unsetRecord);
            $data[$i][0]['guestDetails'] = Helper::unsetFields($data[$i][0]['guests']);
            unset($data[$i][0]['guests']);
            $i++;
        }
        return $data;
    }
     public function showBills($showBills){
        
     }
}
