<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\RoomList;
use Modules\HMS\App\Http\Requests\StoreRoomListRequest;
use Modules\HMS\App\Http\Requests\UpdateRoomListRequest;
use Modules\HMS\App\Http\Resources\RoomListResource;
use Modules\HMS\App\Models\CheckedInMembers;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\ToArray;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class RoomListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try { 
            $user= JWTAuth::parseToken()->authenticate();
            $company_id = $user->company_id;
            $query = RoomList::with(['property','roomtypes'])
            ->whereHas('property', function($q) use($company_id) {
                $q->where('company_id', '=', $company_id);
            })
            ->where(function ($q) use ($request) {
                if ($request->has('room_status')) {
                    $q->where('room_status', $request->input('room_status'));
                }
            });
            $roomList = $query->orderByRaw('property_id , CAST(substring_index(room_number, "-",-1) as unsigned) ASC')->get();
            return Helper::successResponse(RoomListResource::collection($roomList));
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
    public function store(StoreRoomListRequest $request)
    {
        try 
        { 
            $roomList = $request->validated();
            $userId = JWTAuth::parseToken()->authenticate();
            $roomList['created_by'] = $userId->id;
            // $roomList->save();
            RoomList::create($roomList);
            return Helper::successResponse(RoomListResource::make($roomList));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(RoomList $request, $id)
    {
        try
        {
            $roomList = RoomList::where('property_id', $id)->orderBy('id', 'asc')->get();
            return Helper::successResponse(RoomListResource::collection($roomList));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RoomList $roomList)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoomListRequest $request, RoomList $roomList)
    {
        try 
        { 
            $room_list_id = $request->room_list;
            $roomList = $request->validate();
            $userID = JWTAuth::parseToken()->authenticate();
            $roomList['updated_by'] = $userID->id;
            RoomList::where('id', $room_list_id->id)->update($roomList);
            return Helper::successResponse(RoomListResource::make($roomList));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoomList $roomList)
    {
        try 
        { 

            // Set the is_deleted field to 1
            $roomList->is_deleted = 1;
            $userId = JWTAuth::parseToken()->authenticate();
            $roomList->deleted_by = $userId->id;
            $roomList->save();
    
            return Helper::successResponse('Successfully Deleted');
            // return Helper::successResponse(response()->noContent());
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    public function familyRooms(Request $request){
        try {
            if(!isset($request['check_in_id'])){
                return Helper::errorResponse('request is incomploete, checkin id not found');
            }
            $family_record = CheckedInMembers::where('check_in_id', $request['check_in_id'])->get()->toArray();
            $family_rooms = collect($family_record)->pluck('room_number')->unique()->toArray();
            $property_id = collect($family_record)->pluck('property_id')->first();
            $family_rooms = RoomList::whereIn('id', $family_rooms)->get(['id','room_number as title'])->toArray();
            $available_rooms = RoomList::where('property_id', $property_id)->where('room_status', 'available')->get(['id','room_number as title'])->toArray();
            $combile_rooms = array_merge($family_rooms, $available_rooms);
            return Helper::successResponse($combile_rooms);
            dd($family_rooms);
        } catch (Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
}
