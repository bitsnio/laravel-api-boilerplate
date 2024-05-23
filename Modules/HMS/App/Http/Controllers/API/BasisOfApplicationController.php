<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\BasisOfApplication;
use Modules\HMS\App\Http\Requests\StoreBasisOfApplicationRequest;
use Modules\HMS\App\Http\Requests\UpdateBasisOfApplicationRequest;
use Modules\HMS\App\Http\Resources\BasisOfApplicationResource;
use Modules\HMS\App\Utilities\Helper;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class BasisOfApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try 
        { 
            return Helper::successResponse( BasisOfApplicationResource::collection(BasisOfApplication::all()));
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
    public function store(StoreBasisOfApplicationRequest $request)
    {
        try{
            $basisOfApplication = $request->validated();
            $userID = JWTAuth::parseToken()->authenticate();
            $basisOfApplication["created_by"] = $userID->id;
            BasisOfApplication::create($basisOfApplication);
            return Helper::successResponse(BasisOfApplicationResource::make($basisOfApplication));
        } catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(BasisOfApplication $basisOfApplication)
    {
        try{  
            return Helper::successResponse( BasisOfApplicationResource::make($basisOfApplication));
        }
            catch (\Throwable $th) {
                return Helper::errorResponse($th->getMessage());
            }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BasisOfApplication $basisOfApplication)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBasisOfApplicationRequest $request, BasisOfApplication $basisOfApplication)
    {
        try {
            $basis_of_application_id = $request->basis_of_application;
            $basisOfApplication = $request->validated();
            $userID = JWTAuth::parseToken()->authenticate();
            $basisOfApplication['updated_by'] = $userID->id;
            BasisOfApplication::where('id',$basis_of_application_id->id)->update($basisOfApplication);
            return Helper::successResponse(BasisOfApplicationResource::make($basisOfApplication));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BasisOfApplication $basisOfApplication)
    {
        try {

            // $basisOfApplication->delete();
            if (!$basisOfApplication) {
                return Helper::errorResponse('Record not found', 404);
            }
    
            // Set the is_deleted field to 1
            $basisOfApplication->is_deleted = 1;
            $userID = JWTAuth::parseToken()->authenticate();
            $basisOfApplication->deleted_by = $userID->id;
            $basisOfApplication->save();
    
            return Helper::successResponse('Successfully Deleted', 200);
            // return Helper::successResponse('Successfully deleted',404,  response()->noContent());
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
}
