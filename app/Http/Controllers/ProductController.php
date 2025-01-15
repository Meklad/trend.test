<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Events\OrderPlaced;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $cacheKey = 'products_' . serialize($request->all());
        
        return Cache::remember($cacheKey, 60, function () use ($request) {
            $query = Product::query();

            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
        
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
        
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            return $query->paginate(10);
        });

        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);
    
        $totalAmount = 0;
        $order = Order::create([
            'user_id' => auth()->id(),
            'total_amount' => 0,
        ]);
    
        foreach ($request->products as $item) {
            $product = Product::find($item['id']);
            $quantity = $item['quantity'];
    
            if ($product->stock < $quantity) {
                return response()->json(['error' => 'Insufficient stock for ' . $product->name], 400);
            }
    
            $order->products()->attach($product->id, [
                'quantity' => $quantity,
                'price' => $product->price,
            ]);
    
            $totalAmount += $product->price * $quantity;
            $product->decrement('stock', $quantity);
        }
    
        $order->update(['total_amount' => $totalAmount]);
    
        event(new OrderPlaced($order));
    
        return response()->json($order, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
