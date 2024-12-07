<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\JsonResponse;
use App\Traits\validationHelper;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class AutocompleteController extends Controller
{
    use JsonResponse,validationHelper;
    public function index(Request $request){

        try { 
            $user= auth()->user();//JWTAuth::parseToken()->authenticate();
            $validater = Validator::make($request->all(), 
            [
                'search_field' => 'required',
                'table' => 'required',
                'field_value' => '',
                'where'=>''
            ]);

            // return $request->where;


            if($validater->fails()){
                return  JsonResponse::errorResponse(validationHelper::validationErrorsToString($validater->errors()),400);
            }
            
            if($request->table == 'users') return JsonResponse::errorResponse("Data Is not Allowed to retrieve from this table");

            $table = $request->table;
            $search_field = $request->search_field;
            $field_value = $request->field_value;
            $where = $request->where;

        
            $result = DB::table($table)->select($search_field." as title", $table.".id");

            if(Schema::hasColumn($table, 'property_id') && !Schema::hasColumn($table, 'company_id')){
                $result->join('properties',$table.'.property_id','=','properties.id')->where('properties.company_id',$user->company_id);
            }

            if(Schema::hasColumn($table, 'company_id')){
                $result->where('company_id',$user->company_id);
            }
            if(Schema::hasColumn($table, 'is_deleted')){
                $result->where($table.'.is_deleted',0);
            }

            if($request->has('where') && !empty($request->where)){
            
                $where = (!is_array($request->where))? json_decode($request->where):$request->where;
             
                foreach ($where as $field => $value) {
                    $result->where($field, $value);
                }
            }

            $result->where($search_field, 'LIKE', "%".$field_value."%");
            $result = ($search_field === 'room_number')? $result->orderByRaw('CAST(substring_index(room_number, "-",-1) as unsigned) ASC'):$result->orderBy($search_field,'asc');

            return $result->get();
        }
        catch (\Exception $e) {
            return JsonResponse::errorResponse($e->getMessage());
        }
    }

    public function autocompleteWithoutId(Request $request){

        try { 
            $user= auth()->user();
            $validater = Validator::make($request->all(), 
            [
                'search_field' => 'required',
                'table' => 'required',
                'field_value' => '',
                'where'=>''
            ]);

            // $where =  json_decode($request->where);
            // return $where->property_id;
            if($request->has('where') && !empty($request->where)){
                $where = (!is_array($request->where))? json_decode($request->where):$request->where;
            }
            else{
                $where = false;
            }


            if($validater->fails()){
                return  JsonResponse::errorResponse(validationHelper::validationErrorsToString($validater->errors()),400);
            }
            
            if($request->table == 'users') return JsonResponse::errorResponse("Data Is not Allowed to retrieve from this table");

            $table = $request->table;
            $search_field = $request->search_field;
            $field_value = $request->field_value;
           
            $result = DB::table($table);
            
            if($where && is_object($where) && isset($where->property_id) && $where->property_id == 0){

                $result->selectRaw("LOWER(".$search_field.") as title");
            }
            else{
                $result->select($search_field." as title");
            }
            // if(Schema::hasColumn($table, 'property_id') && !Schema::hasColumn($table, 'company_id')){
            //     $result->join('properties',$table.'.property_id','=','properties.id')->where('properties.company_id',$user->company_id);
            // }

            if(Schema::hasColumn($table, 'company_id')){
                $result->where('company_id',$user->company_id);
            }
            if(Schema::hasColumn($table, 'is_deleted')){
                $result->where($table.'.is_deleted', 0);
            }

            if($where){
                foreach ($where as $field => $value) {
                    $result->where($field, $value);
                }
            }

            $query = $result->where($search_field, 'LIKE', "%".$field_value."%");
            $result = ($search_field === 'room_number')? $query->orderByRaw('CAST(substring_index(room_number, "-",-1) as unsigned) ASC'):$query->orderBy($search_field,'asc');
         
            $record =  $result->get()->unique()->toArray();

            return collect( $record )->pluck('title')->all();
            // return DB::getQueryLog();
        }
        catch (\Exception $e) {
            return JsonResponse::errorResponse($e->getMessage());
        }
    }

}
