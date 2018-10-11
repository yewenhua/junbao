<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\UserLoginRequest;
use App\Http\Requests\API\UserRegistRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use JWTAuth;
use Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Hash;
use App\User;
use App\Http\Models\Ptype;
use App\Http\Models\Pbrand;

class AuthController extends Controller
{
    /**
     * Log a user in.
     *
     * @param UserLoginRequest $request
     *
     * @return JsonResponse
     */
    public function login(UserLoginRequest $request)
    {
        $params = $request->only('email', 'password');
        $credentials = array(
            "phone" => $params['email'],
            "password" => $params['password'],
            "isopen" => 1
        );
        try {
            // attempt to verify the credentials and create a token for the user
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            Log::error($e);

            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        $user = User::where('phone', $credentials['phone'])->first();
        $roles = $user->roles;
        foreach ($roles as $role) {
            $permissions = $role->permissions;
        }

        $ptObj = new Ptype();
        $ptype = $ptObj->openList();

        $pbObj = new Pbrand();
        $pbrand = $pbObj->openList();

        return response()->json(compact('token', 'user', 'ptype', 'pbrand'));
    }

    /**
     * Log the current user out.
     *
     * @return JsonResponse
     */
    public function logout()
    {
        if ($token = JWTAuth::getToken()) {
            try {
                JWTAuth::invalidate($token);
            } catch (Exception $e) {
                Log::error($e);
            }
        }

        return response()->json();
    }

    public function register(UserRegistRequest $request) {
        $newUser = [
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => bcrypt($request->get('password')),
        ];
        $user = User::create($newUser);
        $token = JWTAuth::fromUser($user);

        return response()->json(compact('token'));
    }

    public function refresh(){
        $oldToken = JWTAuth::getToken();
        $token = JWTAuth::refresh($oldToken);

        return response()->json(compact('token'));
    }
}
