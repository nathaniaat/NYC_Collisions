<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://nath:150206@localhost:27017/");
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'MongoDB client initialization failed: ' . $e->getMessage()]);
    exit;
}
$col = $client->nyc_collisions->crash_events;

$borough   = $_GET['borough'] ?? 'ALL';
$yearStart = isset($_GET['year_start']) ? intval($_GET['year_start']) : 2020;
$yearEnd   = isset($_GET['year_end']) ? intval($_GET['year_end']) : 2025;
if ($yearEnd < $yearStart) { $yearEnd = $yearStart; }
$yearStart = max(1900, $yearStart);
$yearEnd   = max($yearStart, $yearEnd);

$match = [
    'borough' => ['$ne' => 'Unknown'],
];
if ($borough !== 'ALL') {
    $match['borough'] = $borough;
}
$match['$expr'] = [
    '$and' => [
        ['$gte' => [['$substrBytes' => ['$crash_date', 0, 4]], strval($yearStart)]],
        ['$lte' => [['$substrBytes' => ['$crash_date', 0, 4]], strval($yearEnd)]],
    ]
];

$pipeline = [
    ['$match' => $match],
    ['$group' => [
        '_id' => [
            'borough' => '$borough',
            'crash_hour' => '$crash_hour',
        ],
        'total_crashes' => ['$sum' => 1],
        'pedestrians_killed' => ['$sum' => ['$ifNull' => ['$pedestrians_killed', 0]]],
        'pedestrians_injured' => ['$sum' => ['$ifNull' => ['$pedestrians_injured', 0]]],
        'cyclists_killed' => ['$sum' => ['$ifNull' => ['$cyclists_killed', 0]]],
        'cyclists_injured' => ['$sum' => ['$ifNull' => ['$cyclists_injured', 0]]],
        'total_vru_killed' => ['$sum' => ['$add' => [['$ifNull' => ['$pedestrians_killed', 0]], ['$ifNull' => ['$cyclists_killed', 0]]]]],
        'total_vru_injured' => ['$sum' => ['$add' => [['$ifNull' => ['$pedestrians_injured', 0]], ['$ifNull' => ['$cyclists_injured', 0]]]]],
    ]],
    ['$sort' => ['_id.borough' => 1, '_id.crash_hour' => 1]],
    ['$project' => [
        '_id' => 0,
        'borough' => '$_id.borough',
        'crash_hour' => '$_id.crash_hour',
        'total_crashes' => 1,
        'pedestrians_killed' => 1,
        'pedestrians_injured' => 1,
        'cyclists_killed' => 1,
        'cyclists_injured' => 1,
        'total_vru_killed' => 1,
        'total_vru_injured' => 1,
    ]],
];

$docs = iterator_to_array($col->aggregate($pipeline));

// If the main crash_events collection does not yet contain injury fields,
// fall back to the precomputed borough-hour collection so injured totals appear.
$hasInjuryData = array_reduce($docs, fn($carry, $doc) => $carry || ($doc['total_vru_injured'] ?? 0) > 0, false);
if (!$hasInjuryData && count($docs) > 0) {
    $fallbackCol = $client->nyc_collisions->vru_borough_hour;
    $fallbackQuery = $borough !== 'ALL' ? ['borough' => $borough] : [];
    $fallbackCursor = $fallbackCol->find($fallbackQuery, [
        'sort' => ['borough' => 1, 'crash_hour' => 1],
        'projection' => [
            '_id' => 0,
            'borough' => 1,
            'crash_hour' => 1,
            'total_crashes' => 1,
            'pedestrians_killed' => 1,
            'pedestrians_injured' => 1,
            'cyclists_killed' => 1,
            'cyclists_injured' => 1,
            'total_vru_killed' => 1,
            'total_vru_injured' => 1,
        ],
    ]);
    $docs = iterator_to_array($fallbackCursor);
}

$result = [];
foreach ($docs as $doc) {
    $result[] = [
        'borough' => (string)$doc['borough'],
        'crash_hour' => (int)$doc['crash_hour'],
        'total_crashes' => (int)$doc['total_crashes'],
        'pedestrians_killed' => (int)$doc['pedestrians_killed'],
        'pedestrians_injured' => (int)$doc['pedestrians_injured'],
        'cyclists_killed' => (int)$doc['cyclists_killed'],
        'cyclists_injured' => (int)$doc['cyclists_injured'],
        'total_vru_killed' => (int)$doc['total_vru_killed'],
        'total_vru_injured' => (int)$doc['total_vru_injured'],
    ];
}

echo json_encode($result);