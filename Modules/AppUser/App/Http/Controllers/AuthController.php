<?php

namespace Modules\AppUser\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\AppUsers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->guard('app_users')->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid email or password',
            ], 401);
        }
        $user = auth()->guard('app_users')->user();
        $id = $user->id;
        $name = $user->name;

        return response()->json([
            'access_token' => $token,
            "data" => $user,
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'phone' => 'required|string|unique:app_users,phone',
            'email' => 'required|string|email|unique:app_users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 400);
        }

        $user = AppUsers::create([
            'name' => $request->name,
            'phone' => "009665" . $request->phone,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'access_token' => $token,
            "data" => $user,
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    /**
     * Log out the user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->guard('app_users')->logout();
        return response()->json(['message' => 'عملية تسجيل الخروج تمت بنجاح']);
    }

    public function updateLocation(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'long' => 'required|numeric',
        ]);
        
        if ($validate->fails()) {
            $errors = $validate->errors()->all(); 
        
            return response()->json([
                'success' => false,
                'message' => $errors[0] 
            ], 401);
        }

        $user = auth()->guard('app_users')->user();
        $data = $validate->validated();
        $user->lat = $data['lat'];
        $user->long = $data['long'];

        $user->save();

        return response()->json([
            'message' => 'تم تحديث الموقع بنجاح!',
            'user' => $user
        ]);
    }

    public function setDefault(Address $address)
    {
        $user = auth()->guard('app_users')->user();

        if ($address->user_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $address->update(['hypothetical' => true]);

        Address::where('user_id', $user->id)
            ->where('id', '!=', $address->id)
            ->update(['hypothetical' => false]);

        return response()->json(['message' => 'تم تحديد هذا العنوان كافتراضي']);
    }
}