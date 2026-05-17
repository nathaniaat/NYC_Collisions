<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$borough    = $_GET['borough']    ?? 'ALL';
$hour       = isset($_GET['hour']) ? (int)$_GET['hour'] : null;
$day        = $_GET['day'] ?? null;
$year_start = (int)($_GET['year_start'] ?? 2020);
$year_end   = (int)($_GET['year_end']   ?? 2025);

$conn = pg_connect("host=localhost port=5434 dbname=collision user=postgres password=password");
if (!$conn) { echo json_encode(['error' => 'DB connection failed']); exit; }

if ($hour !== null && $day !== null) {
    $params = [$hour, $day, $year_start, $year_end, $borough];
    $sql = "
        SELECT
            fc.collision_id,
            l.borough,
            t.col_time,
            fc.people_injured,
            fc.people_killed
        FROM fact_collisions fc
        JOIN time_collision t     ON fc.time_id     = t.time_id
        JOIN location_collision l ON fc.location_id = l.location_id
        WHERE t.col_hour    = \$1
          AND t.day_of_week = \$2
          AND t.col_year BETWEEN \$3 AND \$4
          AND (\$5 = 'ALL' OR l.borough = \$5)
        ORDER BY fc.people_killed DESC, fc.people_injured DESC
        LIMIT 50
    ";
} else {
    $params = [$year_start, $year_end, $borough];
    $sql = "
        SELECT
            fc.collision_id,
            l.borough,
            t.col_time,
            fc.people_injured,
            fc.people_killed
        FROM fact_collisions fc
        JOIN time_collision t     ON fc.time_id     = t.time_id
        JOIN location_collision l ON fc.location_id = l.location_id
        WHERE t.col_year BETWEEN \$1 AND \$2
          AND (\$3 = 'ALL' OR l.borough = \$3)
        ORDER BY fc.people_killed DESC, fc.people_injured DESC
        LIMIT 50
    ";
}

$result = pg_query_params($conn, $sql, $params);
echo json_encode(pg_fetch_all($result) ?: []);
pg_close($conn);