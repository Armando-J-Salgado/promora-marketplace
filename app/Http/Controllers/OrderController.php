<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Promocode;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\PromocodeEngine\PromocodeEngine;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        private PromocodeEngine $promocodeEngine,
    ) {}

    public function validate(Order $order, Promocode $promocode): JsonResponse
    {
        $isValid = $this->promocodeEngine->validateCode($order, $promocode);

        return response()->json([
            'valid' => $isValid,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }
}