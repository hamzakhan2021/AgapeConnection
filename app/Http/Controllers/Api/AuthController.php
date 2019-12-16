<?php

namespace App\Http\Controllers\Api;

use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register (Request $request) {

        $validator = Validator::make($request->all(), [
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:users',
            'password'              => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required',
            'date_of_birth'         => 'required',
            'phone_number'          => 'required|unique:users',
            'gender'                => 'required',
            'education_level'       => 'required',
            'religion'              => 'required',
        ]);

        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $request['password']=Hash::make($request['password']);
        $user = User::create($request->toArray());

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;
        $response = [
            'response'      => 1,
            'message'       => 'User Sucessfully Registered',
            'access_token'  => $token,
            'data'          => $user
            ];

        return response($response, 200);
    }

    public function login (Request $request) {

        $user = User::where('email', $request->email)->first();

        if ($user) {

            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                $response = [
                    'response'      => 1,
                    'message'       => 'Login Sucessfully',
                    'access_token'  => $token,
                    'data'          => $user
                ];
                return response($response, 200);
            } else {
                $response = [
                    'response'      => 0,
                    'message'       => 'Invalid Password',
                ];
                return response($response, 422);
            }

        } else {
            $response = [
                'response'      => 1,
                'message'       => 'User does not exist',
            ];
            return response($response, 422);
        }

    }

    public function logout (Request $request) {

        $token = $request->user()->token();
        $token->revoke();

        $response = [
            'response'      => 1,
            'message'       => 'You have been Successfully logged out!',
        ];
        return response($response, 200);

    }
}
