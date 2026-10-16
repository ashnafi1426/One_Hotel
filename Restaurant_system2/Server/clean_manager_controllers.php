<?php
// 1. Refactor FloorAssignmentController.php
$path = __DIR__ . '/app/Http/Controllers/Api/Manager/FloorAssignmentController.php';
$c = file_get_contents($path);
$c = preg_replace('/protected function getHotelId\(\): \?string\s*\{.*?\n    \}/s', '', $c);
$c = preg_replace('/\$hotelId\s*=\s*\$this->getHotelId\(\);/m', '', $c);
$c = preg_replace('/->when\(\$hotelId,\s*fn\(\$q\)\s*=>\s*\$q->where\([\'"]hotel_id[\'"],\s*\$hotelId\)\)/', '', $c);
$c = preg_replace('/->when\(\$hotelId,\s*fn\(\$sub\)\s*=>\s*\$sub->where\([\'"]hotel_id[\'"],\s*\$hotelId\)\)/', '', $c);
$c = preg_replace('/if\s*\(\$hotelId\)\s*\{\s*\$query->where\([\'"]hotel_id[\'"],\s*\$hotelId\);\s*\}/', '', $c);
$c = str_replace("'hotel_id' => \$hotelId,", '', $c);
$c = str_replace("Waiter::when(\$hotelId, fn(\$q) => \$q->where('hotel_id', \$hotelId))", "Waiter::query()", $c);
file_put_contents($path, $c);
echo "FloorAssignmentController updated\n";

// 2. Refactor ShiftManagementController.php
$path = __DIR__ . '/app/Http/Controllers/Api/Manager/ShiftManagementController.php';
$c = file_get_contents($path);
$c = preg_replace('/protected function getHotelId\(\): \?string\s*\{.*?\n    \}/s', '', $c);
$c = preg_replace('/\$hotelId\s*=\s*\$this->getHotelId\(\);/m', '', $c);
$c = preg_replace('/if\s*\(\$hotelId\)\s*\{\s*\$query->where\([\'"]hotel_id[\'"],\s*\$hotelId\);\s*\}/', '', $c);
$c = str_replace("'hotel_id' => \$hotelId,", '', $c);
$c = str_replace("Rule::unique('hotel_shifts', 'name')\n                ->where(fn(\$q) => \$hotelId ? \$q->where('hotel_id', \$hotelId) : \$q),", "'unique:hotel_shifts,name',", $c);
$c = preg_replace('/\\\\Illuminate\\\\Validation\\\\Rule::unique\(\'hotel_shifts\',\s*\'name\'\)\s*->where\(fn\(\$q\)\s*=>.*?\),/s', "'unique:hotel_shifts,name',", $c);
file_put_contents($path, $c);
echo "ShiftManagementController updated\n";

// 3. Refactor WaiterManagementController.php
$path = __DIR__ . '/app/Http/Controllers/Api/Manager/WaiterManagementController.php';
$c = file_get_contents($path);
$c = preg_replace('/protected function getHotelId\(\): \?string\s*\{.*?\n    \}/s', '', $c);
$c = preg_replace('/\$hotelId\s*=\s*\$this->getHotelId\(\);/m', '', $c);
$c = preg_replace('/->when\(\$hotelId,\s*fn\(\$sub\)\s*=>\s*\$sub->where\([\'"]hotel_id[\'"],\s*\$hotelId\)\)/', '', $c);
$c = preg_replace('/->when\(\$hotelId,\s*fn\(\$q\)\s*=>\s*\$q->where\([\'"]hotel_id[\'"],\s*\$hotelId\)\)/', '', $c);
$c = preg_replace('/if\s*\(\$hotelId\)\s*\{\s*\$waitersQuery->where\(function\s*\(\$q\)\s*use\s*\(\$hotelId\)\s*\{\s*\$q->where\([\'"]hotel_id[\'"],\s*\$hotelId\)\s*->orWhereNull\([\'"]hotel_id[\'"]\);\s*\}\);\s*\}/', '', $c);
$c = str_replace("'hotel_id' => \$hotelId,", '', $c);
$c = preg_replace('/\$hotelId\s*=\s*\$waiter->hotel_id.*?;/m', '', $c);
file_put_contents($path, $c);
echo "WaiterManagementController updated\n";

