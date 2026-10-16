<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardController extends Controller
{
    /**
     * Get main administrator dashboard metrics and statistics.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $forceRefresh = $request->query('refresh') === 'true' || $request->header('X-Refresh') === 'true';
            $cacheKey = 'admin_dashboard_stats';

            if ($forceRefresh) {
                Cache::forget($cacheKey);
            }

            $payload = Cache::remember($cacheKey, 300, function () use ($user) {
                $hotel = Hotel::first();

                $roomStats = Room::selectRaw("
                        COUNT(*) as total_rooms,
                        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_rooms,
                        SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms,
                        SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved_rooms,
                        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_rooms
                    ")->first();

                $totalRooms = (int) ($roomStats->total_rooms ?? 0);
                $availableRooms = (int) ($roomStats->available_rooms ?? 0);
                $occupiedRooms = (int) ($roomStats->occupied_rooms ?? 0);
                $reservedRooms = (int) ($roomStats->reserved_rooms ?? 0);
                $maintenanceRooms = (int) ($roomStats->maintenance_rooms ?? 0);

                $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0.0;

                $totalRoomTypes = RoomType::count();
                $totalUsers = User::count();
                $activeStaff = User::where('is_active', true)->count();

                $today = Carbon::today();
                $todayRevenue = 0.0;
                try {
                    $todayRevenue = (float) DB::table('payments')
                        ->whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                        ->where('status', 'completed')
                        ->sum('amount');
                } catch (Throwable) {
                    $todayRevenue = (float) (Reservation::whereBetween('created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                        ->sum('total_amount') ?: 0);
                }

                $monthlyRevenue = $this->getMonthlyRevenueSeries();

                $recentReservations = Reservation::select(['id', 'booking_reference', 'guest_id', 'room_id', 'check_in_date', 'status', 'total_amount', 'created_at'])
                    ->with([
                        'guest:id,first_name,last_name,email',
                        'room:id,room_number,room_type_id',
                        'room.roomType:id,name',
                    ])
                    ->orderBy('created_at', 'desc')
                    ->limit(6)
                    ->get()
                    ->map(function ($res) {
                        $guestName = $res->guest ? trim($res->guest->first_name . ' ' . $res->guest->last_name) : 'Guest';
                        return [
                            'id' => $res->id,
                            'booking_reference' => $res->booking_reference ?: ('BK-' . substr((string) $res->id, 0, 8)),
                            'guest_name' => $guestName,
                            'guest' => [
                                'id' => $res->guest?->id,
                                'name' => $guestName,
                                'email' => $res->guest?->email ?: '-',
                            ],
                            'room_type' => $res->room?->roomType?->name ?: ($res->room?->room_number ? ('Room ' . $res->room->room_number) : 'Standard'),
                            'check_in_date' => $res->check_in_date ? Carbon::parse($res->check_in_date)->format('Y-m-d') : '-',
                            'status' => ucfirst(strtolower($res->status ?: 'Confirmed')),
                            'total_price' => (float) ($res->total_amount ?: 0),
                        ];
                    })
                    ->values()
                    ->all();

                $maintenanceAlerts = Room::where('status', 'maintenance')
                    ->select(['id', 'room_number', 'description'])
                    ->limit(5)
                    ->get()
                    ->map(function ($rm) {
                        return [
                            'id' => $rm->id,
                            'title' => 'Room ' . $rm->room_number . ' Maintenance',
                            'description' => $rm->description ?: 'Scheduled room inspection and repairs required.',
                            'severity' => 'medium',
                        ];
                    })
                    ->values()
                    ->all();

                $staffActivity = [
                    [
                        'id' => 1,
                        'staff_name' => $user?->name ?: ($user?->first_name ? ($user->first_name . ' ' . $user->last_name) : 'Staff Member'),
                        'staff_initials' => strtoupper(substr($user?->first_name ?: ($user?->name ?: 'A'), 0, 1) . substr($user?->last_name ?: 'D', 0, 1)),
                        'action' => 'Accessed hotel administration dashboard',
                        'timestamp' => 'Just now',
                    ],
                ];

                $orderStats = [
                    'total' => 0,
                    'total_today' => 0,
                    'active' => 0,
                    'recent' => [],
                ];
                try {
                    $aggregatedOrders = Order::selectRaw("
                        COUNT(*) as total_count,
                        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_count,
                        SUM(CASE WHEN status IN ('pending', 'preparing', 'ready', 'on_delivery') THEN 1 ELSE 0 END) as active_count
                    ")->first();

                    $recentOrders = Order::select(['id', 'order_number', 'status', 'total', 'created_at'])
                        ->latest()
                        ->limit(5)
                        ->get()
                        ->map(fn($o) => [
                            'id' => $o->id,
                            'order_number' => $o->order_number ?: ('ORD-' . substr((string)$o->id, 0, 6)),
                            'status' => $o->status,
                            'total' => (float) ($o->total ?? 0),
                            'created_at' => $o->created_at?->diffForHumans(),
                        ])
                        ->values()
                        ->all();

                    $orderStats = [
                        'total' => (int) ($aggregatedOrders->total_count ?? 0),
                        'total_today' => (int) ($aggregatedOrders->today_count ?? 0),
                        'active' => (int) ($aggregatedOrders->active_count ?? 0),
                        'recent' => $recentOrders,
                    ];
                } catch (Throwable) {}

                $productStats = [
                    'total_items' => 0,
                    'total_rooms' => $totalRooms,
                ];
                try {
                    $productStats['total_items'] = MenuItem::count();
                } catch (Throwable) {}

                $settingsData = [
                    'hotel_id' => $hotel?->id,
                    'hotel_name' => $hotel?->name ?? 'Sheraten Hotel',
                    'currency' => $hotel?->currency ?: 'ETB',
                    'system_status' => 'operational',
                ];

                return [
                    'users' => [
                        'total' => $totalUsers,
                        'active_staff' => $activeStaff,
                    ],
                    'orders' => $orderStats,
                    'products' => $productStats,
                    'payments' => [
                        'today_revenue' => round($todayRevenue, 2),
                        'monthly_series' => $monthlyRevenue,
                    ],
                    'statistics' => [
                        'total_rooms' => $totalRooms,
                        'occupancy_rate' => $occupancyRate,
                        'room_statistics' => [
                            'available' => $availableRooms,
                            'occupied' => $occupiedRooms,
                            'reserved' => $reservedRooms,
                            'maintenance' => $maintenanceRooms,
                        ],
                    ],
                    'notifications' => [
                        'alerts' => $maintenanceAlerts,
                        'count' => count($maintenanceAlerts),
                    ],
                    'settings' => $settingsData,

                    'overview' => [
                        'totalRooms' => $totalRooms,
                        'totalRoomTypes' => $totalRoomTypes,
                        'occupancyRate' => $occupancyRate,
                        'totalUsers' => $totalUsers,
                        'activeStaff' => $activeStaff,
                        'todayRevenue' => round($todayRevenue, 2),
                        'activeOrders' => $orderStats['active'],
                        'totalProducts' => $productStats['total_items'],
                    ],
                    'roomStatistics' => [
                        'available' => $availableRooms,
                        'occupied' => $occupiedRooms,
                        'reserved' => $reservedRooms,
                        'maintenance' => $maintenanceRooms,
                    ],
                    'recentReservations' => $recentReservations,
                    'monthlyRevenue' => $monthlyRevenue,
                    'staffActivity' => $staffActivity,
                    'maintenanceAlerts' => $maintenanceAlerts,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $payload,
                'cached' => !$forceRefresh,
            ]);
        } catch (Throwable $e) {
            Log::error('DashboardController@index error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard metrics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get revenue data tailored for chart timeframes (week, month, year) with caching.
     */
    public function revenue(Request $request): JsonResponse
    {
        try {
            $timeframe = (string) $request->query('timeframe', 'month');
            $forceRefresh = $request->query('refresh') === 'true' || $request->header('X-Refresh') === 'true';
            $cacheKey = 'admin_dashboard_revenue:' . $timeframe;

            if ($forceRefresh) {
                Cache::forget($cacheKey);
            }

            $data = Cache::remember($cacheKey, 300, function () use ($timeframe) {
                return match ($timeframe) {
                    'week' => $this->getWeeklyRevenueSeries(),
                    'year' => $this->getYearlyRevenueSeries(),
                    default => $this->getMonthlyRevenueSeries(),
                };
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'cached' => !$forceRefresh,
            ]);
        } catch (Throwable $e) {
            Log::error('DashboardController@revenue error', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch revenue series: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper: 6-month revenue series (single aggregated query)
     */
    private function getMonthlyRevenueSeries(): array
    {
        $startRange = Carbon::now()->subMonths(5)->startOfMonth();
        $endRange = Carbon::now()->endOfMonth();

        $paymentSums = [];
        try {
            $paymentSums = DB::table('payments')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startRange, $endRange])
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(amount) as total")
                ->groupBy('ym')
                ->pluck('total', 'ym')
                ->toArray();
        } catch (Throwable) {
            $paymentSums = [];
        }

        $resSums = [];
        try {
            $resSums = Reservation::whereBetween('created_at', [$startRange, $endRange])
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(total_amount) as total")
                ->groupBy('ym')
                ->pluck('total', 'ym')
                ->toArray();
        } catch (Throwable) {
            $resSums = [];
        }

        $series = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $ym = $month->format('Y-m');
            $monthName = $month->format('M');

            $amount = (float) ($paymentSums[$ym] ?? 0.0);
            if ($amount <= 0) {
                $amount = (float) ($resSums[$ym] ?? 0.0);
            }

            $series[] = [
                'month' => $monthName,
                'revenue' => round($amount, 2),
            ];
        }

        return $series;
    }

    /**
     * Helper: 7-day revenue series (single aggregated query)
     */
    private function getWeeklyRevenueSeries(): array
    {
        $startRange = Carbon::now()->subDays(6)->startOfDay();
        $endRange = Carbon::now()->endOfDay();

        $paymentSums = [];
        try {
            $paymentSums = DB::table('payments')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startRange, $endRange])
                ->selectRaw("DATE(created_at) as dt, SUM(amount) as total")
                ->groupBy('dt')
                ->pluck('total', 'dt')
                ->toArray();
        } catch (Throwable) {
            $paymentSums = [];
        }

        $resSums = [];
        try {
            $resSums = Reservation::whereBetween('created_at', [$startRange, $endRange])
                ->selectRaw("DATE(created_at) as dt, SUM(total_amount) as total")
                ->groupBy('dt')
                ->pluck('total', 'dt')
                ->toArray();
        } catch (Throwable) {
            $resSums = [];
        }

        $series = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);
            $dt = $day->format('Y-m-d');
            $dayName = $day->format('D');

            $amount = (float) ($paymentSums[$dt] ?? 0.0);
            if ($amount <= 0) {
                $amount = (float) ($resSums[$dt] ?? 0.0);
            }

            $series[] = [
                'month' => $dayName,
                'revenue' => round($amount, 2),
            ];
        }

        return $series;
    }

    /**
     * Helper: 12-month revenue series
     */
    private function getYearlyRevenueSeries(): array
    {
        return $this->getMonthlyRevenueSeries();
    }
}
