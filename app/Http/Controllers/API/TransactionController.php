<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Menu;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionController extends Controller
{
    /**
     * Get transactions for the authenticated stan.
     */
    public function getStanTransaksiByMonth(Request $request, $month = null, $year = null)
    {
        $user = $request->user();
        
        // Check if user is a stan admin
        if (!$user->isAdminStan()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $stanId = $user->stan->id;
        
        // If month or year not provided, use current
        $month = $month ?: Carbon::now()->month;
        $year = $year ?: Carbon::now()->year;
        
        $transactions = Transaksi::where('id_stan', $stanId)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->with(['siswa', 'detailTransaksi.menu'])
            ->orderBy('tanggal', 'desc')
            ->get();
        
        return response()->json([
            'data' => $transactions
        ]);
    }
    
    /**
     * Get transactions for the authenticated siswa.
     */
    public function getSiswaTransaksiByMonth(Request $request, $month = null, $year = null)
    {
        $user = $request->user();
        
        // Check if user is a siswa
        if (!$user->isSiswa()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $siswaId = $user->siswa->id;
        
        // If month or year not provided, use current
        $month = $month ?: Carbon::now()->month;
        $year = $year ?: Carbon::now()->year;
        
        $transactions = Transaksi::where('id_siswa', $siswaId)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->with(['stan', 'detailTransaksi.menu'])
            ->orderBy('tanggal', 'desc')
            ->get();
        
        return response()->json([
            'data' => $transactions
        ]);
    }
    
    /**
     * Show transaction details.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $transaction = Transaksi::with(['siswa', 'stan', 'detailTransaksi.menu'])->findOrFail($id);
        
        // If siswa, check if transaction belongs to them
        if ($user->isSiswa() && $transaction->id_siswa !== $user->siswa->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // If stan admin, check if transaction belongs to their stan
        if ($user->isAdminStan() && $transaction->id_stan !== $user->stan->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json([
            'data' => $transaction
        ]);
    }
    
    /**
     * Create a new transaction.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Check if user is a siswa
        if (!$user->isSiswa()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'id_stan' => 'required|exists:stan,id',
            'items' => 'required|array|min:1',
            'items.*.id_menu' => 'required|exists:menu,id',
            'items.*.qty' => 'required|integer|min:1'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Create transaction
            $transaction = Transaksi::create([
                'tanggal' => Carbon::now(),
                'id_stan' => $request->id_stan,
                'id_siswa' => $user->siswa->id,
                'status' => 'belum dikonfirm'
            ]);
            
            // Process items
            foreach ($request->items as $item) {
                $menu = Menu::findOrFail($item['id_menu']);
                
                // Check if menu belongs to the specified stan
                if ($menu->id_stan != $request->id_stan) {
                    throw new \Exception('Menu does not belong to the specified stan');
                }
                
                // Calculate price with possible discount
                $diskon = $menu->activeDiskon()->first();
                $hargaBeli = $diskon 
                    ? $menu->harga * (1 - ($diskon->persentase_diskon / 100)) 
                    : $menu->harga;
                
                // Create detail transaction
                DetailTransaksi::create([
                    'id_transaksi' => $transaction->id,
                    'id_menu' => $item['id_menu'],
                    'qty' => $item['qty'],
                    'harga_beli' => $hargaBeli
                ]);
            }
            
            // Commit transaction
            DB::commit();
            
            return response()->json([
                'message' => 'Order placed successfully',
                'data' => $transaction->load(['stan', 'detailTransaksi.menu'])
            ], 201);
            
        } catch (\Exception $e) {
            // Rollback transaction
            DB::rollback();
            
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update transaction status.
     */
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        
        // Check if user is a stan admin
        if (!$user->isAdminStan()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:belum dikonfirm,dimasak,diantar,sampai'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $transaction = Transaksi::findOrFail($id);
        
        // Check if transaction belongs to the user's stan
        if ($transaction->id_stan !== $user->stan->id) {
            return response()->json([
                'message' => 'You can only update your stan\'s transactions'
            ], 403);
        }
        
        $transaction->status = $request->status;
        $transaction->save();
        
        return response()->json([
            'message' => 'Transaction status updated',
            'data' => $transaction
        ]);
    }
    
    /**
     * Check transaction status.
     */
    public function checkStatus(Request $request, $id)
    {
        $user = $request->user();
        $transaction = Transaksi::findOrFail($id);
        
        // If siswa, check if transaction belongs to them
        if ($user->isSiswa() && $transaction->id_siswa !== $user->siswa->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // If stan admin, check if transaction belongs to their stan
        if ($user->isAdminStan() && $transaction->id_stan !== $user->stan->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json([
            'status' => $transaction->status
        ]);
    }
    
    /**
     * Generate and print transaction receipt.
     */
    public function printNota(Request $request, $id)
    {
        $user = $request->user();
        $transaction = Transaksi::with(['siswa', 'stan', 'detailTransaksi.menu'])->findOrFail($id);
        
        // If siswa, check if transaction belongs to them
        if ($user->isSiswa() && $transaction->id_siswa !== $user->siswa->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // If stan admin, check if transaction belongs to their stan
        if ($user->isAdminStan() && $transaction->id_stan !== $user->stan->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Calculate total
        $total = 0;
        foreach ($transaction->detailTransaksi as $item) {
            $total += $item->harga_beli * $item->qty;
        }
        
        // Generate PDF
        $pdf = PDF::loadView('receipts.transaction', [
            'transaction' => $transaction,
            'total' => $total
        ]);
        
        // If it's an API request, return base64 encoded PDF
        if ($request->wantsJson()) {
            $output = $pdf->output();
            $pdfBase64 = base64_encode($output);
            
            return response()->json([
                'pdf_base64' => $pdfBase64
            ]);
        }
        
        // Otherwise, return PDF for download
        return $pdf->download('nota-' . $transaction->id . '.pdf');
    }
}
