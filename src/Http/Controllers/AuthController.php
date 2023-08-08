<?php

namespace App\Http\Controllers\API\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\ValidatorResponse;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthStudentController extends Controller
{
    function __construct()
    {
        $this->middleware('auth:student', ['except' => ['register', 'login']]);
    }

    /**
     * @OA\Post(
     *      path="/api/v1/register-student",
     *      security={{"student":{}}},
     *      operationId="auth_student_store",
     *      tags={"Auth Student"},
     *      summary="Auth Student",
     *      description="Registratsiyadan otish Studentlar uchun",
     *       @OA\RequestBody(required=true, description="lesson save",
     *           @OA\MediaType(mediaType="multipart/form-data",
     *              @OA\Schema(type="object", required={"name", "surname", "email", "password"},
     *                 @OA\Property(property="name", type="string", format="text", example="Salohiddin"),
     *                 @OA\Property(property="surname", type="string", format="text", example="Nuridinov"),
     *                 @OA\Property(property="email", type="string", format="email", example="student@gmail.com"),
     *                 @OA\Property(property="password", type="string", format="password", example="admin123"),
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/Student"),
     *      ),
     *      @OA\Response(response=404,description="Not found",
     *          @OA\JsonContent(ref="#/components/schemas/Error"),
     *      ),
     * )
     */

    public function register(Request $request){
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:students'],
            'password' => ['required', 'string', 'min:8'],
        ];
        $validator = new ValidatorResponse();
        $validator->check($request, $rules);
        if ($validator->fails) {
            return response()->json($validator->response, 400);
        }
        $student = Student::create([
            'name' => $request->name,
            'surname' => $request->surname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $token = auth()->login($student);
        $user = auth('api')->user();
        return response()->json([
            'student' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    /**
     * @OA\Post(
     *      path="/api/v1/login-student",
     *      security={{"student":{}}},
     *      operationId="auth_student_login",
     *      tags={"Auth Student"},
     *      summary="Login Student",
     *      description="Login Parol yordamida kirish Studentlar uchun",
     *       @OA\RequestBody(required=true, description="lesson save",
     *           @OA\MediaType(mediaType="multipart/form-data",
     *              @OA\Schema(type="object", required={"email", "password"},
     *                 @OA\Property(property="email", type="string", format="email", example="student@gmail.com"),
     *                 @OA\Property(property="password", type="string", format="password", example="admin123"),
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Success",
     *          @OA\JsonContent(ref="#/components/schemas/Student"),
     *      ),
     *      @OA\Response(response=404,description="Not found",
     *          @OA\JsonContent(ref="#/components/schemas/Error"),
     *      ),
     * )
     */
    public function login(Request $request)
    {
        $rules  = [
            'email' => 'required|string|email',
            'password' => 'required',
        ];
        $validator = new ValidatorResponse();
        $validator->check($request, $rules);
        if ($validator->fails) {
            return response()->json($validator->response, 400);
        }
        $model = Student::where('email', $request->email)->first();
        if ($model) {
            if (Hash::check($request->password, $model->password)) {
                $credentials = $request->only('email', 'password');
                $token = auth('student')->attempt($credentials);
                $user = auth('student')->user();
                return response()->json(['user' => $user,
                    'authorization' => [
                        'token' => $token,
                        'type' => 'bearer',
                    ]
                ], 200);
            }else {
                return response()->json(['errors' => ['Password incorrect']],400);

            }
        }else{
            return response()->json(['errors' => ['User not found']],400);
        }

    }
}

