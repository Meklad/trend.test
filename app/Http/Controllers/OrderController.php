<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Auth::user()->orders()->latest()->paginate(10);
        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'total_amount' => 'required|numeric',
            'shipping_address' => 'required|string',
            'status' => 'required|in:pending,processing,completed,cancelled'
        ]);

        $order = Auth::user()->orders()->create($validated);
        
        return response()->json($order, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order = Auth::user()->orders()->findOrFail($id);
        return response()->json($order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order = Auth::user()->orders()->findOrFail($id);

        $validated = $request->validate([
            'items' => 'sometimes|array',
            'total_amount' => 'sometimes|numeric',
            'shipping_address' => 'sometimes|string',
            'status' => 'sometimes|in:pending,processing,completed,cancelled'
        ]);

        $order->update($validated);
        
        return response()->json($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Auth::user()->orders()->findOrFail($id);
        $order->delete();
        
        return response()->json(null, 204);
    }
}
