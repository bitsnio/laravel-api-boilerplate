<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\Contract;
use Modules\HMS\App\Http\Requests\StoreContractRequest;
use Modules\HMS\App\Http\Requests\UpdateContractRequest;
use Modules\HMS\App\Http\Resources\BaseResource;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Database\Eloquent\Collection;
use Throwable;
use DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ContractController extends Controller {
    /**
    * Display a listing of the resource.
    */

    public function index() {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $data = Contract::where( 'company_id', $user->company_id )->get();
            return Helper::successResponse( BaseResource::collection( $data ) );
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

    public function store( StoreContractRequest $request ) {
        try {
            $data = $request->validated();
            $user = JWTAuth::parseToken()->authenticate();
            if ( !isset( $data[ 'property_id' ] ) || $data[ 'property_id' ] == null ) {
                $data[ 'property_id' ] = 0;
            }
            $data[ 'company_id' ] = $request->company_id;
            $data[ 'created_by' ] = $user->id;
            // dd( DB::table( 'companies' )->where( 'id', $data[ 'company_id' ] )->exists() );
            // Debugging: Check if company_id exists in companies table
            $companyExists = DB::table( 'companies' )->where( 'id', $data[ 'company_id' ] )->exists();

            if ( !$companyExists ) {
                return Helper::errorResponse( 'The company ID does not exist.' );
            }
            Contract::create( $data );
            return Helper::successResponse( BaseResource::make( $data ) );
        } catch( Throwable $th ) {
            return Helper::errorResponse( $th->getMessage() );
        }
    }

    /**
    * Display the specified resource.
    */

    public function show( Contract $contract ) {
        //
    }

    /**
    * Show the form for editing the specified resource.
    */

    public function edit( Contract $contract ) {
        //
    }

    /**
    * Update the specified resource in storage.
    */

    public function update( UpdateContractRequest $request, Contract $contract ) {
        //
    }

    /**
    * Remove the specified resource from storage.
    */

    public function destroy( Contract $contract ) {
        //
    }
}
