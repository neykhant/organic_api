<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Utils\ErrorType;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuthController extends Controller
{

    public function index()
    {
        $users = User::all();
        return response()->json(["status" => "success", "data" => UserResource::collection($users), "total" => count($users)]);
    }

    public function user()
    {
        $user = Auth::user();

        return jsend_success(new UserResource($user), JsonResponse::HTTP_OK);
    }

    public function login(LoginUserRequest $request)
    {
        $phone = $request->input('phone');
        $password = $request->input('password');
        $remember_me = $request->input('remember_me');

        try {
            $user = User::where('phone', '=', $phone)->first();

            if (is_null($user)) {
                return jsend_fail(['message' => 'User does not exists.'], JsonResponse::HTTP_UNAUTHORIZED);
            }

            if (!Auth::attempt(['phone' => $phone, 'password' => $password])) {
                return jsend_fail(['message' => 'Invalid Credentials.'], JsonResponse::HTTP_UNAUTHORIZED);
            }

            $user = Auth::user();

            // if ($remember_me) {
            //     Passport::personalAccessTokensExpireIn(now()->addHours(24));
            // } else {
            //     Passport::personalAccessTokensExpireIn(now()->addHours(12));
            // }

            $tokenResult = $user->createToken('IO Token');
            $access_token = $tokenResult->accessToken;
            $expiration = $tokenResult->token->expires_at->diffInSeconds(now());

            return jsend_success([
                'name' => $user->name,
                'phone' => $user->phone,
                'position' => $user->position,
                'shop' => $user->shop,
                'token_type' => 'Bearer',
                'access_token' => $access_token,
                'expires_in' => $expiration
            ], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            Log::error('Login Failed!', [
                'code' => $e->getCode(),
                'trace' => $e->getTrace(),
            ]);
            return jsend_error(['message' => 'Invalid Credentialsggg']);
        }

    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255|unique:users',
            'position' => 'required|string',
            'shop_id' => 'required',
        ]);

        try {
            $user = new User();
            $user->name = $request->input('name');
            $user->phone = $request->input('phone');
            $user->position = $request->input('position');
            $user->shop_id = $request->input('shop_id');
            $user->password = Hash::make('123123');

            $user->save();
            return jsend_success(new UserResource($user), JsonResponse::HTTP_CREATED);
        } catch (Exception $e) {
            return jsend_error(__('api.saved-failed', ['model' => 'User']), $e->getCode(), ErrorType::SAVE_ERROR, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string',
            'shop_id' => 'required',
        ]);

        try {
            $user = Auth::user();
            $user->name = $request->input('name');
            $user->position = $request->input('position');
            $user->shop_id = $request->input('shop_id');

            $user->save();

            return jsend_success(new UserResource($user), JsonResponse::HTTP_CREATED);
        } catch (Exception $e) {
            return jsend_error(__('api.updated-failed', ['model' => 'User']), $e->getCode(), ErrorType::UPDATE_ERROR, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return jsend_success(['message' => 'Successfully Logout.'], JsonResponse::HTTP_ACCEPTED);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:6|confirmed',
            'new_password_confirmation' => 'required'
        ]);

        if (!(Hash::check($request->get('current_password'), Auth::user()->password))) {
            // The passwords matches
            return jsend_fail(['message' => 'Your current password does not matches with the password.']);
        }

        if (strcmp($request->get('current_password'), $request->get('new_password')) == 0) {
            // Current password and new password same
            return jsend_fail(['message' => 'New Password cannot be same as your current password.']);
        }

        //Change Password
        $user = Auth::user();
        $user->password = Hash::make($request->get('new_password'));
        $user->save();

        return jsend_success(['message' => 'Password successfully changed!'], JsonResponse::HTTP_CREATED);
    }
    
    public function destroy(User $user)
    {

        try {
            $user->delete();

            return jsend_success(null, JsonResponse::HTTP_NO_CONTENT);
        } catch (ModelNotFoundException $exception) {
            return jsend_error(["error" => 'Data Not Found.'], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}
