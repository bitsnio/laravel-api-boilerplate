<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\CheckedInMembers;
use Modules\HMS\App\Http\Requests\StoreCheckedInMemberRequest;
use Modules\HMS\App\Http\Requests\UpdateCheckedInMemberRequest;
use Modules\HMS\App\Http\Resources\CheckedInMemberResource;
use Modules\HMS\App\Http\Resources\CheckInResource;
use Modules\HMS\App\Models\CheckIn;
use Modules\HMS\App\Models\RoomList;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class CheckedInMemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try 
        { 
            // $checkedInMember = CheckedInMember::where('is_deleted', 0)->get();
            return Helper::successResponse(CheckedInMemberResource::collection(CheckedInMembers::all()));
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
    public function store(StoreCheckedInMemberRequest $request)
    {
        DB::beginTransaction();
        try 
        { 
            $checkedInMember = $request->validated();
            $user = JWTAuth::parseToken()->authenticate();
            $checkIn = CheckIn::find($checkedInMember['check_in_id']);
            if($checkIn['check_in_status'] === 'checked_out'){
                return Helper::errorResponse('checked out data cannot be edit');
            }
            $rooms = collect($checkedInMember['guests'])->pluck('room_number')->unique()->toArray();
            if(strtolower($checkIn['check_in_type']) == 'event'){
                $rooms = explode(',', $checkedInMember['guests'][0]['room_number']);
            }
            $family_rooms = CheckedInMembers::where('check_in_id', $checkedInMember['check_in_id'])->get()->pluck('room_number')->unique()->toArray();
            $requested_rooms = RoomList::whereIn('id', $rooms)->get()->toArray();
            $available_rooms = collect($requested_rooms)->where('room_status', 'available')->pluck('id')->toArray();
            $new_rooms = [];
            $occupied_rooms = [];
            if(strtolower($checkIn['check_in_type']) != 'event'){
                foreach($rooms as $room){
                    if(in_array($room, $family_rooms)){
                        continue;
                    }
                    if(in_array($room, $available_rooms)){
                        $new_rooms [] = $room;
                    }
                    if(!in_array($room, $family_rooms) && (!in_array($room, $available_rooms))){
                        $occupied_rooms[] = collect($requested_rooms)->where('id', $room)->pluck('room_number')->first();
                    }
                }
                if(!empty($occupied_rooms)){
                    $occupied = implode(', ', $occupied_rooms);
                    return Helper::errorResponse('selected rooms '.$occupied.' are not available');
                }
            }
            else{
                $guest_data = [];
                $e_count = $checkIn['total_persons'] + 1;
                foreach($rooms as $room){
                    $event_data = [];
                    $event_data = $checkedInMember['guests'][0];
                    $event_data['room_number'] = $room;
                    $event_data['cnic_passport_number'] = $event_data['cnic_passport_number'].'-EVENT'.$e_count;
                    $guest_data[] =  $event_data;
                    $e_count++;
                    if(in_array($room, $available_rooms)){
                        $new_rooms [] = $room;
                    }
                    if(!in_array($room, $available_rooms)){
                        $occupied_rooms[] = collect($requested_rooms)->where('id', $room)->pluck('room_number')->first();
                    }
                }
                if(!empty($occupied_rooms)){
                    $occupied = implode(', ', $occupied_rooms);
                    return Helper::errorResponse('selected Room/Hall '.$occupied.' are not available');
                }
                $checkedInMember['guests'] = $guest_data;
            }
            if(!empty($new_rooms)){
                RoomList::whereIn('id', $new_rooms)->update(['room_status' => 'occupied', 'updated_by' => $user->id, 'check_in_date' => $checkIn['check_in_date'], 'check_out_date' => $checkIn['expected_check_out_date']]);
            }
            $payload = Helper::objectsToArray($checkedInMember['guests'], ['property_id' => $checkIn['property_id'], 'check_in_id' => $checkedInMember['check_in_id'], 'created_by' => $user->id]);
            $total_persons = $checkIn['total_persons'] + count($payload);
            if(strtolower($checkIn['check_in_type']) == 'single'){
                CheckIn::where('id', $checkedInMember['check_in_id'])->update(['total_persons' => $total_persons, 'updated_by' => $user->id, 'check_in_type' => 'group']);
            }
            else{
                CheckIn::where('id', $checkedInMember['check_in_id'])->update(['total_persons' => $total_persons, 'updated_by' => $user->id,]);
            }
            DB::table('checked_in_members')->insert($payload);
            $return_data = CheckIn::with(['guests.rooms', 'properties'])->where('id', $checkedInMember['check_in_id'])
            ->whereHas('properties', function ($q) use ($user) {
                $q->where('company_id', '=', $user->company_id);
                $q->where('is_deleted', '=', 0);
            })->get();
            DB::commit();
            return Helper::successResponse(CheckInResource::collection($return_data), 'Guest added successfuly');
            return Helper::successResponse(null, 'Guest added successfuly');
        }
        catch (\Throwable $th) {
            DB::rollBack();
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CheckedInMembers $checkedInMember)
    {
        try 
        { 
            if ($checkedInMember->is_deleted == 1) {
                return Helper::errorResponse('Record not found', 404);
            }
            return Helper::successResponse(CheckedInMemberResource::make($checkedInMember));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CheckedInMembers $checkedInMember)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCheckedInMemberRequest $request, CheckedInMembers $checkedInMember)
    {
        try 
        {
            $data = $request->validated();
            $checkIn = CheckIn::find($checkedInMember['check_in_id']);
            if($checkIn['check_in_status'] == 'checked_out'){
                return Helper::errorResponse('checked out data cannot be updated');
            }
            $user = JWTAuth::parseToken()->authenticate();
            $data['updated_by'] = $user->id;
            CheckedInMembers::where('id', $checkedInMember->id)->update($data);
            return Helper::successResponse(CheckedInMemberResource::make($checkedInMember), 'successfully updated');
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CheckedInMembers $checkedInMember)
    {
        try 
        { 
            // $checkedInMember->delete();
            if (!$checkedInMember) {
                return Helper::errorResponse('Record not found', 404);
            }
    
            // Set the is_deleted field to 1
            $checkedInMember->is_deleted = 1;
            $userID = JWTAuth::parseToken()->authenticate();
            $checkedInMember->deleted_by = $userID->id;
            $checkedInMember->save();
    
            return Helper::successResponse('Successfully Deleted', 200);
            // return Helper::successResponse(response()->noContent());
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
    
}
