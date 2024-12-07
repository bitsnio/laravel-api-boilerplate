<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class createUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // $input = $request->only( 'name', 'email', 'password', 'confirm_password' );

        // $validator = Validator::make( $input, [
        //     'name' => 'required',
        //     'email' => 'required|email|unique:users',
        //     'password' => 'required|min:8',
        //     'confirm_password' => 'required|same:password',
        // ] );

        // if ( $validator->fails() ) {
        //     return JsonResponse::errorResponse( $validator->errors() );
        // }

        // $input[ 'password' ] = Hash::make( $input[ 'password' ] );
        // // use bcrypt to hash the passwords
        // unset( $input[ 'c_password' ] );
        // // excldue repeat_password
        // $user = User::create( $input );
        // // eloquent creation of data

        // $success[ 'user' ] = $user;

        // return JsonResponse::successResponse( $success, 'user registered successfully' );
    }
}
