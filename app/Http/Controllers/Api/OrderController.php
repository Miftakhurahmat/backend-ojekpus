<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\User;
use App\Models\History;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function index()
    {
        $currentTimestamp = Carbon::now()->subMinutes(1);
        $currentUser = auth()->user();
        if ($currentUser->status == 0) {
            // Ambil pesanan dengan status 0 (belum diambil) dan driver yang last_seen lebih dari 1 menit yang lalu atau belum mengambil pesanan sebelumnya
            $orders = Order::with('customer')
                ->where('status', 0)
                ->orderBy('created_at') // Urutkan pesanan berdasarkan waktu dibuat
                ->get();
        
            // Ambil semua pengemudi yang memenuhi kriteria
            $drivers = User::where('role', 'driver')
                ->where(function ($query) use ($currentTimestamp) {
                    $query->where('last_seen', '>=', $currentTimestamp)
                        ->orWhereNull('last_order_taken_at');
                })
                ->orderBy('last_seen') // Urutkan pengemudi berdasarkan last_seen
                ->get();
        
            // Iterasi pesanan dan assign ke pengemudi yang memenuhi kriteria
            foreach ($orders as $order) {
                foreach ($drivers as $driver) {
                    // Jika pengemudi belum memiliki pesanan atau memiliki pesanan lebih dari 1 menit yang lalu
                    if (!$driver->last_order_taken_at || $driver->last_order_taken_at->lte(Carbon::now()->subMinutes(1))) {
                        $order->update(['driver_id' => $driver->id]);
                        // Update last_order_taken_at pengemudi
                        $driver->update(['last_order_taken_at' => Carbon::now()]);
                        // Hapus pengemudi dari daftar untuk pesanan ini
                        $drivers = $drivers->except($driver->id);
                        break; // Langsung pindah ke pesanan berikutnya
                    }
                }
            }
        
            // Ambil kembali pesanan dengan driver yang ditugaskan
        
            $orders = Order::with('customer', 'driver')
                ->where('status', 0)
                ->get();
        }
    
        $response['message'] = 'List Pesanan';
        $response['data'] = $orders ?? 'Data Kosong';
    
        return response()->json($response, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titik_awal' => 'required|string',
            'titik_antar' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            
            User::where('id', Auth::user()->id)->update([
                    'last_seen' => now(),
            ]);
        
            $order = Order::create([
                'customer_id' => auth()->user()->id,
                'titik_awal' => $request->titik_awal,
                'titik_antar' => $request->titik_antar,
                'status' => 0,
                'timeout' => 2,
            ]);

            $response['message'] = 'Pesanan Berhasil Dibuat';
            $response['data'] = $order;

            return response()->json($response, 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => "Failed " . $e->errorInfo
            ]);
        }
    }

    public function show(Order $order)
    {
        $response['message'] = 'Detail Pesanan ' . $order->id;
        $response['data'] = $order;

        return response()->json($response, 200);
    }

    public function update(Request $request, Order $order)
    {
        $user = auth()->user();
        $loggedInUser = User::find($user->id);

        $currentTimestamp = Carbon::now()->subMinutes(1);
        
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            if ($loggedInUser->last_seen >= $currentTimestamp) {
                $response['message'] = 'Anda Harus Menunggu Beberapa Menit Untuk Menerima Orderan Lagi';
                return response()->json($response, 422);
            }
            
            if ($order->status == 1) {
                $response['message'] = 'Order Telah Diterima Oleh Driver Lain';
                return response()->json($response, 422);
            }
            
            User::where('id', $request->driver_id)->update([
                'last_seen' => now(),
                'status' => 1
            ]);
        
            $order->update([
                'driver_id' => $request->driver_id,
                'status' => 1
            ]);
            $response['message'] = 'Status Pesanan Berhasil Diubah';
            $response['data'] = $order;

            return response()->json($response, 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => "Failed " . $e->errorInfo
            ]);
        }
    }
    public function done(Request $request, Order $order)
    {
        try {
            User::where('id', $order->driver_id)->update([
                    'last_seen' => now(),
                    'status' => 0
            ]);
        
            $order->update([
                'status' => 2
            ]);

            History::create([
                'order_id' => $order->id,
            ]);

            $response['message'] = 'Pesanan Selesai';
            $response['data'] = $order;

            return response()->json($response, 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => "Failed " . $e->errorInfo
            ]);
        }
    }
    
    public function listDriver()
    {
        $currentTimestamp = Carbon::now()->subMinutes(1);
        $drivers = User::where('role', 'driver')
            ->where('last_seen', '>=', $currentTimestamp)
            ->where('status', 0)
            ->orderBy('last_seen', 'asc')
            ->get();
            // ->where('last_seen', '>=', $currentTimestamp)
    
        $response['message'] = 'List Driver';
        $response['data'] = $drivers;
    
        return response()->json($response, 200);
    }
    
    public function updateLastOrderTakenAt(Request $request, $id)
    {
        try {
            $driver = User::findOrFail($id);
            $driver->update(['last_order_taken_at' => now()]);
            return response()->json(['message' => 'Update Berhasil'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Terjadi Kesalahan'], 500);
        }
    }
}
