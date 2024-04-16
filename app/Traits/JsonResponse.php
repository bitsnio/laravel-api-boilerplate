<?php
namespace App\Traits; 

trait JsonResponse{
    
    public static function successResponse($data, $message = null, $code = 200,$token=null){
        return response()->json([
			'success'=> true, 
			'message' => $message, 
			'data' => $data,
            'token'=>$token
		], $code);
    }

    public static function errorResponse($message = null, $code=400,$token=null){
        return response()->json([
			'success'=>false,
			'message' => $message,
			'data' => null,
            'token'=>$token
		], $code);
    }
}

?>