// 4. Refactor WaiterTableAssignmentController.php
$path = __DIR__ . '/app/Http/Controllers/Api/Manager/WaiterTableAssignmentController.php';
$c = file_get_contents($path);
$c = preg_replace('/protected function getHotelId\(\): \?string\s*\{.*?\n    \}/s', '', $c);
$c = preg_replace('/\$hotelId\s*=\s*\$this->getHotelId\(\);/m', '', $c);
$c = preg_replace('/if\s*\(\$hotelId\s*&&\s*\$tbl->hotel_id\s*&&\s*\$tbl->hotel_id\s*!==\s*\$hotelId\)\s*\{.*?\}/s', '', $c);
$c = preg_replace('/if\s*\(\$hotelId\s*&&\s*\$waiter->hotel_id\s*&&\s*\$waiter->hotel_id\s*!==\s*\$hotelId\)\s*\{.*?\}/s', '', $c);
file_put_contents($path, $c);
echo "WaiterTableAssignmentController updated\n";

// 5. Refactor ReportController.php
$path = __DIR__ . '/app/Http/Controllers/Api/Manager/ReportController.php';
$c = file_get_contents($path);
$c = preg_replace('/if\s*\(\$hotelId\)\s*\{\s*\$popularItemsQuery->where\(\'orders\.hotel_id\',\s*\$hotelId\);\s*\}/', '', $c);
file_put_contents($path, $c);
echo "ReportController updated\n";

// 6. Refactor RestaurantSectionController.php
$path = __DIR__ . '/app/Http/Controllers/Api/Manager/RestaurantSectionController.php';
$c = file_get_contents($path);
$c = preg_replace('/protected function getHotelId\(\): \?string\s*\{.*?\n    \}/s', '', $c);
$c = preg_replace('/\$hotelId\s*=\s*\$this->getHotelId\(\);/m', '', $c);
$c = str_replace('$this->sectionService->getSections($hotelId, $activeOnly);', '$this->sectionService->getSections(null, $activeOnly);', $c);
$c = str_replace('$this->sectionService->getSection($id, $hotelId);', '$this->sectionService->getSection($id);', $c);
$c = str_replace('$this->sectionService->createSection($request->validated(), $hotelId);', '$this->sectionService->createSection($request->validated());', $c);
$c = str_replace('$this->sectionService->updateSection($section, $request->validated(), $hotelId);', '$this->sectionService->updateSection($section, $request->validated());', $c);
file_put_contents($path, $c);
echo "RestaurantSectionController updated\n";

// 7. Refactor RestaurantTableController.php
$path = __DIR__ . '/app/Http/Controllers/Api/Manager/RestaurantTableController.php';
$c = file_get_contents($path);
$c = preg_replace('/protected function getHotelId\(\): \?string\s*\{.*?\n    \}/s', '', $c);
$c = preg_replace('/\$hotelId\s*=\s*\$this->getHotelId\(\);/m', '', $c);
$c = str_replace('$this->tableService->getTables($request->all(), $perPage, $hotelId);', '$this->tableService->getTables($request->all(), $perPage);', $c);
$c = str_replace('$this->tableService->getTable($id, $hotelId);', '$this->tableService->getTable($id);', $c);
$c = str_replace('$this->tableService->createTable($request->validated(), $hotelId);', '$this->tableService->createTable($request->validated());', $c);
$c = str_replace('$this->tableService->updateTable($table, $request->validated(), $hotelId);', '$this->tableService->updateTable($table, $request->validated());', $c);
$c = str_replace('$this->tableService->getStatistics($hotelId);', '$this->tableService->getStatistics();', $c);
file_put_contents($path, $c);
echo "RestaurantTableController updated\n";
