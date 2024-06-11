<?php

namespace Modules\HMS\App\Http\Controllers\API;
use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\BillingTimeRule;
use Modules\HMS\App\Http\Requests\StoreBillingTimeRuleRequest;
use Modules\HMS\App\Http\Requests\UpdateBillingTimeRuleRequest;
use Modules\HMS\App\Http\Resources\BaseResource;
use Modules\HMS\App\Http\Resources\BillingtimeRuleResource;
use Modules\HMS\App\Utilities\Helper;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class BillingTimeRuleController extends Controller {
    /**
    * Display a listing of the resource.
    */

    public function index() {
        try {

            return Helper::successResponse( BillingtimeRuleResource::collection( BillingTimeRule::all() ) );
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

    public function store( StoreBillingTimeRuleRequest $request ) {
        try {

            $userId = JWTAuth::parseToken()->authenticate();
            $data = $request->validated();
            $billingTimeRule = $data[ 'BillingRuleForm' ];
            $billingTimeRule[ 'additional_service_id' ] = $data[ 'additional_service_id' ];
            $billingTimeRule[ 'created_by' ] = $userId->id;
            BillingTimeRule::create( $billingTimeRule );
            unset( $billingTimeRule[ 'additional_service_id' ] );
            return Helper::successResponse( BaseResource::make( $billingTimeRule ) );
        } catch ( \Throwable $th ) {
            return Helper::errorResponse( $th->getMessage() );
        }
    }

    /**
    * Display the specified resource.
    */

    public function show( BillingTimeRule $billingTimeRule ) {
        try {

            return Helper::successResponse( BillingTimeRuleResource::make( $billingTimeRule ) );
        } catch ( \Throwable $th ) {
            return Helper::errorResponse( $th->getMessage() );
        }
    }

    /**
    * Show the form for editing the specified resource.
    */

    public function edit( BillingTimeRule $billingTimeRule ) {
        //
    }

    /**
    * Update the specified resource in storage.
    */

    public function update( UpdateBillingTimeRuleRequest $request, BillingTimeRule $billingTimeRule, $id ) {
        try {

            // dd( $request->toArray(), $billingTimeRule->toArray(), $id );
            $billing_time_rule_id = $request->billing_time_rule;
            $billingTimeRule = $request->validated();
            $userID = JWTAuth::parseToken()->authenticate();
            $billingTimeRule[ 'updated_by' ] = $userID->id;
            BillingTimeRule::where( 'id', $id )->update( $billingTimeRule );
            unset( $billingTimeRule[ 'additional_service_id' ] );
            return Helper::successResponse( BaseResource::make( $billingTimeRule ) );
        } catch ( \Throwable $th ) {
            return Helper::errorResponse( $th->getMessage() );
        }
    }

    /**
    * Remove the specified resource from storage.
    */

    public function destroy( BillingTimeRule $billingTimeRule, $id ) {
        try {

            $user = JWTAuth::parseToken()->authenticate();
            $billingTimeRule = BillingTimeRule::findOrFail( $id );
            // $billingTimeRule->is_deleted = 1;
            $billingTimeRule->deleted_by = $user->id;
            $billingTimeRule->save();
            $billingTimeRule->delete();
            // $billingTimeRule->update();
            return Helper::successResponse( [], 'Successfully Deleted', 200 );
        } catch ( \Throwable $th ) {
            return Helper::errorResponse( $th->getMessage() );
        }
    }
}
