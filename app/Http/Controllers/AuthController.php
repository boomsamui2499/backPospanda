<?php

namespace App\Http\Controllers;

use App\Models\Order_lines;
use App\Models\Order_payment;
use App\Models\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use File;
use ZipArchive;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function login(Request $request)
    {
        // var_dump( 'ssss' );
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
        }
        return $this->createNewToken($token);
    }
    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'last_name' => 'required|string|between:2,100',
            'phone_number' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'permission' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'messageError' => $validator->errors()->toJson()
            ], 400);
        }
        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));
        return response()->json([
            'success' => true,
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

    /**
     * Log the user out ( Invalidate the token ).
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function userProfile()
    {
        $data = auth()->user();
        return response()->json([
            'user' => $data,
            'package_type' =>  \config('getURL.package_type'),
            'is_demo' => \config('getURL.is_demo')
        ]);
    }
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        $data = auth()->user();
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => 60 * 60 * 24,
            // 'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => $data,
            'package_type' =>  \config('getURL.package_type'),
            'exp_date' =>  \config('getURL.exp_date'),

            'is_demo' => \config('getURL.is_demo'),
            'url_fix' =>  \config('getURL.url')


        ]);
    }

    public function clearData(Request $request)
    {
        $data = auth()->user();
        if ($data->permission == 'owner') {
            $pass = User::where('id', $data->id)->first();
            if (Hash::check($request->input('password'),  $pass->password)) {
                DB::statement('SET foreign_key_checks=0');
                Order_payment::truncate();
                Order_lines::truncate();
                Orders::truncate();
                DB::statement('SET foreign_key_checks=1');

                return response()->json([
                    'success' => true,
                    'message' => 'Clear Database'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'wrong password',
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
    }

    public function dumpData(Request $request)
    {
        $data = auth()->user();
        if ($data->permission == 'owner') {
            $pass = User::where('id', $data->id)->first();
            if (Hash::check($request->input('password'),  $pass->password)) {

                $path = public_path('../storage/app/public/backup/DB.sql');
                // $d = 'mysqldump -u ' . env( 'DB_USERNAME' ) . ' -h'. env( 'DB_HOST' ) .'  ' . env( 'DB_DATABASE' ) ." --result-file={$path} 2>&1";
                exec('mysqldump -u ' . env('DB_USERNAME') . '     -h' . env('DB_HOST') . '  ' . env('DB_DATABASE') . " --result-file={$path} 2>&1");

                $zip_file = 'storage.zip';
                $zip = new \ZipArchive();
                $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

                $zipPath = storage_path();
                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($zipPath));
                foreach ($files as $name => $file) {
                    // We're skipping all subfolders
                    if (!$file->isDir()) {
                        $filePath     = $file->getRealPath();

                        // extracting filename with substr/strlen
                        $relativePath = 'storage/' . substr($filePath, strlen($zipPath) + 1);

                        $zip->addFile($filePath, $relativePath);
                    }
                }
                return response()->json([
                    "success" => true,
                    "message" => "Dump database success",
                ]);
            } else {
                return response()->json([
                    "success" => false,
                    "message" => "wrong password",
                ]);
            }
        } else {
            return response()->json([
                "success" => false,
                'message' => 'Unauthorized'
            ], 401);
        }
    }
    public function download(Request $request)
    {
        // $zip_file = Carbon::now()->timestamp;

        $zip_file = 'storage.zip';
        $zip = new \ZipArchive();
        $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $path = storage_path();
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        foreach ($files as $name => $file) {
            // We're skipping all subfolders
            if (!$file->isDir()) {
                $filePath     = $file->getRealPath();

                // extracting filename with substr/strlen
                $relativePath = 'storage/' . substr($filePath, strlen($path) + 1);

                $zip->addFile($filePath, $relativePath);
            }
        }
        return response()->json([
            'success' => true,
            'message' => 'Zip file success',
        ]);
    }
}
