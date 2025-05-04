<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    /**
     * Display a listing of the menu items.
     */
    public function index(Request $request)
    {
        // If stan_id is provided, filter by stan
        if ($request->has('stan_id')) {
            $menu = Menu::with('diskon')
                ->where('id_stan', $request->stan_id)
                ->get();
        } else {
            $menu = Menu::with('diskon')->get();
        }
        
        // Add calculated price with discount
        $menuWithDiscount = $menu->map(function ($item) {
            $diskon = $item->activeDiskon()->first();
            $item->harga_setelah_diskon = $diskon 
                ? $item->harga * (1 - ($diskon->persentase_diskon / 100)) 
                : $item->harga;
            return $item;
        });
        
        return response()->json([
            'data' => $menuWithDiscount
        ]);
    }

    /**
     * Store a newly created menu item.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Check if user is a stan admin
        if (!$user->isAdminStan()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'nama_makanan' => 'required|string|max:100',
            'harga' => 'required|numeric|min:0',
            'jenis' => 'required|in:makanan,minuman',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'deskripsi' => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Get stan id from logged in user
        $stanId = $user->stan->id;
        
        // Process and store photo if uploaded
        $fotoName = null;
        if ($request->hasFile('foto')) {
            $foto = $request->file('foto');
            $fotoName = time() . '.' . $foto->getClientOriginalExtension();
            $foto->storeAs('public/menu', $fotoName);
        }
        
        // Create menu
        $menu = Menu::create([
            'nama_makanan' => $request->nama_makanan,
            'harga' => $request->harga,
            'jenis' => $request->jenis,
            'foto' => $fotoName,
            'deskripsi' => $request->deskripsi,
            'id_stan' => $stanId
        ]);
        
        return response()->json([
            'message' => 'Menu item created successfully',
            'data' => $menu
        ], 201);
    }

    /**
     * Display the specified menu item.
     */
    public function show($id)
    {
        $menu = Menu::with(['stan', 'diskon'])->findOrFail($id);
        
        // Calculate price with discount
        $diskon = $menu->activeDiskon()->first();
        $menu->harga_setelah_diskon = $diskon 
            ? $menu->harga * (1 - ($diskon->persentase_diskon / 100)) 
            : $menu->harga;
        
        return response()->json([
            'data' => $menu
        ]);
    }

    /**
     * Update the specified menu item.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        // Check if user is a stan admin
        if (!$user->isAdminStan()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $menu = Menu::findOrFail($id);
        
        // Check if menu belongs to the user's stan
        if ($menu->id_stan !== $user->stan->id) {
            return response()->json([
                'message' => 'You can only update your own menu items'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'nama_makanan' => 'string|max:100',
            'harga' => 'numeric|min:0',
            'jenis' => 'in:makanan,minuman',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'deskripsi' => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Update menu details
        if ($request->has('nama_makanan')) {
            $menu->nama_makanan = $request->nama_makanan;
        }
        
        if ($request->has('harga')) {
            $menu->harga = $request->harga;
        }
        
        if ($request->has('jenis')) {
            $menu->jenis = $request->jenis;
        }
        
        if ($request->has('deskripsi')) {
            $menu->deskripsi = $request->deskripsi;
        }
        
        // Process and store new photo if uploaded
        if ($request->hasFile('foto')) {
            // Delete old photo if exists
            if ($menu->foto) {
                Storage::delete('public/menu/' . $menu->foto);
            }
            
            $foto = $request->file('foto');
            $fotoName = time() . '.' . $foto->getClientOriginalExtension();
            $foto->storeAs('public/menu', $fotoName);
            $menu->foto = $fotoName;
        }
        
        $menu->save();
        
        return response()->json([
            'message' => 'Menu item updated successfully',
            'data' => $menu
        ]);
    }

    /**
     * Remove the specified menu item.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        // Check if user is a stan admin
        if (!$user->isAdminStan()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $menu = Menu::findOrFail($id);
        
        // Check if menu belongs to the user's stan
        if ($menu->id_stan !== $user->stan->id) {
            return response()->json([
                'message' => 'You can only delete your own menu items'
            ], 403);
        }
        
        // Delete photo if exists
        if ($menu->foto) {
            Storage::delete('public/menu/' . $menu->foto);
        }
        
        $menu->delete();
        
        return response()->json([
            'message' => 'Menu item deleted successfully'
        ]);
    }
}
