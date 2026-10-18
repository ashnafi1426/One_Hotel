<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
| Single-Hotel Architecture: Channel authorization verifies roles and user permissions.
|
*/

/**
 * Customer Order Status Channel
 * 
 * Channel: orders.{orderId} or orders.{hotelId}.{orderId} (for backward compatibility)
 * 
 * Authorization:
 * - Order must exist
 * - User must own the order OR be authenticated staff member
 */
Broadcast::channel('orders.{hotelId}.{orderId}', function ($user, string $hotelId, string $orderId) {
    return authorizeOrderChannel($user, $orderId);
});

Broadcast::channel('orders.{orderId}', function ($user, string $orderId) {
    return authorizeOrderChannel($user, $orderId);
});

/**
 * Order Status Update Channels (Real-time WebSocket broadcasts)
 */
Broadcast::channel('orders.update.{orderId}', function ($user, string $orderId) {
    return authorizeOrderChannel($user, $orderId);
});

Broadcast::channel('kitchen.orders.update', function ($user) {
    return authorizeKitchenChannel($user);
});

Broadcast::channel('waiter.orders.update', function ($user) {
    return authorizeWaiterChannel($user);
});

if (!function_exists('authorizeOrderChannel')) {
    function authorizeOrderChannel($user, string $orderId): bool {
        try {
            $order = Order::withoutGlobalScopes()->find($orderId);
            
            if (!$order) {
                Log::warning("[WebSocket Auth] Order not found", ['order_id' => $orderId]);
                return false;
            }
            
            // If guest tracking their order
            if (!$user) {
                return true;
            }
            
            // If user owns this order
            if ($order->guest_id && $order->guest_id === $user->id) {
                return true;
            }
            
            // Any authenticated staff member has access
            if ($user instanceof User) {
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("[WebSocket Auth] Channel authorization error: " . $e->getMessage(), [
                'order_id' => $orderId,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}

/**
 * Hotel Kitchen Channel
 */
Broadcast::channel('hotel.{hotelId}.kitchen', function ($user) {
    return authorizeKitchenChannel($user);
});

Broadcast::channel('hotel.kitchen', function ($user) {
    return authorizeKitchenChannel($user);
});

if (!function_exists('authorizeKitchenChannel')) {
    function authorizeKitchenChannel($user): bool {
        if (!$user) {
            return false;
        }
        
        try {
            $userRole = strtolower($user->role ?? '');
            $kitchenRoles = ['chef', 'cook', 'kitchen_staff', 'manager', 'admin', 'administrator', 'super_admin'];
            
            if (in_array($userRole, $kitchenRoles)) {
                return true;
            }
            
            if (method_exists($user, 'hasRole')) {
                foreach ($kitchenRoles as $role) {
                    if ($user->hasRole($role)) {
                        return true;
                    }
                }
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("[WebSocket Auth] Kitchen channel error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Hotel Waiters Channel
 */
Broadcast::channel('hotel.{hotelId}.waiters', function ($user) {
    return authorizeWaiterChannel($user);
});

Broadcast::channel('hotel.waiters', function ($user) {
    return authorizeWaiterChannel($user);
});

if (!function_exists('authorizeWaiterChannel')) {
    function authorizeWaiterChannel($user): bool {
        if (!$user) {
            return false;
        }
        
        try {
            $userRole = strtolower($user->role ?? '');
            $waiterRoles = ['waiter', 'manager', 'admin', 'administrator', 'super_admin'];
            
            if (in_array($userRole, $waiterRoles)) {
                return true;
            }
            
            if (method_exists($user, 'hasRole')) {
                foreach ($waiterRoles as $role) {
                    if ($user->hasRole($role)) {
                        return true;
                    }
                }
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("[WebSocket Auth] Waiters channel error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Hotel Orders Channel (Dashboard)
 */
Broadcast::channel('hotel.{hotelId}.orders', function ($user) {
    return $user instanceof User;
});

Broadcast::channel('hotel.orders', function ($user) {
    return $user instanceof User;
});

/**
 * Hotel Payments Channel (Cashier & Accounting)
 */
Broadcast::channel('payments.{hotelId}', function ($user) {
    return authorizePaymentsChannel($user);
});

Broadcast::channel('payments', function ($user) {
    return authorizePaymentsChannel($user);
});

if (!function_exists('authorizePaymentsChannel')) {
    function authorizePaymentsChannel($user): bool {
        if (!$user) {
            return false;
        }
        
        $paymentRoles = ['cashier', 'receptionist', 'manager', 'admin', 'administrator', 'accountant', 'super_admin'];
        $userRole = strtolower($user->role ?? '');
        
        if (in_array($userRole, $paymentRoles)) {
            return true;
        }
        
        if (method_exists($user, 'hasRole')) {
            foreach ($paymentRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }
        
        return false;
    }
}

/**
 * Waiter Notification Channel
 *
 * Channel: waiter.{waiterId}
 */
Broadcast::channel('waiter.{waiterId}', function ($user, $waiterId) {
    if (!$user) {
        return false;
    }
    try {
        $waiter = \App\Models\Waiter::find($waiterId);
        if (!$waiter) {
            return false;
        }

        // Check if user is the assigned waiter
        if ((string) $waiter->user_id === (string) $user->id) {
            return true;
        }

        // Allow manager/admin of the hotel
        return in_array(strtolower($user->role ?? ''), ['admin', 'manager', 'administrator', 'super_admin']);
    } catch (\Exception $e) {
        Log::error("[WebSocket Auth] Waiter channel error: " . $e->getMessage());
        return false;
    }
});

/**
 * Delivery Task Channel
 *
 * Channel: delivery.{deliveryId}
 */
Broadcast::channel('delivery.{deliveryId}', function ($user, $deliveryId) {
    if (!$user) {
        return false;
    }
    try {
        $task = \App\Models\DeliveryTask::withoutGlobalScopes()->find($deliveryId);
        if (!$task) {
            return false;
        }

        return $user instanceof User;
    } catch (\Exception $e) {
        Log::error("[WebSocket Auth] Delivery channel error: " . $e->getMessage());
        return false;
    }
});

/**
 * Manager Notification Channel
 *
 * Channel: manager
 */
Broadcast::channel('manager', function ($user) {
    if (!$user) {
        return false;
    }
    return in_array(strtolower($user->role ?? ''), ['admin', 'manager', 'administrator', 'super_admin'])
        || (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin());
});

/**
 * Order Status Update Channel
 *
 * Channel: orders.update.{orderId}
 * Used for broadcasting order status changes in real-time
 */
Broadcast::channel('orders.update.{orderId}', function ($user, string $orderId) {
    return authorizeOrderChannel($user, $orderId);
});

/**
 * Kitchen Orders Update Channel
 *
 * Channel: kitchen.orders.update
 * Broadcasts all order changes to kitchen staff
 */
Broadcast::channel('kitchen.orders.update', function ($user) {
    return authorizeKitchenChannel($user);
});

/**
 * Waiter Orders Update Channel
 *
 * Channel: waiter.orders.update
 * Broadcasts all order changes to waiters
 */
Broadcast::channel('waiter.orders.update', function ($user) {
    return authorizeWaiterChannel($user);
});
