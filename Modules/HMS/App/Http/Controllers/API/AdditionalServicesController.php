<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\AdditionalServices;
use Modules\HMS\App\Http\Requests\StoreAdditionalServicesRequest;
use Modules\HMS\App\Http\Requests\UpdateAdditionalServicesRequest;
use Modules\HMS\App\Http\Resources\AdditionalServicesResource;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AdditionalServicesController extends Controller {
    /**
    * Display a listing of the resource.
    */

    public function index() {
        try {

            $userID = JWTAuth::parseToken()->authenticate();
            $d = AdditionalServices::with( 'billingRules' )->where( 'company_id', $userID->company_id )->get();
            return Helper::successResponse( AdditionalServicesResource::collection( $d ) );
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

    public function store( StoreAdditionalServicesRequest $request ) {
        DB::beginTransaction();
        try {

            //$additionalServices = $request->validated();
            $additionalServices = $request[ 'AdditionalService' ];
            $userID = JWTAuth::parseToken()->authenticate();

            $additionalServices[ 'created_by' ] = $userID->id;
            $additionalServices[ 'company_id' ] = $userID->company_id;
            // $service = AdditionalServices::create( $additionalServices );

            // Check if an entry with the same unique key already exists
            $existingService = AdditionalServices::where( 'service_name', $additionalServices[ 'service_name' ] )->first();

            if ( $existingService ) {
                // Handle the case where the service already exists
                return Helper::errorResponse( 'Service with this name already exists.' );
            } else {
                // Create a new record if it does not exist
                $service = AdditionalServices::create( $additionalServices );
            }
            // dd( $additionalServices );
            $billingRules = $request[ 'billingRule' ];
            $billing_rules_data = [];
            $i = 0;
            if ( $billingRules !== null || ( is_array( $billingRules ) && count( $billingRules ) != 0 ) ) {
                foreach ( $billingRules as $input ) {
                    $input[ 'additional_service_id' ] = $service->id;
                    $input[ 'created_by' ] = $userID->id;
                    $billing_rules_data[ $i ] = $input;
                    $i++;
                }
                // dd( $checked_in_members_data );
            }
            DB::table( 'billing_time_rules' )->insert( $billing_rules_data );
            DB::commit();
            $additional_service = AdditionalServices::with( [ 'billingRules' ] )->where( 'additional_services.id', $service->id )->get();
            return Helper::successResponse( AdditionalServicesResource::collection( $additional_service ) );

        } catch ( \Throwable $th ) {
            DB::rollback();
            return Helper::errorResponse( $th->getMessage() );
        }
    }

    /**
    * Display the specified resource.
    */

    public function show( AdditionalServices $additionalServices ) {
        try {

            return Helper::successResponse( AdditionalServicesResource::make( $additionalServices ) );
        } catch ( \Throwable $th ) {
            return Helper::errorResponse( $th->getMessage() );
        }
    }

    /**
    * Show the form for editing the specified resource.
    */

    public function edit( AdditionalServices $additionalServices ) {
        //
    }

    /**
    * Update the specified resource in storage.
    */

    public function update( UpdateAdditionalServicesRequest $request, AdditionalServices $additionalServices ) {
        try {

            $id = $request->additional_service;
            $additional_service_request = $request->validated();

            $userID = JWTAuth::parseToken()->authenticate();
            $additional_service_request[ 'updated_by' ] =  $userID->id;
            AdditionalServices::where( 'id', $id )->update( $additional_service_request );

            $additional_service = AdditionalServices::with( [ 'billingRules' ] )->where( 'additional_services.id', $id )->get();
            return Helper::successResponse( AdditionalServicesResource::collection( $additional_service ) );
        } catch ( \Throwable $th ) {
            return Helper::errorResponse( $th->getMessage() );
        }
    }

    /**
    * Remove the specified resource from storage.
    */

    public function destroy( AdditionalServices $additionalServices, $id ) {
        try {
            $data = AdditionalServices::find( $id );
            $user = JWTAuth::parseToken()->authenticate();
            $data->is_deleted = 1;
            $data->deleted_by = $user->id;
            $data->update();
            return Helper::successResponse( [], 'Successfully Deleted', 200 );
        } catch ( \Throwable $th ) {
            return Helper::errorResponse( $th->getMessage() );
        }
    }
}
