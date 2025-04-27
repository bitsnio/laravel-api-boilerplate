<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Modules\HMS\App\Utilities\Helper;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller {
    use JsonResponse;

    /**
    * Get a JWT via given credentials.
    *
    * @return \Illuminate\Http\JsonResponse
    */

    public function login( Request $request ) {
       
        try {
            $input = $request->only( 'email', 'password' );

            $validateUser = Validator::make( $input, [
                'email' => 'required',
                'password' => 'required',
            ] );
            if ( $validateUser->fails() ) {
                return  JsonResponse::errorResponse(  $validateUser->errors() , 400 );
            }

            if ( ! $token = JWTAuth::attempt( $input ) ) {
                return  JsonResponse::errorResponse( 'Invalid login credentials', 400 );
            }

            $user = User::where( 'email', $request->email )->first();
            $customClaims = [ 'user_id' => $user->id, 'user_name' => $user->name, 'company_id' => $user->company_id ];
            $token = JWTAuth::claims( $customClaims )->attempt( $input );
            //  dd( $user );
            // $data = Helper::usersModules( $user->id );
            return JsonResponse::successResponse([], 'User Logged In Successfully', 200, $token );

        } catch ( JWTException $e ) {
            return JsonResponse::errorResponse( $e->getMessage() );
        } catch ( \Throwable $th ) {
            return JsonResponse::errorResponse( $th->getMessage() );
        }
    }

    public function register( Request $request ) {

        $input = $request->only( 'name', 'email', 'password', 'confirm_password' ,'company_id');

        $validator = Validator::make( $input, [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
            'company_id'=> 'required'
        ] );

        if ( $validator->fails() ) {
            return JsonResponse::errorResponse( $validator->errors() );
        }

        $input[ 'password' ] = Hash::make( $input[ 'password' ] );
        // use bcrypt to hash the passwords
        unset( $input[ 'c_password' ] );
        // excldue repeat_password
        $user = User::create( $input );
        // eloquent creation of data

        $success[ 'user' ] = $user;

        return JsonResponse::successResponse( $success, 'user registered successfully' );
    }

    /**
    * Log the user out ( Invalidate the token ).
    *
    * @return \Illuminate\Http\JsonResponse
    */

    public function logout() {
        Auth::logout();

        return response()->json( [ 'message' => 'Successfully logged out' ] );
    }

    /**
    * Refresh a token.
    *
    * @return \Illuminate\Http\JsonResponse
    */

    public function refresh() {
        return JsonResponse::successResponse( Auth::refresh() );
    }

    /**
    * Get the authenticated User.
    *
    * @return \Illuminate\Http\JsonResponse
    */

    public function addRoles() {
        $role = Role::create( [ 'name' => 'writer' ] );
        $permission = Permission::create( [ 'name' => 'edit articles' ] );
    }
}
