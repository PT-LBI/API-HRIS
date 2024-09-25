<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
// use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

// use Tymon\JWTAuth\Facades\JWTAuth;
// use App\Models\User;
// use Illuminate\Support\Str;


class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'login_mobile']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json([
                'code' => 401,
                'status' => 'Unauthorized',
                'message' => "Anda belum memiliki permission!"
            ], 200);
        } else {
            $user = auth()->user();

            // Check if the user has a role of 'finance' or 'superadmin'
            if (!in_array($user->user_role, ['superadmin','admin','finance','warehouse','owner', 'hr'])) {
                return response()->json([
                    'code' => 403,
                    'status' => 'Forbidden',
                    'message' => "Anda tidak memiliki izin untuk login!"
                ], 403);
            } else {
                $output = [
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Berhasil Login',
                    'result'     => [
                        'user'          => convertResponseSingle(auth()->user()),
                        'data_token'    => $token,
                        'menu'          => 'menu'
                    ]
                ];
            }

        }

        return response()->json($output, 200);
    }


    public function login_mobile()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json([
                'code' => 401,
                'status' => 'Unauthorized',
                'message' => "Anda belum memiliki permission!"
            ], 401);
        }

        $user = auth()->user();

        // Check if the user has a role of 'finance' or 'superadmin'
        if (!in_array($user->user_role, ['staff'])) {
            return response()->json([
                'code' => 403,
                'status' => 'Forbidden',
                'message' => "Anda tidak memiliki izin untuk login!"
            ], 403);
        }

        $output = [
            'code'      => 200,
            'status'    => 'success',
            'message'   => 'Berhasil Login',
            'result'     => [
                'user'          => convertResponseSingle(auth()->user()),
                'data_token'    => $token,
            ]
        ];
        

        return response()->json($output, 200);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 1
            // 'expires_in' => auth()->factory()->getTTL() * 60 * 60 * 7
        ]);
    }
    
}
