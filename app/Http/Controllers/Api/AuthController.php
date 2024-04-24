<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login()
    {
        $validator = Validator::make(request()->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {

            if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
                $user = Auth::user();
                $tokenResult = $user->createToken('testing');
                $token = $tokenResult->token;
                // $token->expires_at = now()->addHours(24);
                $success['access_token'] = $tokenResult->accessToken;
                $success['token_type'] = 'Bearer';
                $success['expire_at'] = $token->expires_at->format('Y-m-d H:i:s');
                User::where('id', Auth::user()->id)->update([
                    'last_seen' => now(),
                ]);
                return response()->json($success, 200);
            } else {
                $error['message'] = __('auth.failed');
                return response()->json($error, 422);
            }
        } catch (QueryException $e) {
            return response()->json([
                'message' => "Failed " . $e->errorInfo
            ]);
        }
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|regex:/^\S*$/u',
            'last_name' => 'required|string|regex:/^\S*$/u',
            'email' => 'required|email|unique:users',
            'phone' => 'required|numeric',
            'gender' => 'required|in:male,female',
            'role' => 'required|in:driver,customer',
            'password' => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $input = $request->all();
            $input['password'] = bcrypt($input['password']);
            $user = User::create($input);
            $success['name'] = $user->first_name . ' ' . $user->last_name;
            $success['token'] = $user->createToken('testing')->accessToken;
            return response()->json($success, 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => "Failed " . $e->errorInfo
            ]);
        }
    }

    public function me()
    {
        $user = Auth::user();
        return response()->json($user, 200);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->token();
        $token->revoke();
        $success['message'] = 'Logout Success';
        return response()->json($success, 200);
    }

    public function refresh(Request $request)
    {
        $request->user()->tokens()->delete();
        $tokenResult = $request->user()->createToken('testing refresh');
        $token = $tokenResult->token;
        $token->expires_at;
        $token->save();
        $data['access_token'] = $tokenResult->accessToken;
        $data['token_type'] = 'Bearer';
        $data['expire_at'] = $token->expires_at->format('Y-m-d H:i:s');

        return response()->json($data, 200);
    }
}
