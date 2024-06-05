<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\RoomType;
use Modules\HMS\App\Http\Requests\StoreRoomTypeRequest;
use Modules\HMS\App\Http\Requests\UpdateRoomTypeRequest;
use Modules\HMS\App\Http\Resources\RoomtypeResource;
use Modules\HMS\App\Models\RoomList;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Support\Arr;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

class RoomTypeController extends Controller {
    /**
    * Display a listing of the resource.
    */

    public function index() {
        try {

            $user = JWTAuth::parseToken()->authenticate();
            $company_id = $user->company_id;
            $roomType = RoomType::with( [ 'property' ] )
            ->whereHas( 'property', function( $q ) use( $company_id ) {
                $q->where( 'company_id', '=', $company_id );
            }
        )->get();
        return Helper::successResponse( RoomtypeResource::collection( $roomType ) );
    } catch ( \Throwable $th ) {
        return Helper::errorResponse( $th->getMessage() );
    }
}

/**
* Show the form for creating a new resource.
*/

public function create() {
    //
}

/**
* Store a newly created resource in storage.
*/

public function store( StoreRoomTypeRequest $request ) {
    DB::beginTransaction();
    try {

        $roomType = $request->validated();
        $userID = JWTAuth::parseToken()->authenticate();
        $roomType[ 'created_by' ] = $userID->id;
        // return $roomType;
        $room = RoomType::create( $roomType );
        $roomNum = $roomType[ 'room_number_start_from' ];
        $total = $roomType[ 'quantity' ];
        $room_prefix = str_replace( array( ',', ' ' ), '-', $roomType[ 'room_prefix' ] );
        for ( $i = $roomNum; $i<$roomNum+$total; $i++ ) {
            RoomList::create( [
                'property_id' => $room->property_id,
                'room_type_id' => $room->id,
                'room_number' => $room_prefix.'-'.$i,
                'created_by' => $userID->id,
            ] );
        }

        DB::commit();
        $roomType = RoomType::with( [ 'property' ] )->where( 'room_types.id', $room->id )->get();
        return Helper::successResponse( RoomtypeResource::collection( $roomType ) );
    } catch ( \Throwable $th ) {
        DB::rollback();
        return Helper::errorResponse( $th->getMessage() );
    }
}

/**
* Display the specified resource.
*/

public function show( RoomType $roomType ) {
    try {

        return Helper::successResponse( RoomtypeResource::make( $roomType ) );
    } catch ( \Throwable $th ) {
        return Helper::errorResponse( $th->getMessage() );
    }
}

/**
* Show the form for editing the specified resource.
*/

public function edit( RoomType $roomType ) {
    //
}

/**
* Update the specified resource in storage.
*/

public function update( UpdateRoomTypeRequest $request, $id ) {
    try {

        $roomType = $request->validated();
        $userID = JWTAuth::parseToken()->authenticate();
        $roomType[ 'updated_by' ] = $userID->id;
        RoomType::where( 'id', $id )->update( $roomType );
        $roomType = RoomType::with( [ 'property' ] )->where( 'room_types.id', $id )->get();
        return Helper::successResponse( RoomtypeResource::collection( $roomType ) );
    } catch ( \Throwable $th ) {
        return Helper::errorResponse( $th->getMessage() );
    }
}

/**
* Remove the specified resource from storage.
*/
public function destroy( RoomType $roomType, $id )
{
    try
{

        // $roomType->delete();
        // Set the is_deleted field to 1
        // $roomType->is_deleted = 1;
        $user = JWTAuth::parseToken()->authenticate();
        $roomType = RoomType::findOrFail( $id );
        // $roomType->deleted_by = $user->id;
        $occupied_rooms = RoomList::where( 'room_type_id', $id )->where( 'room_status', 'occupied' )->get()->toArray();
        // dd( $occupied_rooms );
        if ( !empty( $occupied_rooms ) ) {
            return Helper::errorResponse( 'selected  room type can not be deleted, some of rooms are assigned to checkins' );
        }
         // Soft delete the room type
        $roomType->deleted_by = $user->id;
        $roomType->save();
        $roomType->delete();
        // dd( $occupied_rooms );
        RoomList::where( 'room_type_id', $roomType->id )->update( [ 'deleted_by' => $user->id ] );
        RoomList::where( 'room_type_id', $roomType->id )->delete();
        // $roomType->save();

        return Helper::successResponse( 'Successfully Deleted', 200 );
        // return Helper::successResponse( response()->noContent() );
    }
    catch ( \Throwable $th ) {
        return Helper::errorResponse( $th->getMessage() );
    }
}


/**
* if you want to Restore then call this function.
*/
// public function destroy(RoomType $roomType, $id ) {
//     try {
//         $user = JWTAuth::parseToken()->authenticate();

//         // Find the soft-deleted RoomType
//         $roomType = RoomType::onlyTrashed()->findOrFail( $id );

//         // Restore the RoomType
//         $roomType->restore();

//         // Update the deleted_by field to null
//         $roomType->deleted_by = 0;
//         $roomType->save();

//         // Restore associated RoomLists
//         RoomList::onlyTrashed()->where( 'room_type_id', $roomType->id )->update( [ 'deleted_by' => 0 ] );
//         RoomList::onlyTrashed()->where( 'room_type_id', $roomType->id )->restore();

//         return Helper::successResponse( 'Successfully Restored', 200 );
//     } catch ( \Illuminate\Database\Eloquent\ModelNotFoundException $e ) {
//         return Helper::errorResponse( 'RoomType not found', 404 );
//     }catch ( \Throwable $th ) {
//             return Helper::errorResponse( $th->getMessage() );
//         }
//     }

}
