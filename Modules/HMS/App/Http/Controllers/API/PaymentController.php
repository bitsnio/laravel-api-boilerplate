<?php

namespace Modules\HMS\App\Http\Controllers\API;

use Modules\HMS\App\Http\Controllers\Controller;
use Modules\HMS\App\Models\Payment;
use Modules\HMS\App\Http\Requests\StorePaymentRequest;
use Modules\HMS\App\Http\Requests\UpdatePaymentRequest;
use Modules\HMS\App\Http\Resources\PaymentResource;
use Modules\HMS\App\Http\Resources\ReceiptResource;
use Modules\HMS\App\Models\AdvancePayment;
use Modules\HMS\App\Models\Receipt;
use Modules\HMS\App\Utilities\Helper;
use Illuminate\Support\Facades\DB;
use PHPUnit\TextUI\Help;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class PaymentController extends Controller {
    /**
    * Display a listing of the resource.
    */

    public function index() {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $data = Receipt::with( 'payments' )->where( 'company_id', $user->company_id )->get();
            return Helper::successResponse( ReceiptResource::collection( $data ) );
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

    public function store( StorePaymentRequest $request ) {
        DB::beginTransaction();
        try {
            $payment = $request->validated();
            if ( $payment[ 'total_amount' ] == ( $payment[ 'processed_amount' ] + $payment[ 'paid_amount' ] ) ) {
                $payment[ 'payment_status' ] = 'paid';
            }
            if ( $payment[ 'total_amount' ] > ( $payment[ 'processed_amount' ] + $payment[ 'paid_amount' ] ) ) {
                $payment[ 'payment_status' ] = 'partial';
            }
            $payment[ 'paid_amount' ] = $payment[ 'processed_amount' ];
            if ( isset( $payment[ 'reference_number' ] ) &&  $payment[ 'reference_number' ] != null ) {
                $payment[ 'payment_reference' ] = $payment[ 'reference_number' ];
            }
            $user = JWTAuth::parseToken()->authenticate();
            $payment[ 'created_by' ] = $user->id;
            // $record = Payment::create( $payment );
            if ( strtolower( $payment[ 'payment_method' ] ) == 'adjustment' ) {
                $advance = AdvancePayment::where( 'property_id', $payment[ 'property_id' ] )->get()->toArray();
                if ( empty( $advance ) ) {
                    return Helper::errorResponse( 'No advance payment found against the selected property' );
                }
                $advance_balance = collect( $advance )->sum( 'advance_amount' );
                if ( $advance_balance == 0 ) {
                    return Helper::errorResponse( 'Advance Balance is 0 against the selected property' );
                }
                if ( $payment[ 'paid_amount' ] > $advance_balance ) {
                    return Helper::errorResponse( 'Insufficient balance in advance payments to process this transaction, your current advance balance is '.$advance_balance );
                }
                $advance_payment = [];
                $advance_payment[ 'property_id' ] = $payment[ 'property_id' ];
                $advance_payment[ 'payment_id' ] = $record->id;
                $advance_payment[ 'advance_amount' ] = -$payment[ 'paid_amount' ];
                $advance_payment[ 'payment_method' ] = $payment[ 'payment_method' ];
                $advance_payment[ 'payment_date' ] = $payment[ 'payment_date' ];
                if ( isset( $payment[ 'reference_number' ] ) &&  $payment[ 'reference_number' ] != null ) {
                    $advance_payment[ 'payment_reference' ] = $payment[ 'reference_number' ];
                }
                $advance_payment[ 'created_by' ] = $user->id;
                AdvancePayment::create( $advance_payment );
            }
            $return = Receipt::with( 'payments', 'properties.advancePayments' )->where( 'id', $payment[ 'receipt_id' ] )->get()->toArray();
            $return[ 0 ][ 'batch_number' ] = 'BN-'.str_pad( $return[ 0 ][ 'id' ], 5, '0', STR_PAD_LEFT ).$return[ 0 ][ 'total_merged_amount' ];
            if ( $return[ 0 ][ 'receipt_type' ] == 'payable' ) {
                $return[ 0 ][ 'advance_amount' ] = collect( $return[ 0 ][ 'properties' ][ 'advance_payments' ] )->sum( 'advance_amount' );
                unset( $return[ 0 ][ 'properties' ][ 'advance_payments' ] );
            }
            if ( $return[ 0 ][ 'properties' ] == null ) {
                unset( $return[ 0 ][ 'properties' ] );
            }

            DB::commit();
            return Helper::successResponse( ReceiptResource::collection( $return ) );
        } catch ( \Throwable $th ) {
            DB::rollBack();
            return Helper::errorResponse( $th->getMessage() );
        }
    }

    /**
    * Display the specified resource.
    */

    public function show( Payment $payment ) {
        //
    }

    /**
    * Show the form for editing the specified resource.
    */

    public function edit( Payment $payment ) {
        //
    }

    /**
    * Update the specified resource in storage.
    */

    public function update( UpdatePaymentRequest $request, Payment $payment ) {
        //
    }

    /**
    * Remove the specified resource from storage.
    */

    public function destroy( Payment $payment ) {
        //
    }
}
