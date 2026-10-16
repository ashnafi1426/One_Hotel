<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\KitchenOrderResource;
use App\Models\Order;
use App\Services\KitchenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class KitchenController extends Controller
{
    public function __construct(
        protected KitchenService $kitchenService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $orders = $this->kitchenService->getKitchenOrders(auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Kitchen orders retrieved successfully.',
                'data' => [
                    'pending' => KitchenOrderResource::collection($orders['pending']),
                    'preparing' => KitchenOrderResource::collection($orders['preparing']),
                    'ready' => KitchenOrderResource::collection($orders['ready']),
                    'served' => KitchenOrderResource::collection($orders['served']),
                ]
            ]);
        } catch (Throwable $e) {
            Log::error('[KITCHEN] Failed to load orders: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load kitchen orders.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function start(Request $request, Order $order): JsonResponse
    {
        try {
            $updatedOrder = $this->kitchenService->startPreparing($order, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Order started preparing successfully.',
                'data' => new KitchenOrderResource($updatedOrder)
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function ready(Request $request, Order $order): JsonResponse
    {
        try {
            $updatedOrder = $this->kitchenService->markReady($order);

            return response()->json([
                'success' => true,
                'message' => 'Order marked as ready and waiter notified.',
                'data' => new KitchenOrderResource($updatedOrder)
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function complete(Request $request, Order $order): JsonResponse
    {
        try {
            $updatedOrder = $this->kitchenService->markServed($order);

            return response()->json([
                'success' => true,
                'message' => 'Order completed and marked as served.',
                'data' => new KitchenOrderResource($updatedOrder)
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function statistics(Request $request): JsonResponse
    {
        try {
            $statistics = $this->kitchenService->statistics(auth()->user());

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
