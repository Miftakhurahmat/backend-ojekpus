<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\History;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HistoryController extends Controller
{
    public function index()
    {
        $histories = Order::where('customer_id', auth()->user()->id)->where('status', 2)->latest()->get();
        $response['message'] = 'List History';
        $response['data'] = $histories;


        return response()->json($response, 200);
    }
    public function waiting()
    {
        $histories = Order::where('customer_id', auth()->user()->id)->where('status', 0)->latest()->get();
        $response['message'] = 'List Order Menunggu';
        $response['data'] = $histories;


        return response()->json($response, 200);
    }
    public function process()
    {
        $histories = Order::where('customer_id', auth()->user()->id)->where('status', 1)->latest()->get();
        $response['message'] = 'List Order Diantar';
        $response['data'] = $histories;


        return response()->json($response, 200);
    }
    
    public function processDriver()
    {
        $histories = Order::where('driver_id', auth()->user()->id)->where('status', 1)->latest()->get();
        $response['message'] = 'List Order Diantar';
        $response['data'] = $histories;


        return response()->json($response, 200);
    }
    
    public function historyDriver()
    {
        $histories = Order::where('driver_id', auth()->user()->id)->where('status', 2)->latest()->get();
        $response['message'] = 'List History';
        $response['data'] = $histories;


        return response()->json($response, 200);
    }
    
    
}
