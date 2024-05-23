<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Http\Requests\StoreCheckInRequest;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ImportCheckIn;
use App\Imports\ImportCheckOut;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Support\Facades\Validator;
use Throwable;

class fileReaderController extends Controller
{
    public function store(Request $request){
        // Validate the request
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,csv,xls|max:10240', // Adjust max file size as needed
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return Helper::errorResponse($validator->errors()->first());
        }

        try {
            // Validation passed, proceed with file import9.
            Excel::import(new ImportCheckIn, $request->file('file'));

            return Helper::successResponse([], 'Data uploaded successfully');
        } catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    //function to checkout records from excell or csv files
    public  function fileCheckouts(Request $request){
        // Validate the request
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,csv,xls|max:10240', // Adjust max file size as needed
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return Helper::errorResponse($validator->errors()->first());
        }
        try {
            // Validation passed, proceed with file import
            Excel::import(new ImportCheckOut, $request->file('file'));

            return Helper::successResponse([], 'Data checked-out successfully');
        } catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
}
