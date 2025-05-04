<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Stan;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class StanController extends Controller
{
    /**
     * Get the authenticated stan's profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        // Check if user is a stan admin
        if (!$user->isAdminStan()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $stan = $user->stan;
        
        return response()->json([
            'data' => $stan
        ]);
    }
    
    /**
     * Update the authenticated stan's profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        // Check if user is a stan admin
        if (!$user->isAdminStan()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'nama_stan' => 'string|max:100',
            'nama_pemilik' => 'string|max:100',
            'telp' => 'string|max:20',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $stan = $user->stan;
        
        if ($request->has('nama_stan')) {
            $stan->nama_stan = $request->nama_stan;
        }
        
        if ($request->has('nama_pemilik')) {
            $stan->nama_pemilik = $request->nama_pemilik;
        }
        
        if ($request->has('telp')) {
            $stan->telp = $request->telp;
        }
        
        $stan->save();
        
        return response()->json([
            'message' => 'Stan profile updated successfully',
            'data' => $stan
        ]);
    }
    
    /**
     * Get monthly income for the stan
     */
    public function incomeByMonth(Request $request, $month = null, $year = null)
    {
        $user = $request->user();
        
        // Check if user is a stan admin
        if (!$user->isAdminStan()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $stan = $user->stan;
        
        // If month or year not provided, use current
        $month = $month ?: Carbon::now()->month;
        $year = $year ?: Carbon::now()->year;
        
        // Get transactions for the month
        $transactions = $stan->transaksiByMonth($month, $year);
        
        // Calculate income
        $income = $stan->incomeByMonth($month, $year);
        
        // Group transactions by day for a detailed breakdown
        $dailyBreakdown = [];
        foreach ($transactions as $transaction) {
            $day = Carbon::parse($transaction->tanggal)->day;
            
            if (!isset($dailyBreakdown[$day])) {
                $dailyBreakdown[$day] = 0;
            }
            
            foreach ($transaction->detailTransaksi as $detail) {
                $dailyBreakdown[$day] += $detail->harga_beli * $detail->qty;
            }
        }
        
        return response()->json([
            'month' => $month,
            'year' => $year,
            'total_income' => $income,
            'transaction_count' => count($transactions),
            'daily_breakdown' => $dailyBreakdown
        ]);
    }
    
    /**
     * Get all customers (siswa)
     */
    public function getCustomers()
    {
        $siswa = Siswa::with('user')->get();
        
        return response()->json([
            'data' => $siswa
        ]);
    }
    
    /**
     * Get a specific customer (siswa)
     */
    public function getCustomer($id)
    {
        $siswa = Siswa::with('user')->findOrFail($id);
        
        return response()->json([
            'data' => $siswa
        ]);
    }
    
    /**
     * Create a new customer (siswa)
     */
    public function createCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:100|unique:users',
            'password' => 'required|string|min:6',
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
            'message' => 'Customer created successfully',
            'data' => $siswa
        ], 201);
    }
    
    /**
     * Update a customer (siswa)
     */
    public function updateCustomer(Request $request, $id)
    {
        $siswa = Siswa::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'nama_siswa' => 'string|max:100',
            'alamat' => 'string',
            'telp' => 'string|max:20',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Update password if provided
        if ($request->has('password')) {
            $user = $siswa->user;
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
            'message' => 'Customer updated successfully',
            'data' => $siswa
        ]);
    }
    
    /**
     * Delete a customer (siswa)
     */
    public function deleteCustomer($id)
    {
        $siswa = Siswa::findOrFail($id);
        $user = $siswa->user;
        
        // Delete photo if exists
        if ($siswa->foto) {
            Storage::delete('public/siswa/' . $siswa->foto);
        }
        
        // Delete user (will cascade delete siswa due to foreign key constraint)
        $user->delete();
        
        return response()->json([
            'message' => 'Customer deleted successfully'
        ]);
    }
}
