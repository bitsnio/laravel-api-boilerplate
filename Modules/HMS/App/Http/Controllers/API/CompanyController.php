<?php

namespace Modules\HMS\App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Modules\HMS\App\Models\Company;
use Modules\HMS\App\Http\Requests\StoreCompanyRequest;
use Modules\HMS\App\Http\Requests\UpdateCompanyRequest;
use Modules\HMS\App\Http\Resources\CompanyResource;
use App\Models\User;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Modules\HMS\Database\Seeders\additionalServices;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    
    public function index(){
        try { 
            $data = Company::all();
            // return Helper::successResponse($data);
            return Helper::successResponse(CompanyResource::collection($data));
        }
    catch (\Throwable $th) {
        return Helper::errorResponse($th->getMessage());
    }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request)
    {
        DB::beginTransaction();
        try{
          
             // $company = Company::create($request->validated());
            $userId = JWTAuth::parseToken()->authenticate();
            $company = $request->validated();
            $company['created_by'] = $userId->id;
            $userName = $company['user_name'];
            $userPassword = $company['password'];
            $userModuleId = $company['main_module_id'];
            unset($company['user_name']);
            unset($company['password']);
            unset($company['main_module_id']);
            
            $companyId = Company::create($company);
            
            $user = JWTAuth::parseToken()->authenticate();
            
            User::create([
                'user_type' => 'ca',
                'name' => $userName,
                'email' => $company['company_email'], // Assuming a foreign key relationship
                'password' => Hash::make($userPassword),
                'company_id' => $companyId->id,
                'main_module_id' => $userModuleId,
                'created_by' => $user->id
            ]);
            
            $seeder = new AdditionalServices;
            $result = $seeder->run($companyId->id);
            if($result['success']) {
                DB::commit();
                return Helper::successResponse($data = $this->getCompanies($companyId->id), 'Company successfully created');
            }
            else{
                return Helper::errorResponse($result['message']);
            }
           
            }catch(\Throwable $e){
                DB::rollback();
                return Helper::errorResponse($e->getMessage());
            }
       
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company){
        try  { 
            return Helper::successResponse(CompanyResource::make($company));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        // dd('hello');
        try { 

            $companyId = $request->company->id;
            $company = $request->validated();
            $user = User::where('user_type', 'ca')->where('company_id',$companyId)->first();
            $user['email'] = $request['company_email'];
            if($request['password']){
                $user['password'] = Hash::make($request['password']);
            }
            $user['main_module_id'] = $request['main_module_id'];
            $userId = JWTAuth::parseToken()->authenticate();
            $company['updated_by'] = $userId->id;
            $user['updated_by'] = $userId->id;
            Company::where('id', $companyId)->update($company);
            $user->save();
            $userId = JWTAuth::parseToken()->authenticate();
            $data = $this->getCompanies( $companyId);
            return Helper::successResponse($data);
            // return Helper::successResponse(CompanyResource::make($company));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company) {
        try {
            if (!$company) {
                return Helper::errorResponse('Record not found', 404);
            }

            // Set the is_deleted field to 1
            $userId = JWTAuth::parseToken()->authenticate();
            $company->deleted_by = $userId->id;
            $company->is_deleted = 1;
            $company->save();

            return Helper::successResponse('Successfully Deleted', 200);
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
    private function getCompanies($id=null){
       $companies_query =  
            DB::table('users')
                ->Join(
                    'companies', 
                    'users.company_id', '=', 'companies.id')
                ->leftjoin(
                    'main_modules', 
                    DB::raw("FIND_IN_SET(main_modules.id,users.main_module_id)"),">",DB::raw("'0'"))
                ->select(
                    'companies.id', 
                    'companies.company_name',
                    'companies.company_email',
                    'users.name',
                    'companies.country',
                    'companies.city',
                    'companies.company_phone',
                    'companies.street_address',
                    'main_modules.title',
                    'main_modules.id as main_module_id'
                    )
                ->where('users.user_type', '=', 'ca');
            if($id !== null){
                $companies_query->where('companies.id','=',$id);
            } 
               
            $data = $companies_query->get();
            return $this->prepareCompnayResponse($data);

    }

    private function prepareCompnayResponse($inputArray){
        $resultArray = [];
        $i = 0;
        $mmKey = '';
        $firstIteration = true;
        foreach ($inputArray as $item) {
            
            if($mmKey != $item->id){
                if(!$firstIteration) $i++;
                $resultArray[$i] = [
                    'id'=>$item->id,
                    'company_name' => $item->company_name,
                    'company_email' => $item->company_email,
                    'user_name'=>$item->name,
                    'country' =>$item->country,
                    'city'=>$item->city,
                    'company_phone'=>$item->company_phone,
                    'street_address'=>$item->street_address,
                    'main_module_id'=>[]
                ];
              
                // $resultArray[$i]['type'] = ($item->Sub_slug != null)?'sub':'link';
            }
            
            $resultArray[$i]['main_module_id'][] = [
               
                'title' => $item->title,
                'id'=> $item->main_module_id
            ];
            $mmKey = $item->id;
            $firstIteration = false;
        }
        
        return $resultArray;
    }
}
