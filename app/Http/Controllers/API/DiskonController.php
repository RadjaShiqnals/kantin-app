<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Diskon;
use App\Models\Menu;
use App\Models\MenuDiskon;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class DiskonController extends Controller
{
    /**
     * Display a listing of the discounts.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // If user is stan admin, only show their discounts
        if ($user->isAdminStan()) {
            $diskon = Diskon::where('id_stan', $user->stan->id)
                ->with('menuDiskon.menu')
                ->get();
        } else {
            // If stan_id is provided, filter by stan
            if ($request->has('stan_id')) {
                $diskon = Diskon::where('id_stan', $request->stan_id)
                    ->with('menuDiskon.menu')
                    ->get();
            } else {
                $diskon = Diskon::with('menuDiskon.menu')->get();
            }
        }
        
        return response()->json([
            'data' => $diskon
        ]);
    }
    
    /**
     * Get active discounts.
     */
    public function getActiveDiskon(Request $request)
    {
        $now = Carbon::now();
        
        // If stan_id is provided, filter by stan
        if ($request->has('stan_id')) {
            $diskon = Diskon::where('id_stan', $request->stan_id)
                ->where('tanggal_awal', '<=', $now)
                ->where('tanggal_akhir', '>=', $now)
                ->with('menuDiskon.menu')
                ->get();
        } else {
            $diskon = Diskon::where('tanggal_awal', '<=', $now)
                ->where('tanggal_akhir', '>=', $now)
                ->with('menuDiskon.menu')
                ->get();
        }
        
        return response()->json([
            'data' => $diskon
        ]);
    }

    /**
     * Store a newly created discount.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Check if user is a stan admin
        if (!$user->isAdminStan()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'nama_diskon' => 'required|string|max:100',
            'persentase_diskon' => 'required|numeric|min:0|max:100',
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
            'menu_ids' => 'nullable|array',
            'menu_ids.*' => 'exists:menu,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Get stan id from logged in user
        $stanId = $user->stan->id;
        
        // Create diskon
        $diskon = Diskon::create([
            'nama_diskon' => $request->nama_diskon,
            'persentase_diskon' => $request->persentase_diskon,
            'tanggal_awal' => $request->tanggal_awal,
            'tanggal_akhir' => $request->tanggal_akhir,
            'id_stan' => $stanId
        ]);
        
        // Attach menu items if provided
        if ($request->has('menu_ids') && is_array($request->menu_ids)) {
            foreach ($request->menu_ids as $menuId) {
                // Verify menu belongs to this stan
                $menu = Menu::find($menuId);
                if ($menu && $menu->id_stan === $stanId) {
                    MenuDiskon::create([
                        'id_menu' => $menuId,
                        'id_diskon' => $diskon->id
                    ]);
                }
            }
        }
        
        return response()->json([
            'message' => 'Discount created successfully',
            'data' => $diskon->load('menuDiskon.menu')
        ], 201);
    }

    /**
     * Display the specified discount.
     */
    public function show($id)
    {
        $diskon = Diskon::with('menuDiskon.menu')->findOrFail($id);
        
        return response()->json([
            'data' => $diskon
        ]);
    }

    /**
     * Update the specified discount.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        // Check if user is a stan admin
        if (!$user->isAdminStan()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $diskon = Diskon::findOrFail($id);
        
        // Check if discount belongs to the user's stan
        if ($diskon->id_stan !== $user->stan->id) {
            return response()->json([
                'message' => 'You can only update your own discounts'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'nama_diskon' => 'string|max:100',
            'persentase_diskon' => 'numeric|min:0|max:100',
            'tanggal_awal' => 'date',
            'tanggal_akhir' => 'date|after_or_equal:tanggal_awal'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Update discount details
        if ($request->has('nama_diskon')) {
            $diskon->nama_diskon = $request->nama_diskon;
        }
        
        if ($request->has('persentase_diskon')) {
            $diskon->persentase_diskon = $request->persentase_diskon;
        }
        
        if ($request->has('tanggal_awal')) {
            $diskon->tanggal_awal = $request->tanggal_awal;
        }
        
        if ($request->has('tanggal_akhir')) {
            $diskon->tanggal_akhir = $request->tanggal_akhir;
        }
        
        $diskon->save();
        
        return response()->json([
            'message' => 'Discount updated successfully',
            'data' => $diskon->load('menuDiskon.menu')
        ]);
    }

    /**
     * Remove the specified discount.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        // Check if user is a stan admin
        if (!$user->isAdminStan()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $diskon = Diskon::findOrFail($id);
        
        // Check if discount belongs to the user's stan
        if ($diskon->id_stan !== $user->stan->id) {
            return response()->json([
                'message' => 'You can only delete your own discounts'
            ], 403);
        }
        
        // Delete will cascade to menu_diskon due to foreign key constraint
        $diskon->delete();
        
        return response()->json([
            'message' => 'Discount deleted successfully'
        ]);
    }
    
    /**
     * Attach a menu to a discount.
     */
    public function attachMenu(Request $request, $diskonId, $menuId)
    {
        $user = $request->user();
        
        // Check if user is a stan admin
        if (!$user->isAdminStan()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $diskon = Diskon::findOrFail($diskonId);
        $menu = Menu::findOrFail($menuId);
        
        // Check if both belong to the user's stan
        if ($diskon->id_stan !== $user->stan->id || $menu->id_stan !== $user->stan->id) {
            return response()->json([
                'message' => 'You can only manage your own menus and discounts'
            ], 403);
        }
        
        // Check if already attached
        $exists = MenuDiskon::where('id_diskon', $diskonId)
            ->where('id_menu', $menuId)
            ->exists();
            
        if ($exists) {
            return response()->json([
                'message' => 'Menu is already attached to this discount'
            ], 422);
        }
        
        // Create relationship
        MenuDiskon::create([
            'id_menu' => $menuId,
            'id_diskon' => $diskonId
        ]);
        
        return response()->json([
            'message' => 'Menu attached to discount successfully'
        ]);
    }
    
    /**
     * Detach a menu from a discount.
     */
    public function detachMenu(Request $request, $diskonId, $menuId)
    {
        $user = $request->user();
        
        // Check if user is a stan admin
        if (!$user->isAdminStan()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $diskon = Diskon::findOrFail($diskonId);
        
        // Check if discount belongs to the user's stan
        if ($diskon->id_stan !== $user->stan->id) {
            return response()->json([
                'message' => 'You can only manage your own discounts'
            ], 403);
        }
        
        // Delete relationship
        $deleted = MenuDiskon::where('id_diskon', $diskonId)
            ->where('id_menu', $menuId)
            ->delete();
            
        if (!$deleted) {
            return response()->json([
                'message' => 'Menu is not attached to this discount'
            ], 422);
        }
        
        return response()->json([
            'message' => 'Menu detached from discount successfully'
        ]);
    }
}
