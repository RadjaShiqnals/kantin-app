<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Siswa;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SiswaController extends Controller
{
    /**
     * Get the authenticated siswa's profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        // Check if user is a siswa
        if (!$user->isSiswa()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $siswa = $user->siswa;
        
        return response()->json([
            'data' => $siswa
        ]);
    }
    
    /**
     * Update the authenticated siswa's profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        // Check if user is a siswa
        if (!$user->isSiswa()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'nama_siswa' => 'string|max:100',
            'alamat' => 'string',
            'telp' => 'string|max:20',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'current_password' => 'required_with:password',
            'password' => 'nullable|string|min:6|confirmed',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $siswa = $user->siswa;
        
        // Update user password if provided
        if ($request->has('password')) {
            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect'
                ], 422);
            }
            
            $user->password = Hash::make($request->password);
            $user->save();
        }
        
        // Update siswa details
        if ($request->has('nama_siswa')) {
            $siswa->nama_siswa = $request->nama_siswa;
        }
        
        if ($request->has('alamat')) {
            $siswa->alamat = $request->alamat;
        }
        
        if ($request->has('telp')) {
            $siswa->telp = $request->telp;
        }
        
        // Process and store new photo if uploaded
        if ($request->hasFile('foto')) {
            // Delete old photo if exists
            if ($siswa->foto) {
                Storage::delete('public/siswa/' . $siswa->foto);
            }
            
            $foto = $request->file('foto');
            $fotoName = time() . '.' . $foto->getClientOriginalExtension();
            $foto->storeAs('public/siswa', $fotoName);
            $siswa->foto = $fotoName;
        }
        
        $siswa->save();
        
        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $siswa
        ]);
    }
}
