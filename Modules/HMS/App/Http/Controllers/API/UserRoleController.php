<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use App\Models\UserRole;
use Modules\HMS\App\Http\Requests\StoreUserRoleRequest;
use Modules\HMS\App\Http\Requests\UpdateUserRoleRequest;
use Modules\HMS\App\Http\Resources\UserRoleResource;
use Modules\HMS\App\Models\RoleWithPermission;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try 
        { 
            $user = JWTAuth::parseToken()->authenticate();
            // return $user->company_id;
            $data = $this->getUserRoles($user->company_id);
            return Helper::successResponse(UserRoleResource::collection($data));
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
    public function store(StoreUserRoleRequest $request)
    {
        DB::beginTransaction();
        try{ 
            $userRole=$request->validated();
            $user = JWTAuth::parseToken()->authenticate();
            $userRole['company_id'] = $user->company_id;
            $userRole['created_by'] = $user->id;
            (!isset($userRole['property_id']) || $userRole['property_id'] == null) ? $userRole['property_id'] = 0 : $userRole;
            $newRole = UserRole::create($userRole);
            if(isset($userRole['permissions']) && $userRole['permissions'] != null){
                $role_with_permissions = collect(explode(',', $userRole['permissions']))->map(function ($permission) use ($newRole, $user) {
                    return [
                        'role_id' => $newRole['id'],
                        'permission_id' => $permission,
                        'company_id' => $user->company_id,
                        'created_by' => $user->id,
                    ];
                })->toArray();
                DB::table('role_with_permissions')->insert($role_with_permissions);
            }
            $data = $this->getUserRoles($user->company_id, $newRole->id);
            DB::commit();
            return Helper::successResponse(UserRoleResource::make($data));
        }
        catch (\Throwable $th) {
            DB::rollBack();
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(UserRole $userRole) {
        try { 
            return Helper::successResponse(UserRoleResource::make($userRole));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserRole $userRole)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRoleRequest $request, UserRole $userRole)
    {
        try {
            $id = $request->role; 
            $validatedRequest = $request->validated();
            $user = JWTAuth::parseToken()->authenticate();
            $validatedRequest['company_id'] = $user->company_id;
            $validatedRequest['updated_by'] = $user->id;
            UserRole::where('id', $id)->update($validatedRequest);
            return Helper::successResponse($this->getUserRoles($user->company_id,$id));
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserRole $request, $id)
    {
        try 
        { 
             // $userRole->delete();
            //  dd($userRole);
             if (!$request) {
                return Helper::errorResponse('Record not found', 404);
            }
            $request = $request->toArray();
            // Set the is_deleted field to 1
            $request['is_deleted'] = 1;
            $userID = JWTAuth::parseToken()->authenticate();
            $request['deleted_by'] = $userID->id;
            UserRole::where('id',$id)->update($request);
            return Helper::successResponse('Successfully Deleted', 200);
        }
        catch (\Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }

    private function getUserRoles($company_id,$id=null){
        $userRole_query =  UserRole::with('roleWithPermissions.permissions')
                 ->leftjoin(
                     'sub_modules', 
                     DB::raw("FIND_IN_SET(sub_modules.id, user_roles.sub_module_id)"),">",DB::raw("'0'"))
                 ->select(
                    'user_roles.id',
                    'user_roles.role_name',
                    'sub_modules.id as sub_modules_id',
                    'sub_modules.title',
                 );
             if($id !== null)  $userRole_query->where('user_roles.id',$id);
             $data = $userRole_query->where('user_roles.company_id',$company_id)->get()->toArray();
             return $this->prepareUserRoleResponse($data);
    }
 
     private function prepareUserRoleResponse($inputArray){
        $resultArray = [];
        $i = 0;
        $groupArray = collect($inputArray)->groupBy('id')->toArray();
        foreach ($groupArray as $array){
            $data = [];
            $data['id'] = $array[0]['id'];
            $data['role_name'] = $array[0]['role_name'];
            foreach($array as $item){
                $data['excluded_modules'][] = ['id' => $item['sub_modules_id'], 'title' => $item['title']];
            }
            $excludedPermissions = $array[0]['role_with_permissions'];
            if(!empty($excludedPermissions)){
                $data['excluded_permissions'] = collect($excludedPermissions)->map(function ($permission) {
                    return [
                        'id' => $permission['id'],
                        'title' => $permission['permissions']['title'],
                    ];
                })->toArray();
            }
            else $data['excluded_permissions'] = [];
            $resultArray[$i] = $data;
            $i++;
        }
        return $resultArray;
     }
}
