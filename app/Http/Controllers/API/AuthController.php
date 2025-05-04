<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Siswa;
use App\Models\Stan;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     */
    public function __construct()
    {

    }

    /**
     * Register a new student user
     */
    public function registerSiswa(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:100|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'nama_siswa' => 'required|string|max:100',
            'alamat' => 'required|string',
            'telp' => 'required|string|max:20',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create user
        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => 'siswa',
        ]);

        // Process and store photo if uploaded
        $fotoName = null;
        if ($request->hasFile('foto')) {
            $foto = $request->file('foto');
            $fotoName = time() . '.' . $foto->getClientOriginalExtension();
            $foto->storeAs('public/siswa', $fotoName);
        }

        // Create siswa profile
        $siswa = Siswa::create([
            'nama_siswa' => $request->nama_siswa,
            'alamat' => $request->alamat,
            'telp' => $request->telp,
            'id_user' => $user->id,
            'foto' => $fotoName,
        ]);

        return response()->json([
            'message' => 'Siswa registered successfully',
            'user' => $user,
            'siswa' => $siswa
        ], 201);
    }

    /**
     * Register a new stan admin user
     */
    public function registerStan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:100|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'nama_stan' => 'required|string|max:100',
            'nama_pemilik' => 'required|string|max:100',
            'telp' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create user
        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => 'admin_stan',
        ]);

        // Create stan profile
        $stan = Stan::create([
            'nama_stan' => $request->nama_stan,
            'nama_pemilik' => $request->nama_pemilik,
            'telp' => $request->telp,
            'id_user' => $user->id,
        ]);

        return response()->json([
            'message' => 'Stan admin registered successfully',
            'user' => $user,
            'stan' => $stan
        ], 201);
    }

    /**
     * Login user with JWT
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Attempt to authenticate and get JWT token
        $credentials = $request->only('username', 'password');
        
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Invalid login details'
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Could not create token'
            ], 500);
        }

        $user = Auth::user();
        // Get user profile based on role
        $profile = $user->profile();

        return $this->respondWithToken($token, $user, $profile);
    }

    /**
     * Get authenticated user info
     */
    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }
            $profile = $user->profile();
            
            return response()->json([
                'user' => $user,
                'profile' => $profile
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    /**
     * Logout user (invalidate token)
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'message' => 'Successfully logged out'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Failed to logout'
            ], 500);
        }
    }

    /**
     * Refresh JWT token
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::getToken();
            $newToken = JWTAuth::refresh($token);
            $user = JWTAuth::setToken($newToken)->toUser();
            $profile = $user->profile();
            
            return $this->respondWithToken($newToken, $user, $profile);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Could not refresh token'
            ], 500);
        }
    }
    
    /**
     * Get the token array structure.
     */
    protected function respondWithToken($token, $user, $profile)
    {
        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'profile' => $profile,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60
        ]);
    }
}
