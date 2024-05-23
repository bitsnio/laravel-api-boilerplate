<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Http\Requests\StoreAdvancePaymentRequest;
use Modules\HMS\App\Http\Resources\BaseResource;
use Modules\HMS\App\Models\AdvancePayment;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AdvancePaymentController extends Controller
{
    public function store(StoreAdvancePaymentRequest $request)
    {
        try{
            $advance_payment = $request->validated();
            $user = JWTAuth::parseToken()->authenticate();
            $advance_payment['created_by'] = $user->id;
            $payment = AdvancePayment::create($advance_payment);
            return Helper::successResponse(BaseResource::make(AdvancePayment::find($payment->id)));
        } catch (Throwable $th) {
            return Helper::errorResponse($th->getMessage());
        }
    }
}
