<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\Payable;
use Modules\HMS\App\Http\Requests\StorePayableRequest;
use Modules\HMS\App\Http\Requests\UpdatePayableRequest;
use Modules\HMS\App\Http\Resources\BaseResource;
use Modules\HMS\App\Http\Resources\PayableResource;
use Modules\HMS\App\Models\AdditionalServices;
use Modules\HMS\App\Models\AdvancePayment;
use Modules\HMS\App\Models\CheckedInMembers;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Models\CheckOut;
use Modules\HMS\App\Models\Property;
use Modules\HMS\App\Models\PropertySetting;
use Illuminate\Support\Str;
use Modules\HMS\App\Models\RoomType;
use Modules\HMS\App\Utilities\Helper;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class PayableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // $user = JWTAuth::parseToken()->authenticate();
            // $fields = [];
            // $data = CheckIn::with(['payables', 'property', 'guests.rooms'])
            //     ->whereHas('payables', function ($q) use ($request) {
            //         $q->whereColumn('check_in_id', '=', 'check_ins.id');
            //         if ($request->has('payment_status')) {
            //             $q->where('payables.payment_status', $request->input('payment_status'));
            //         }
            //         if ($request->has('property_id')) {
            //             $q->where('payables.property_id', $request->input('property_id'));

            //         }
            //     })
            //     ->whereHas('property', function ($q) use ($user) {
            //         $q->where('company_id', '=', $user->company_id);
            //     })
            //     ->whereNotExists(function ($query) {
            //         $query->select(DB::raw(1))
            //             ->from('receipts')
            //             ->whereRaw('FIND_IN_SET(check_ins.id, receipts.check_in_ids) > 0');
            //     });
            // if($request->has('bound_country')){
            //     $data->where('bound_country', $request->input('bound_country'));
            // }
            // $data = $data->get();
            // $fields['total_payables'] = collect($data->toArray())->pluck('payables')->flatten(1)->sum('total_amount');
            // if ($request->has('property_id')) {
            //     $fields['advance_amount'] = AdvancePayment::where('property_id', $request->input('property_id'))->sum('advance_amount');
            // } else{
            //     $fields['advance_amount'] = Property::where('company_id', $user->company_id)->join('advance_payments', 'properties.id', '=' , 'advance_payments.property_id')->sum('advance_amount');
            // }
            // $result = $this->totalRecord($data, 'payables');
            $response = $this->getPayables(null, $request);
            if(isset($response['data']['error'])){Helper::errorResponse($response['data']['error']);}
            return (isset($response['error']))?Helper::errorResponse($response['error']):Helper::successResponse(['extra_fields' => $response['extra_fields'], 'data' => PayableResource::collection($response['data'])]);
        } catch (\Throwable $th) {
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
    public function store(StorePayableRequest $request)
    {
        try {

            $payable = $request->validated();
            $check_in_id = $payable['check_in_id'];
            $userId = JWTAuth::parseToken()->authenticate();
            $payable['created_by'] = $userId->id;
            Payable::create($payable);
            $d = CheckIn::with(['payables', 'property', 'guests'])
                ->whereHas('payables', function ($q) use ($check_in_id) {
                    $q->where('check_in_id',  $check_in_id);
                })
                ->get();
            $result = $this->totalRecord($d, 'payables');
            return Helper::successResponse(BaseResource::make($result));
        } catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Payable $payable)
    {
        try {
            return Helper::successResponse(PayableResource::make($payable));
        } catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payable $payable)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePayableRequest $request, Payable $payable)
    {
        try {
            $payable_id = $request->payable;
            $payable = $request->validate();
            $userID = JWTAuth::parseToken()->authenticate();
            $payable['updated_by'] = $userID->id;
            Payable::where('id', $payable_id->id)->update($payable);
            return Helper::successResponse(PayableResource::make($payable));
        } catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payable $payable)
    {
        try {

            // $payable->delete();
            if (!$payable) {
                return Helper::errorResponse('Record not found', 404);
            }

            // Set the is_deleted field to 1
            $payable->is_deleted = 1;
            $userID = JWTAuth::parseToken()->authenticate();
            $payable->deleted_by = $userID->id;
            $payable->save();

            return Helper::successResponse('Successfully Deleted', 200);
            // return Helper::successResponse('Successfully deleted',404,  response()->noContent());
        } catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
    private function calculateCost($request)
    {
        $room_id = [];
        $total_cost = 0;
        $check_in_id = $request['check_in_id'];
        $startDate = $request['start_date'];
        $endDate = $request['end_date'];
        if ($check_in_id !== null) {
            $service_cost = DB::table('assigned_additional_services')
                ->where('check_in_id', $check_in_id)
                ->sum('cost');
        } else {
            $service_cost = DB::table('services')
                ->where('service_id', 6)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('cost');
        }
    }
    public function totalRecord($dataArray, $type)
    {
        try {
            $array = $dataArray->toArray();
            $final_result = [];
            $j = 0;
            foreach ($array as $data) {
                $result = [];
                $i = 0;
                $services_record = ($type === 'payables') ? $data['payables'] : $data['property_billings'];
                $groupedData = collect($services_record)->groupBy('assigned_additional_service_id');
                $settings = $this->checkSettings($data['property_id']);
                if (isset($settings['error'])) {
                    return ['error' => $settings['error']];
                }
                // dd($settings);
                foreach ($groupedData as $group) {
                    $service_name = 'Additional Service';
                    $service_total_amount = 0;
                    $new_data = [];
                    $discount_amount = 0;
                    $days_minus = 0; //number of services skipped on checkin and checkout day
                    foreach ($group as $item) {                        
                        $checkInDate = Carbon::createFromFormat('Y-m-d', $data['check_in_date']);
                        $checkInDate = $checkInDate->format('d-m-Y');
                        $checkOutDate = Carbon::createFromFormat('Y-m-d', $data['check_out_date']);
                        $checkOutDate = $checkOutDate->format('d-m-Y');
                        // $checkOutDate = date('d-m-y', strtotime($data['check_out_date']));
                        if ($item['assigned_additional_service_id'] === 0) {
                            $find = Str::contains(strtolower($item['item_name']), 'room rent');
                            if ($type === 'payables' && $settings['payables'] == 0 && $find == true ) {
                                $item['item_name'] = 'Room Rent';
                            } else if ($type === 'property_billings' && $settings['property_billings'] == 0 && $find == true ) {
                                $item['item_name'] = 'Room Rent';
                            }
                            $item['final_amount'] = $item['total_amount'];
                            $item['billing_rules_discount'] = 0;
                            $item['date'] = $checkInDate. " to " . $checkOutDate;
                            $result[$i] = $item;
                            $i++;
                        } else {
                            
                            if($item['total_amount'] >= 0 && $item['days'] >= 0){
                                $service_name = $item['item_name'];
                                $service_total_amount = $item['total_amount'];
                            }
                            if ($item['total_amount'] < 0) {
                                $column_name = ($type === 'payables') ? 'cost' : 'selling_price';
                                $discount_amount += $item['total_amount'];
                                if ($item['days'] < 0) {
                                    $days_minus += $item['days'];
                                }
                            } else {
                                $item['item_name'] = $service_name;
                                $new_data = $item;
                            }
                        }
                    }
                    if (empty($new_data) === false) {
                        $new_data['final_amount'] = $service_total_amount + $discount_amount;
                        $new_data['billing_rules_discount'] = $discount_amount;
                        $new_data['days'] += $days_minus;
                        $new_data['date'] = $checkInDate. " to " . $checkOutDate;
                        $result[$i] = $new_data;
                        // dd($new_data);
                        $i++;
                    }
                }
                $number_of_rooms = collect($data['guests'])->unique('room_number')->count();
                unset($data['guests']);
                $data['number_of_rooms'] = $number_of_rooms;
                $data['invoice_number'] = date("Ymd", strtotime($data['check_out_date'])) . str_pad($data['id'], 5, "0", STR_PAD_LEFT);
                $days_nights = Helper::calculateDaysNights($data['check_in_date'], $data['check_in_time'], $data['check_out_date'], $data['check_out_time'], $data['property']['id']);
                if(isset($days_nights['error'])){
                    return ['error' => $days_nights['error']];
                }
                $data['check_in_date'] = $checkInDate;
                $data['check_out_date'] = $checkOutDate;
                $data['days'] = $days_nights['days'];
                $data['nights'] = $days_nights['nights'];
                if (isset($data['payables'])) {
                    $data['payables'] = $result;
                } else {
                    $data['property_billings'] = $result;;
                }
                $final_result[$j] = $data;
                $j++;
            }
            return $final_result;
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }


    //function to update payment status in payable against given checkins
    public function updatePaymentStatus(Request $request)
    {
        try {
            if ($request->check_in_id === null) {
                return Helper::errorResponse('No checkin ids found');
            }
            $record = Payable::whereIn('check_in_id', $request->check_in_id)->where('payment_status', 0)->get()->toArray();
            if (empty($record)) {
                return Helper::successResponse([], 'Payment status already updated');
            }
            $pendind_record = collect($record)->pluck('check_in_id')->unique()->toArray();
            $user = JWTAuth::parseToken()->authenticate();
            Payable::whereIn('check_in_id', $pendind_record)->update(['payment_status' => 1, 'updated_by' => $user->id]);
            return Helper::successResponse([], count($pendind_record) . ' Record updated');
        } catch (Throwable $th) {
            Helper::errorResponse($th->getMessage());
        }
    }


    //check property settings if to add room name with invoice
    public function checkSettings($property_id)
    {
        try {
            $payable_status = 0;
            $receivable_status = 0;
            $settings = PropertySetting::whereIn('key', ['show_room_name_with_receivable_invoice', 'show_room_name_with_payable_invoice'])->where('property_id', $property_id)->get()->toArray();
            if (empty($settings)) {
                return ['payables' => $payable_status, 'property_billings' => $receivable_status];
            } else {
                $payable_status = collect($settings)->where('key', 'show_room_name_with_payable_invoice')->pluck('value')->first();
                $receivable_status = collect($settings)->where('key', 'show_room_name_with_receivable_invoice')->pluck('value')->first();
            }
            return ['payables' => $payable_status, 'property_billings' => $receivable_status];
            // dd($payable_status);
        } catch (Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }

    public function getPayables($ids = null, $request = null){
        try{
            $user = JWTAuth::parseToken()->authenticate();
            $fields = [];
            $data = CheckIn::with(['payables', 'property', 'guests.rooms'])
                ->whereHas('payables', function ($q) use ($request, $ids) {
                    $q->whereColumn('check_in_id', 'check_ins.id');
                    if($ids == null)$q->where('merged', 0);
                    if($request != null && $request->has('payment_status'))$q->where('payables.payment_status', $request->input('payment_status'));
                    if($request != null && $request->has('property_id'))$q->where('payables.property_id', $request->input('property_id'));
                })->whereHas('property', function ($q) use ($user) {
                    $q->where('company_id', '=', $user->company_id);
                });
            if($request != null && $request->has('bound_country'))$data->where('bound_country', $request->input('bound_country'));
            if($request != null && $request->has('start_date'))$data->where('check_out_date', '>=' ,$request->input('start_date'));
            if($request != null && $request->has('end_date'))$data->where('check_out_date', '<=' ,$request->input('end_date'));
            if($ids != null && is_array($ids))$data->whereIn('id', $ids);
            $data = $data->get();
            $fields['total_payables'] = collect($data->toArray())->pluck('payables')->flatten(1)->sum('total_amount');
            if ($request != null && $request->has('property_id')) {
                $fields['advance_amount'] = AdvancePayment::where('property_id', $request->input('property_id'))->sum('advance_amount');
            } else{
                $fields['advance_amount'] = Property::where('company_id', $user->company_id)->join('advance_payments', 'properties.id', '=' , 'advance_payments.property_id')->sum('advance_amount');
            }
            $result = $this->totalRecord($data, 'payables');
            return ['data' => $result, 'extra_fields' => $fields];
        }
        catch (Throwable $th){
            return ['error' => $th->getMessage()];
        }
    }
}
