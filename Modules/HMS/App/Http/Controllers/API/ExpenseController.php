<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\Expense;
use Modules\HMS\App\Http\Requests\StoreExpenseRequest;
use Modules\HMS\App\Http\Requests\UpdateExpenseRequest;
use Illuminate\Support\Facades\File;
use Modules\HMS\App\Http\Resources\BaseResource;
use Modules\HMS\App\Models\Property;
use Modules\HMS\App\Utilities\Helper;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Throwable;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try{
            $user = JWTAuth::parseToken()->authenticate();
            $query = Expense::where('company_id', $user->company_id);
            if ($request->has('property_id')) {
                $query->where('property_id', $request->input('property_id'));
            }
            $expenses = $query->get();
            return Helper::successResponse(BaseResource::collection($expenses));
       }
       catch(Throwable $th){
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
    public function store(StoreExpenseRequest $request)
    {
        try{
            $user = JWTAuth::parseToken()->authenticate();
            $payload = $request->validated();
            $payload['company_id'] = $user->company_id;
            $payload['created_by'] = $user->id;
            if(!isset($payload['property_id']) || $payload['property_id'] == null){
                $payload['property_id'] = 0;
            }
            $response = Expense::create($payload);
            return Helper::successResponse(BaseResource::make($response->toArray()));
        }
        catch(Throwable $th){
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        //
    }

    public function readJson(){
        try{
            $filePath = base_path('file.json');
            $jsonData = file_get_contents($filePath);
            $data = json_decode($jsonData, true);
            $properties = [];
            // dd($data);
            // foreach($data['properties'] as $key => $value){
            //     if($value['type'] == 'object'){
            //         $obj = [];
            //         foreach($value['properties'] as $k => $v){
            //             if(isset($v['enum'])){
            //                 $properties[0][$k] = [
            //                     'type' => $v['type'],
            //                     'enums' => implode(',', $v['enum'])
            //                 ];
            //             }
            //             else $properties[0][$k] = $v['type'];
            //         }
            //     }
            //     elseif($value['type'] == 'array'){
            //         if($value['items']['type'] == 'object'){
            //             $obj = [];
            //             foreach($value['items']['properties'] as $k => $v){
            //                 if(isset($v['enum'])){
            //                     $obj[$k] = [
            //                         'type' => $v['type'],
            //                         'enums' => implode(',', $v['enum'])
            //                     ];
            //                 }
            //                 else $obj[$k] = $v['type'];
            //             }
            //             $properties[][$key] = $obj;
            //         }
            //     }
            //     else{
            //         if(isset($value['enum'])){
            //             $properties[$key] = [
            //                 'type' => $value['type'],
            //                 'enums' => implode(',', $value['enum'])
            //             ];
            //         }
            //         else $properties[0][$key] = $value['type'];
            //     }
            // }
            $json = [];
            foreach($data['properties'] as $key => $value){
                if($value['type'] == 'object'){
                    foreach($value['properties'] as $k => $v){
                        if(isset($v['enum'])) $json[$k] = "enum:".implode(',',$v['enum']);
                        else $json[$k] = $v['type'];
                    }
                }
                elseif ($value['type'] == 'array'){
                    foreach($value['items']['properties'] as $k => $v){
                        if(isset($v['enum'])) $json[$key][$k] = 'enum:'.implode(',',$v['enum']);
                        else $json[$key][$k] = $v['type'];
                    }
                }
                else {
                    if(isset($value['enum'])) $json[$key] = 'enum:'.implode(',',$value['enum']);
                    else $json[$key] = $value['type'];
                }
            }
            dd($json);
            return $properties;
        }
        catch(\Exception $e){
            throw new \Exception($e->getMessage());
        }
    }
}
