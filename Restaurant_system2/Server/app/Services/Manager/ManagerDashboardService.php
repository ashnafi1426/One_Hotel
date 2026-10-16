<?php

namespace App\Services\Manager;

use App\Models\Room;
use App\Models\CheckIn;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Order;
use App\Models\Guest;
use App\Models\ManagerActivityLog;
use App\Models\HousekeepingTask;
use App\Models\RoomServiceDelivery;
use App\Models\DeliveryTask;
use App\Models\Waiter;
use App\Models\LaundryRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ManagerDashboardService
{
    public function statistics(): array
    {
        $today = Carbon::today();

        $orders = Order::query();
        $totalOrders = (clone $orders)->count();
        $pendingOrders = (clone $orders)->where('status', Order::STATUS_PENDING)->count();
        $preparingOrders = (clone $orders)->where('status', Order::STATUS_PREPARING)->count();
        $readyOrders = (clone $orders)->where('status', Order::STATUS_READY)->count();
        $servedOrders = (clone $orders)->where('status', Order::STATUS_SERVED)->count();

        $deliveries = DeliveryTask::query();
        $totalDeliveries = (clone $deliveries)->count();
        $activeDeliveries = (clone $deliveries)->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery'])->count();
        $completedDeliveries = (clone $deliveries)->where('status', 'delivered')->count();

        $totalRooms = Room::count();
        $occupiedRooms = CheckIn::whereNull('checked_out_at')->count();
        $availableRooms = Room::where('status', 'available')->count();
        $reservedRooms = Reservation::where('status', 'confirmed')->count();
        $maintenanceRooms = Room::where('status', 'maintenance')->count();

        $totalGuests = Guest::count();
        $checkedInGuests = $occupiedRooms;
        $guestCheckouts = CheckIn::whereDate('expected_check_out_at', $today)->count();
        $todayReservations = Reservation::whereDate('created_at', $today)->count();

        $laundryQuery = LaundryRequest::query();
        $housekeepingQuery = HousekeepingTask::query();

        $pendingLaundry = (clone $laundryQuery)->where('status', 'pending')->count();
        $pendingHousekeeping = (clone $housekeepingQuery)->where('status', 'pending')->count();

        $activeStaff = User::where('is_active', true)->where('role', '!=', 'guest')->count();

        $todayRevenue = (float) ((clone $orders)->whereDate('served_at', $today)->where('status', Order::STATUS_SERVED)->sum('total') ?: 0);
        $monthlyRevenue = (float) ((clone $orders)->whereMonth('served_at', $today->month)->where('status', Order::STATUS_SERVED)->sum('total') ?: 0);

        if ($todayRevenue == 0) {
            try {
                $todayRevenue = (float) DB::table('payments')
                    ->where('status', 'completed')
                    ->whereDate('created_at', $today)
                    ->sum('amount');
                $monthlyRevenue = (float) DB::table('payments')
                    ->where('status', 'completed')
                    ->whereMonth('created_at', $today->month)
                    ->sum('amount');
            } catch (\Throwable $e) {}
        }

        return [
            'totalRooms' => $totalRooms,
            'occupiedRooms' => $occupiedRooms,
            'availableRooms' => $availableRooms,
            'reservedRooms' => $reservedRooms,
            'maintenanceRooms' => $maintenanceRooms,

            'totalGuests' => $totalGuests,
            'checkedInGuests' => $checkedInGuests,
            'guestCheckouts' => $guestCheckouts,
            'todayReservations' => $todayReservations,

            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'preparingOrders' => $preparingOrders,
            'readyOrders' => $readyOrders,
            'servedOrders' => $servedOrders,

            'totalDeliveries' => $totalDeliveries,
            'activeDeliveries' => $activeDeliveries,
            'completedDeliveries' => $completedDeliveries,

            'pendingLaundry' => $pendingLaundry,
            'pendingHousekeeping' => $pendingHousekeeping,

            'activeStaff' => $activeStaff,

            'todayRevenue' => round($todayRevenue, 2),
            'monthlyRevenue' => round($monthlyRevenue, 2),
        ];
    }

    public function revenueSummary(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisWeekStart = Carbon::now()->startOfWeek();
        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisYearStart = Carbon::now()->startOfYear();

        return [
            'today' => $this->calculateRevenue($today, $today),
            'yesterday' => $this->calculateRevenue($yesterday, $yesterday),
            'thisWeek' => $this->calculateRevenue($thisWeekStart, Carbon::now()),
            'thisMonth' => $this->calculateRevenue($thisMonthStart, Carbon::now()),
            'thisYear' => $this->calculateRevenue($thisYearStart, Carbon::now()),
        ];
    }

    private function calculateRevenue(Carbon $start, Carbon $end): float
    {
        try {
            if (Schema::hasTable('payments')) {
                return (float) DB::table('payments')
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                    ->sum('amount');
            }
        } catch (\Throwable $e) {}

        return (float) (Order::whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])->sum('total') ?: 0);
    }

    public function occupancySummary(): array
    {
        $totalRooms = Room::count();
        $occupiedRooms = CheckIn::whereNull('checked_out_at')->count();
        $availableRooms = Room::where('status', 'available')->count();
        $reservedRooms = Reservation::where('status', 'confirmed')->count();
        $maintenanceRooms = Room::where('status', 'maintenance')->count();

        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

        return [
            'totalRooms' => $totalRooms,
            'occupiedRooms' => $occupiedRooms,
            'availableRooms' => $availableRooms,
            'reservedRooms' => $reservedRooms,
            'maintenanceRooms' => $maintenanceRooms,
            'occupancyRate' => $occupancyRate,
        ];
    }

    public function reservationSummary(): array
    {
        return [
            'pending' => Reservation::where('status', 'pending')->count(),
            'confirmed' => Reservation::where('status', 'confirmed')->count(),
            'checkedIn' => CheckIn::whereNull('checked_out_at')->count(),
            'checkedOut' => CheckIn::whereNotNull('checked_out_at')->count(),
            'cancelled' => Reservation::where('status', 'cancelled')->count(),
        ];
    }

    public function revenueChart(string $period = 'monthly'): array
    {
        $data = [];

        if ($period === 'weekly') {
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $revenue = $this->calculateRevenue($date, $date);
                $data[] = [
                    'label' => $date->format('D'),
                    'revenue' => $revenue,
                ];
            }
        } elseif ($period === 'monthly') {
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $revenue = $this->calculateRevenue($date, $date);
                $data[] = [
                    'label' => $date->format('M d'),
                    'revenue' => $revenue,
                ];
            }
        } else {
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $revenue = $this->calculateRevenue($date->startOfMonth(), $date->endOfMonth());
                $data[] = [
                    'label' => $date->format('M Y'),
                    'revenue' => $revenue,
                ];
            }
        }

        return $data;
    }

    public function occupancyChart(): array
    {
        $data = [];
        $total = Room::count();

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $occupied = CheckIn::where(function ($query) use ($date) {
                    $query->whereDate('checked_in_at', '<=', $date)
                        ->where(function ($q) use ($date) {
                            $q->whereDate('expected_check_out_at', '>=', $date)
                                ->orWhereNull('checked_out_at');
                        });
                })->count();

            $data[] = [
                'label' => $date->format('D'),
                'occupied' => $occupied,
                'available' => max(0, $total - $occupied),
            ];
        }

        return $data;
    }

    public function completeDashboard(): array
    {
        return [
            'statistics' => $this->statistics(),
            'revenue' => $this->revenueSummary(),
            'occupancy' => $this->occupancySummary(),
            'reservations' => $this->reservationSummary(),
            'staff' => $this->getStaff(),
            'recentOrders' => $this->getRecentOrders(),
            'deliveries' => $this->getDeliveries(),
            'housekeeping' => $this->getHousekeeping(),
            'laundry' => $this->getLaundry(),
            'recentActivities' => $this->getActivities(),
            'notifications' => [],
            'revenueChart' => $this->revenueChart(),
            'occupancyChart' => $this->occupancyChart(),
            'waiters' => $this->getWaiters(),
        ];
    }

    public function getStaff(): array
    {
        return User::where('role', '!=', 'guest')
            ->with('roles')
            ->limit(10)
            ->get()
            ->map(function ($user) {
                $roleName = $user->roles->first()?->name ?? ucfirst($user->role ?: 'staff');
                return [
                    'id' => $user->id,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->email,
                    'phone' => $user->phone,
                    'role' => $roleName,
                    'status' => $user->is_active ? 'active' : 'inactive',
                ];
            })
            ->toArray();
    }

    public function getRecentOrders(): array
    {
        return Order::with('guest', 'room', 'orderItems')
            ->select('id', 'order_number', 'guest_id', 'room_id', 'status', 'total', 'created_at')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'orderNumber' => $order->order_number,
                    'guestName' => $order->guest ? ($order->guest->first_name . ' ' . $order->guest->last_name) : 'Unknown',
                    'roomNumber' => $order->room?->room_number,
                    'itemCount' => $order->orderItems->count(),
                    'status' => $order->status,
                    'total' => $order->total,
                    'createdAt' => $order->created_at,
                ];
            })
            ->toArray();
    }

    public function getDeliveries(): array
    {
        return DeliveryTask::with('room', 'order', 'waiter')
            ->select('id', 'room_id', 'order_id', 'status', 'waiter_id', 'assigned_at', 'delivered_at')
            ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'on_delivery', 'delivered'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($delivery) {
                return [
                    'id' => $delivery->id,
                    'roomNumber' => $delivery->room?->room_number,
                    'orderNumber' => $delivery->order?->order_number,
                    'waiterName' => $delivery->waiter?->name ?? 'Unassigned',
                    'status' => $delivery->status,
                    'time' => $delivery->delivered_at ?: $delivery->assigned_at,
                ];
            })
            ->toArray();
    }

    public function getHousekeeping(): array
    {
        return HousekeepingTask::with('room', 'assignedTo')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'roomId' => $task->room_id,
                    'roomNumber' => $task->room?->room_number,
                    'assignedTo' => $task->assignedTo ? ($task->assignedTo->first_name . ' ' . $task->assignedTo->last_name) : 'Unassigned',
                    'status' => $task->status,
                    'taskType' => $task->task_type,
                    'priority' => $task->priority,
                    'scheduledTime' => $task->scheduled_time,
                ];
            })
            ->toArray();
    }

    public function getLaundry(): array
    {
        return LaundryRequest::with('room', 'guest')
            ->select('id', 'room_id', 'guest_id', 'status', 'requested_time', 'pickup_time', 'delivery_time', 'cost')
            ->whereIn('status', ['pending', 'processing', 'ready'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'roomId' => $request->room_id,
                    'roomNumber' => $request->room?->room_number,
                    'guestName' => $request->guest ? ($request->guest->first_name . ' ' . $request->guest->last_name) : 'N/A',
                    'status' => $request->status,
                    'requestedTime' => $request->requested_time,
                    'pickupTime' => $request->pickup_time,
                    'deliveryTime' => $request->delivery_time,
                    'cost' => $request->cost,
                ];
            })
            ->toArray();
    }

    public function getActivities(): array
    {
        return ManagerActivityLog::select('id', 'manager_id', 'action', 'description', 'created_at')
            ->with('manager')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'manager' => $activity->manager ? ($activity->manager->first_name . ' ' . $activity->manager->last_name) : 'System',
                    'action' => $activity->action,
                    'description' => $activity->description,
                    'timestamp' => $activity->created_at,
                ];
            })
            ->toArray();
    }

    public function getWaiters(): array
    {
        return Waiter::with('user')
            ->select('id', 'user_id', 'section', 'status', 'shift', 'experience_level')
            ->where('status', 'active')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($waiter) {
                return [
                    'id' => $waiter->id,
                    'userId' => $waiter->user_id,
                    'name' => $waiter->user ? ($waiter->user->first_name . ' ' . $waiter->user->last_name) : 'N/A',
                    'section' => $waiter->section,
                    'status' => $waiter->status,
                    'shift' => $waiter->shift,
                    'experienceLevel' => $waiter->experience_level,
                ];
            })
            ->toArray();
    }
}
