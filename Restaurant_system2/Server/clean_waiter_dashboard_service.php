<?php
$filePath = __DIR__ . '/app/Services/Waiter/WaiterDashboardService.php';
$content = file_get_contents($filePath);

// 1. Clean floor IDs cache key and query
$content = str_replace(
    '$cacheKey = $hotelId ? "waiter_floor_ids:{$hotelId}:{$waiterId}" : "waiter_floor_ids:{$waiterId}";',
    '$cacheKey = "waiter_floor_ids:{$waiterId}";',
    $content
);
$content = preg_replace(
    '/if \(\$hotelId\) \{\s*\$query->where\(\'hotel_id\', \$hotelId\);\s*\}/',
    '// Scoped to single hotel installation',
    $content
);

// 2. Clean table IDs cache key and query
$content = str_replace(
    '$cacheKey = $hotelId ? "waiter_table_ids:{$hotelId}:{$waiterId}" : "waiter_table_ids:{$waiterId}";',
    '$cacheKey = "waiter_table_ids:{$waiterId}";',
    $content
);
$content = preg_replace(
    '/if \(\$hotelId\) \{\s*\$query->where\(function \(\$q\) use \(\$hotelId\) \{\s*\$q->where\(\'hotel_id\', \$hotelId\)\s*->orWhereNull\(\'hotel_id\'\);\s*\}\);\s*\}/',
    '// Scoped to single hotel installation',
    $content
);

// 3. Clean dashboard stats cache key
$content = str_replace(
    '$cacheKey = "waiter_dashboard_stats:" . ($hotelId ?? \'all\') . ":" . ($waiterId ?? auth()->id() ?? \'all\');',
    '$cacheKey = "waiter_dashboard_stats:" . ($waiterId ?? auth()->id() ?? \'all\');',
    $content
);

// 4. Clean delivery_tasks.hotel_id queries
$content = preg_replace(
    '/if \(\$hotelId\) \{\s*\$taskQuery->where\(\'delivery_tasks\.hotel_id\', \$hotelId\);\s*\}/',
    '// Single hotel installation',
    $content
);
$content = preg_replace(
    '/if \(\$hotelId\) \{\s*\$taskMetricsQuery->where\(\'delivery_tasks\.hotel_id\', \$hotelId\);\s*\}/',
    '// Single hotel installation',
    $content
);
$content = preg_replace(
    '/if \(\$hotelId\) \{\s*\$performanceQuery->where\(\'hotel_id\', \$hotelId\);\s*\}/',
    '// Single hotel installation',
    $content
);
$content = preg_replace(
    '/if \(\$hotelId\) \{\s*\$baseQuery->where\(\'delivery_tasks\.hotel_id\', \$hotelId\);\s*\}/',
    '// Single hotel installation',
    $content
);
$content = preg_replace(
    '/if \(\$hotelId\) \{\s*\$query->where\(\'delivery_tasks\.hotel_id\', \$hotelId\);\s*\}/',
    '// Single hotel installation',
    $content
);
$content = preg_replace(
    '/if \(\$hotelId\) \{\s*\$query->where\(\'hotel_id\', \$hotelId\);\s*\}/',
    '// Single hotel installation',
    $content
);
$content = preg_replace(
    '/if \(\$hotelId\) \{\s*\$tasksQuery->where\(function \(\$q\) use \(\$hotelId\) \{\s*\$q->where\(\'delivery_tasks\.hotel_id\', \$hotelId\)\s*->orWhereNull\(\'delivery_tasks\.hotel_id\'\);\s*\}\);\s*\}/',
    '// Single hotel installation',
    $content
);
$content = preg_replace(
    '/if \(\$hotelId\) \{\s*\$query->where\(function \(\$q\) use \(\$hotelId\) \{\s*\$q->where\(\'delivery_tasks\.hotel_id\', \$hotelId\)\s*->orWhereNull\(\'delivery_tasks\.hotel_id\'\);\s*\}\);\s*\}/',
    '// Single hotel installation',
    $content
);
$content = preg_replace(
    '/if \(\$hotelId\) \{\s*\$baseQuery->where\(function\(\$q\) use \(\$hotelId\) \{\s*\$q->where\(\'delivery_tasks\.hotel_id\', \$hotelId\)\s*->orWhereNull\(\'delivery_tasks\.hotel_id\'\);\s*\}\);\s*\}/',
    '// Single hotel installation',
    $content
);

// 5. Clean $isAdminOrManager
$content = str_replace(
    '$isAdminOrManager = auth()->user() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, [\'admin\', \'hotel_admin\', \'manager\']));',
    '$isAdminOrManager = auth()->user() && in_array(auth()->user()->role, [\'admin\', \'manager\', \'receptionist\']);',
    $content
);

// 6. Remove unused $hotelId = app(TenantContext::class)->getHotelId(); assignments where redundant
$content = preg_replace(
    '/\$hotelId = app\(TenantContext::class\)->getHotelId\(\);\s*/',
    '',
    $content
);

file_put_contents($filePath, $content);
echo "Successfully cleaned WaiterDashboardService.php\n";
