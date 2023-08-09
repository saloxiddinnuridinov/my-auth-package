<?php

namespace My\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\ValidatorResponse;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
class AuthController extends Controller
{
    function __construct()
    {
        $this->middleware('auth:api', ['except' => ['register', 'login']]);
    }

    /**
     * * * * * *  * * * *  * * * * * *
     * @OA\Post(
     * path="/api/login",
     * summary="login",
     * description="Login student bt emaill, password",
     * tags={"Auth"},
     * @OA\RequestBody(required=true, description="Pass Student credentials",
     *    @OA\MediaType(mediaType="multipart/form-data",
     *       @OA\Schema(type="object", required={"email","password"},
     *          @OA\Property(property="email", type="string", format="text", example="user@gmail.com"),
     *          @OA\Property(property="password", type="string", format="password", example="admin123"),
     *      ),
     *    ),
     * ),
     *      @OA\Response(response=404,description="Not found",
     *          @OA\JsonContent(ref="#/components/schemas/Error"),
     *      ),
     * )
     */
    public function login(Request $request)
    {
        $rules = [
            'email' => 'required|string|email',
            'password' => 'required',
        ];
        $validator = new ValidatorResponse();
        $validator->check($request, $rules);
        if ($validator->fails) {
            return response()->json($validator->response, 400);
        }
        try {
            $model = User::where('email', $request->email)->first();
            if ($model) {
                if (Hash::check($request->password, $model->password)) {
                    if ($model->is_active) {
                        if ($model->role) {
                            $credentials = $request->only('email', 'password');
                            $token = auth('api')->attempt($credentials);
                            $user = auth('api')->user();
                            try {
                                return response()->json([
                                    'user' => $user,
                                    'role' => $model->role,
                                    'is_manager' => $model->isManager()->exists(),
                                    'authorization' => [
                                        'token' => $token,
                                        'type' => 'bearer',
                                    ]
                                ], 200);
                            } catch (\Exception $exception) {
                                return response()->json([
                                    'errors' => [$exception->getMessage()]
                                ], 400);
                            }

                        } else {
                            return response()->json(['errors' => ['Role not given']], 400);
                        }
                    } else {
                        return response()->json(['errors' => ['User is not active']], 400);
                    }
                } else {
                    return response()->json(['errors' => ['Password is incorrect']], 400);
                }
            } else {
                return response()->json(['errors' => ['User is not found']], 400);
            }
        } catch (\Exception $exception) {
            return response()->json(['errors' => [$exception->getMessage()]], 400);
        }
    }

    /**
     * @OA\Post(
     * path="/api/register",
     * summary="Post a new data",
     * description="Register",
     * tags={"Auth"},
     * @OA\RequestBody(required=true, description="register auth",
     *   @OA\MediaType(mediaType="multipart/form-data",
     *       @OA\Schema(type="object", required={"name","surname","email","password"},
     *          @OA\Property(property="name", type="string", format="text", example="Student"),
     *          @OA\Property(property="surname", type="string", format="text", example="Surname"),
     *          @OA\Property(property="email", type="string", format="email", example="user@gmail.com"),
     *          @OA\Property(property="password", type="string", format="password", example="admin123"),
     *      ),
     *    ),
     * ),
     *
     *    @OA\Response(response=404,description="Not found",
     *        @OA\JsonContent(ref="#/components/schemas/Error"),
     *      ),
     * )
     */
    public function register(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ];
        $validator = new ValidatorResponse();
        $validator->check($request, $rules);
        if ($validator->fails) {
            return response()->json($validator->response, 400);
        }
        $user = User::create([
            'name' => $request->name,
            'surname' => $request->surname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $token = auth()->login($user);
        $user = auth('api')->user();
        return response()->json([
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    /**
     * @OA\Post(
     * path="/api/logout",
     * summary="Post a new data",
     * description="Logout",
     * tags={"Auth"},
     * security={{ "api": {} }},
     *
     * @OA\Response(response=404,description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/Error"),
     * ),
     * )
     */
    public function logout()
    {
        auth('api')->logout();
        return response()->json([
            'success' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * @OA\Post(
     * path="/api/refresh",
     * summary="Post a new data",
     * description="Logout",
     * tags={"Auth"},
     * security={{ "api": {} }},
     * @OA\Response(response=404,description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/Error"),
     * ),
     * )
     */
    public function refresh()
    {
        return response()->json([
            'success' => 'success',
            'data' => auth('api')->user(),
            'authorization' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }
}

