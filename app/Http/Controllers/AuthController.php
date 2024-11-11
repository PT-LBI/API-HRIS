<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
// use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

// use Tymon\JWTAuth\Facades\JWTAuth;
// use App\Models\User;
// use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;


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
                'message' => "Maaf, email atau password salah, silahkan coba lagi."
            ], 401);
        } else {
            $user = auth()->user();
            if (auth()->payload()->get('exp') < now()->timestamp) {
                // Jika token kadaluarsa
                return response()->json([
                    'code' => 401,
                    'status' => 'Unauthorized',
                    'message' => "Maaf, session anda telah berakhir. Silahkan login kembali"
                ], 401);
            }

            // Check if the user has a role of 'finance' or 'superadmin'
            if (!in_array($user->user_role, ['superadmin','admin','finance','manager','owner', 'hr'])) {
                return response()->json([
                    'code' => 403,
                    'status' => 'Forbidden',
                    'message' => "Anda tidak memiliki izin untuk login!"
                ], 403);
            } else {
                $user = Auth::user();
                $user->update(['api_token' => $token]); 

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
            // Jika kredensial salah, langsung tampilkan pesan kesalahan
            return response()->json([
                'code' => 401,
                'status' => 'Unauthorized',
                'message' => "Maaf, email atau password salah, silahkan coba lagi."
            ], 401);
        } else {
            // Cek apakah token sudah kadaluarsa
            if (auth()->payload()->get('exp') < now()->timestamp) {
                return response()->json([
                    'code' => 401,
                    'status' => 'Unauthorized',
                    'message' => "Maaf, session anda telah berakhir. Silahkan login kembali"
                ], 401);
            }

            $user = auth()->user();
    
            // Check if the user has a role of 'finance' or 'superadmin'
            if (!in_array($user->user_role, ['superadmin','admin','finance','manager','owner', 'hr', 'staff'])) {
                return response()->json([
                    'code' => 403,
                    'status' => 'Forbidden',
                    'message' => "Anda tidak memiliki izin untuk login!"
                ], 403);
            } else {
                $user = Auth::user();
                $user->update(['api_token' => $token]); 
                
                $output = [
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Berhasil Login',
                    'result'     => [
                        'user'          => convertResponseSingle(auth()->user()),
                        'data_token'    => $token,
                    ]
                ];
            }
        }
        
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
            'expires_in' =>  auth()->factory()->getTTL() 
            // 'expires_in' => auth()->factory()->getTTL() * 60 * 60 * 7 * 100
        ]);
    }

    public function update_fcm_token(Request $request)
    {
        $id = auth()->user()->user_id;
        $check_data = User::find($id);

        if (!$check_data) {
            return response()->json([
                'code' => 404,
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
                'result' => [],
            ], 404);
        }

        $rules = [
            'user_fcm_token' => 'required',
        ];

        // Validate the request
        $validator = Validator::make($request->all(), $rules);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => 'error',
                'message' => $validator->messages()
            ], 422);
        }

        $res = $check_data->update([
            'user_fcm_token'    => $request->user_fcm_token,
        ]);

        if ($res) {
            $output = [
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'Berhasil mengubah data',
                'result'     => []
            ];
        } else {
            $output = [
                'code'      => 500,
                'status'    => 'error',
                'message'   => 'Gagal mengubah data',
                'result'     => []
            ];
        }

        return response()->json($output, 200);
    }
    
}
