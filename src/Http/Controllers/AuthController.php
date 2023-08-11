<?php
namespace My\Auth\Http\Controllers;

use App\Http\ValidatorResponse;
use App\Jobs\RegisterEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['register', 'login', 'checkVerifyCode', 'resend', ' ', 'activateUser', 'resetPasswordVerify', 'revokePassword', 'checkResetPasswordVerify',]]);
    }

    /**
     * @OA\Post(
     * path="/api/register",
     * summary="Post a new data",
     * description="Register Student ",
     * tags={"Auth Student"},
     * @OA\RequestBody(required=true, description="Student data  credentials",
     *   @OA\MediaType(mediaType="multipart/form-data",
     *       @OA\Schema(type="object", required={"name","surname","email","password"},
     *          @OA\Property(property="name", type="string", format="text", example="Student"),
     *          @OA\Property(property="surname", type="string", format="text", example="Surname"),
     *          @OA\Property(property="email", type="string", format="email", example="user@gmail.com"),
     *          @OA\Property(property="password", type="string", format="password", example="admin123"),
     *      ),
     *    ),
     * ),
     *    @OA\Response(response=200,description="Successful operation",
     *        @OA\JsonContent(ref="#/components/schemas/User"),
     *      ),
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
            'email' => ['required', 'string', 'max:255', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ];
        $validator = new ValidatorResponse();
        $validator->check($request, $rules);
        if ($validator->fails) {
            return response()->json($validator->response, 400);
        }
        $student = User::where('email', $request->email)->first();
        if ($student) {
            return response()->json([
                'success' => false,
                'message' => "Avval Ro'yxatdan o'tgansiz",
                'data' => null,
                'code' => 400
            ]);
        } else {
            $data = [
                'name' => $request->name,
                'surname' => $request->surname,
                'email' => $request->email,
                'password' => $request->password,
            ];
        }
        $this->dispatch(new RegisterEmail($data));
        return response()->json([
            'success' => true,
            'message' => $request->email . " ga faollashtirish kodi yuborildi",
            'data' => null,
            'code' => 200
        ]);
    }

    /**
     * * * * * *  * * * *  * * * * * *
     * @OA\Post(
     * path="/api/login",
     * summary="login student  ",
     * description="Login student bt emaill, password",
     * tags={"Auth Student"},
     * @OA\RequestBody(required=true, description="Pass Student credentials",
     *    @OA\MediaType(mediaType="multipart/form-data",
     *       @OA\Schema(type="object", required={"email","password"},
     *          @OA\Property(property="email", type="string", format="text", example="user@gmail.com"),
     *          @OA\Property(property="password", type="string", format="password", example="admin123"),
     *      ),
     *    ),
     * ),
     *      @OA\Response(response=200,description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/User"),
     *      ),
     *      @OA\Response(response=404,description="Not found",
     *          @OA\JsonContent(ref="#/components/schemas/Error"),
     *      ),
     * )
     */
    public function login(Request $request)
    {
        $rules = [
            'email' => ['required', 'string', 'max:255', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ];
        $validator = new ValidatorResponse();
        $validator->check($request, $rules);
        if ($validator->fails) {
            return response()->json($validator->response, 400);
        }
        $email = trim($request->email);
        $model = User::where('email', $email)->first();
        if ($model) {
            if (Hash::check($request->password, $model->password)) {
                if (!$model->is_blocked) {
                    $credentials = $request->only('email', 'password');
                    $token = auth('api')->attempt($credentials);
                    if (!$token) {
                        return response()->json(['errors' => ['Unauthorized']], 401);
                    }
                    $model->active_token = $token;
                    $model->update();
                    $user = auth()->user();
                    $user->authorization = [
                        'token' => $token,
                        'type' => 'bearer',
                    ];
                    return response()->json([
                        'success' => true,
                        'message' => "Success",
                        'data' => $user,
                        'code' => 200
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => "Bloklangan " . $model->block_reason,
                        'data' => null,
                        'code' => 403
                    ]);
                }
            } else if ($model->password === md5($request->password)) {
                if (!$model->is_blocked) {
                    $token = auth('api')->login($model);
                    if (!$token) {
                        return response()->json(['errors' => ['Unauthorized']], 401);
                    }
                    $model->active_token = $token;
                    $model->password = Hash::make($request->password);
                    $model->update();
                    $user = auth('api')->user();
                    $user->token = $token;
                    $user->type = 'bearer';

                    return response()->json([
                        'success' => true,
                        'message' => "Success",
                        'data' => $user,
                        'code' => 200
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => "Bloklangan " . $model->block_reason,
                        'data' => null,
                        'code' => 403
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Parol noto'g'ri",
                    'data' => null,
                    'code' => 400
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => "Foydalanuvchi topilmadi",
                'data' => null,
                'code' => 404
            ]);
        }
    }

    /**
     * @OA\Post(
     * path="/api/refresh",
     * summary="Post a new data",
     * description="Logout Student ",
     * tags={"Auth Student"},
     * security={{ "student": {} }},
     * @OA\Response(response=200,description="Successful operation",
     *     @OA\JsonContent(ref="#/components/schemas/User"),
     * ),
     * @OA\Response(response=404,description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/Error"),
     * ),
     * )
     */
    public function refresh()
    {
        return response()->json([
            'success' => true,
            'message' => auth()->guard('api')->user(),
            'data' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ],
            'code' => 200
        ]);
    }

    /**
     * @OA\Post(
     * path="/api/logout",
     * summary="Post a new data",
     * description="Logout Student",
     * tags={"Auth Student"},
     * security={{ "student": {} }},
     * @OA\Response(response=200,description="Successful operation",
     *     @OA\JsonContent(ref="#/components/schemas/User"),
     * ),
     * @OA\Response(response=404,description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/Error"),
     * ),
     * )
     */
    public function logout()
    {
        $student = auth('api')->user();
        $model = User::where('id', $student->id)->first();
        $model->active_token = null;
        $model->update();
        $student->logout();
        return response()->json([
            'success' => true,
            'message' => 'Hisobdan muvaffaqiyatli chiqdi',
            'data' => null,
            'code' => 200
        ]);
    }

    /**
     * @OA\Post (
     *      path="/api/resend-code",
     *      tags={"Auth Student"},
     *      operationId="resend-activate-code",
     *      summary="Verification",
     *      description="Resend activation code",
     *       @OA\RequestBody(
     *          required=true,
     *          description="Resend",
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  type="object",
     *                  required={"email"},
     *                  @OA\Property(property="email", type="string", format="email", example="azimxalilov5443@gmail.com"),
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Student created successfully"),
     *              @OA\Property(property="user", type="string", example="{name,surname . . .}"),
     *          ),
     *      )
     * )
     */
    public function resend(Request $request)
    {
        $rules = [
            'email' => ['required', 'string', 'max:255', 'email'],
        ];
        $validator = new ValidatorResponse();
        $validator->check($request, $rules);
        if ($validator->fails) {
            return response()->json($validator->response, 400);
        }
        $email = trim($request->email);

        $getCache = Cache::store('database')->get($email);

        if ($getCache) {
            $student = User::where('email', $email)->first();
            if (!$student) {
                $sms = new MainNotificationController();
                $message = [
                    'title' => 'Programmer UZ',
                    'verify_code' => $getCache['verify_code'],
                    'name' => $getCache['name'],
                ];
                return $sms->mail($email, $message, 'revoke_password');
            } else {
                $sms = new MainNotificationController();
                $message = [
                    'title' => 'Programmer UZ',
                    'verify_code' => $getCache['verify_code'],
                    'name' => $student->name,
                ];
                return $sms->mail($email, $message, 'revoke_password');
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => "Vaqt tugadi",
                'data' => null,
                'code' => 200
            ]);
        }

    }

    /**
     * @OA\Post (
     *      path="/api/activate-user",
     *      tags={"Auth Student"},
     *      operationId="active_user",
     *      summary="Verification",
     *      description="Ro'yxatdan o'tgandan so'ng userni aktiv qilish",
     *       @OA\RequestBody(required=true,description="Verify",
     *          @OA\MediaType(mediaType="multipart/form-data",
     *              @OA\Schema(type="object",
     *                  required={"email","verify_code"},
     *                  @OA\Property(property="email", type="string", format="email", example="azimxalilov5443@gmail.com"),
     *                  @OA\Property(property="verify_code", type="integer", format="number", example=123456),
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200,description="Success",
     *          @OA\JsonContent(@OA\Property(property="message", type="string", example="SUCCESS")),
     *      )
     * )
     */

    public function activateUser(Request $request)
    {
        $rules = [
            'email' => 'required|email|unique:users,email',
            'verify_code' => 'required|numeric',
        ];
        $validator = new ValidatorResponse();
        $validator->check($request, $rules);
        if ($validator->fails) {
            return response()->json($validator->response, 400);
        }

        $email = trim($request->email);

        $getCache = Cache::store('database')->get($email);

        if (Cache::store('database')->has($email)) {

            if ($getCache['verify_code'] == $request->verify_code) {
                try {
                    $student = User::where('email', $email)->first();
                    if (!$student) {
                        $student = new User();
                        $student->name = $getCache['name'];
                        $student->surname = $getCache['surname'];
                        $student->email = $email;
                        $student->password = Hash::make($getCache['password']);
                        $student->save();
                    } else {
                        $student->name = $getCache['name'];
                        $student->surname = $getCache['surname'];
                        $student->email = $email;
                        $student->password = Hash::make($getCache['password']);
                    }
                    $token = JWTAuth::fromUser($student);
                    $student->active_token = $token;
                    $student->update();
                    $authorization = [
                        'user' => $student,
                        'token' => $token,
                        'type' => 'bearer',
                    ];

                    Cache::store('database')->forget($email);

                    return response()->json([
                        'success' => true,
                        'message' => "Akkount faollashdi",
                        'data' => $authorization,
                        'code' => 200
                    ]);
                } catch (\Exception $exception) {
                    return response()->json([
                        'success' => false,
                        'message' => "Xatolik yuz berdi keyinroq urinib ko'ring",
                        'data' => null,
                        'code' => 400
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'kod xato',
                    'data' => $getCache['verify_code'],
                    'code' => 400
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'foydalanuvchi topilmadi',
                'data' => null,
                'code' => 404
            ]);
        }
    }

    /**
     * @OA\Post (
     *      path="/api/revoke-password",
     *      tags={"Auth Student"},
     *      operationId="revoke_password",
     *      summary="revoke Password",
     *      description="Parolni esdan chiqardi tizimga kirish uchun yangi parol so'rash",
     *       @OA\RequestBody(required=true,description="Reset Password",
     *          @OA\MediaType(mediaType="multipart/form-data",
     *              @OA\Schema(type="object",required={"email"},
     *                  @OA\Property(property="email", type="string", format="email", example="azimxalilov5443@gmail.com"),
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200,description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Student created successfully"),
     *              @OA\Property(property="user", type="string", example="{name,surname . . .}"),
     *              @OA\Property(property="authorization", type="string", example="{token,type}"),
     *          ),
     *      )
     * )
     */

    public function revokePassword(Request $request)
    {
        $rules = [
            'email' => ['required', 'exists:users,email'],
        ];
        $validator = new ValidatorResponse();
        $validator->check($request, $rules);
        if ($validator->fails) {
            return response()->json($validator->response, 400);
        }
        $login = $request->email;
        $student = User::where('email', $login)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => "Foydalanuvchi topilmadi!",
                'data' => null,
                'code' => 404
            ]);
        }

        $data = [
            'name' => $student->name,
            'surname' => $student->surname,
            'email' => $student->email,
        ];

        $this->dispatch(new RevokePassword($data));

        return response()->json([
            'success' => true,
            'message' => $request->email . " ga tasdiqlash kodi yuborildi",
            'data' => null,
            'code' => 200
        ]);
    }

    /**
     * @OA\Post (
     *      path="/api/check-verify-code",
     *      tags={"Auth Student"},
     *      operationId="check_verify_code",
     *      summary="revoke Password",
     *      description="Activatsiya kodi to'g'riligini tekshirish",
     *       @OA\RequestBody(required=true,description="Check code",
     *          @OA\MediaType(mediaType="multipart/form-data",
     *              @OA\Schema(type="object",required={"email","verify_code"},
     *                  @OA\Property(property="email", type="string", format="email", example="azimxalilov5443@gmail.com"),
     *                  @OA\Property(property="verify_code", type="integer", format="number", example=123456),
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200,description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Student created successfully"),
     *              @OA\Property(property="user", type="string", example="{name,surname . . .}"),
     *              @OA\Property(property="authorization", type="string", example="{token,type}"),
     *          ),
     *      )
     * )
     */

    public function checkVerifyCode(Request $request)
    {
        $rules = [
            'email' => ['required', 'exists:users,email'],
            'verify_code' => ['required', 'numeric'],
        ];
        $validator = new ValidatorResponse();
        $validator->check($request, $rules);
        if ($validator->fails) {
            return response()->json($validator->response, 400);
        }

        $getCache = Cache::store('database')->get(trim($request->email));
        if (Cache::store('database')->has(trim($request->email))) {
            if ($getCache['verify_code'] == $request->verify_code) {
                try {
                    return response()->json([
                        'success' => true,
                        'message' => 'Kod To`g`ri kiritildi',
                        'data' => $getCache['verify_code'],
                        'code' => 200
                    ]);
                } catch (\Exception $exception) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Xatolik yuz berdi',
                        'data' => null,
                        'code' => 400
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'kod xato',
                    'data' => $request->verify_code,
                    'code' => 400
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Foydalanuvchi topilmadi',
                'data' => null,
                'code' => 404
            ]);
        }
    }

    /**
     * @OA\Post (
     *      path="/api/reset-password-verify",
     *      operationId="reset_password_verify",
     *      tags={"Auth Student"},
     *      summary="reset Password Verify",
     *      description="Tizimga kirish uchun tasdiqlash parolini to'g'ri yozgan bulsa endi yangi parol kiritish",
     *       @OA\RequestBody(required=true,description="Reset Password",
     *          @OA\MediaType(mediaType="multipart/form-data",
     *              @OA\Schema(type="object",
     *                  required={"email","password","password_confirmation","verify_code"},
     *                  @OA\Property(property="email", type="string", format="email", example="azimxalilov5443@gmail.com"),
     *                  @OA\Property(property="verify_code", type="integer", format="number", example=123456),
     *                  @OA\Property(property="password", type="string", format="text", example="admin123"),
     *                  @OA\Property(property="password_confirmation", type="string", format="text", example="admin123"),
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="message", type="string", example="Student created successfully"),
     *              @OA\Property(property="user", type="string", example="{name,surname . . .}"),
     *              @OA\Property(property="authorization", type="string", example="{token,type}"),
     *          ),
     *      )
     * )
     */
    public function resetPasswordVerify(Request $request): \Illuminate\Http\JsonResponse
    {
        $rules = [
            'email' => ['required', 'exists:users,email'],
            'verify_code' => ['required', 'numeric'],
            'password' => ['required', 'confirmed', 'min:8'],
        ];
        $validator = new ValidatorResponse();
        $validator->check($request, $rules);
        if ($validator->fails) {
            return response()->json($validator->response, 400);
        }
        $email = trim($request->email);

        $getCache = Cache::store('database')->get($email);

        if (Cache::store('database')->has($email)) {
            if ($getCache['verify_code'] == $request->verify_code) {
                $student = User::where('email', $email)->first();
                $student->password = Hash::make($request->password);
                $token = JWTAuth::fromUser($student);
                $student->active_token = $token;
                $student->update();

                return response()->json([
                    'success' => true,
                    'message' => 'Parol o`zgartirildi',
                    'data' => [
                        'user' => $student,
                        'authorization' => [
                            'token' => $token,
                            'type' => 'bearer',
                        ]
                    ],
                    'code' => 200
                ]);

            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Tasdiqlash kodi xato",
                    'data' => null,
                    'code' => 400
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Foydalanuvchi topilmadi',
                'data' => null,
                'code' => 404
            ]);
        }
    }

    /**
     * @OA\Post(
     *      path="/v1/change-password",
     *      tags={"Auth Student"},
     *      security={{ "student": {} }},
     *      summary="Change Password",
     *      description="Change Password",
     *      @OA\RequestBody(required=true, description="Login pages",
     *          @OA\MediaType(mediaType="multipart/form-data",
     *              @OA\Schema(type="object", required={"password", "password_confirmation"},
     *                  @OA\Property(property="password", type="string", format="password", example="admin123"),
     *                  @OA\Property(property="password_confirmation", type="string", format="password", example="admin123"),
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="email", type="string", example="admin@gmail.com"),
     *              @OA\Property(property="password", type="string", example="admin123"),
     *          ),
     *      ),
     *      @OA\Response(response=422,description="invalid",
     *          @OA\JsonContent(
     *              @OA\Property(property="msg", type="string", example="fail"),
     *          )
     *      )
     * )
     */
    public function changePassword(Request $request)
    {
        $rules = [
            'password' => 'required|min:6|confirmed',
        ];
        $validator = new ValidatorResponse();
        $validator->check($request, $rules);
        if ($validator->fails) {
            return response()->json($validator->response, 400);
        } else {
            $user = auth('student')->user();
            $model = User::where('id', $user->id)->first();
            if ($model) {
                $model->password = Hash::make($request->password);
                $model->update();
                return response()->json([
                    'success' => true,
                    'message' => "Parol o'zgartirildi",
                    'data' => [],
                    'code' => 200
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "User topilmadi",
                    'data' => [],
                    'code' => 404
                ]);
            }
        }
    }

    public function me()
    {
        return response()->json(auth('student')->user(), 200);
    }

}

