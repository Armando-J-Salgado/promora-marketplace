<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Promocode;
use App\PromocodeEngine\PromocodeEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function __construct(
        private PromocodeEngine $promocodeEngine,
    ) {}

    public function validate(Order $order, Promocode $promocode): JsonResponse
    {
        try {
            $isValid = $this->promocodeEngine->validateCode($order, $promocode);

            return response()->json([
                'valid' => $isValid,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'valid' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function index(): JsonResponse
    {
        $orders = Order::with(['customer', 'services'])->get();

        return response()->json($orders);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'services' => 'required|array|min:1',
            'services.*.id' => 'required|exists:services,id',
            'services.*.quantity' => 'required|integer|min:1',
        ]);

        $order = Order::create([
            'customer_id' => $validated['customer_id'],
            'status' => 'pending',
            'subtotal' => 0,
            'total' => 0,
        ]);

        foreach ($validated['services'] as $service) {
            $order->services()->attach($service['id'], ['quantity' => $service['quantity']]);
        }

        $order->getSubtotal();
        $order->load('services', 'customer');

        return response()->json($order, 201);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['customer', 'services']);

        return response()->json($order);
    }
}
