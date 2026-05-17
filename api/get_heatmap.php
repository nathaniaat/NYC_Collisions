<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$borough    = $_GET['borough']    ?? 'ALL';
$year_start = (int)($_GET['year_start'] ?? 2020);
$year_end   = (int)($_GET['year_end']   ?? 2025);

$conn = pg_connect("host=localhost port=5434 dbname=collision user=postgres password=password");
if (!$conn) { echo json_encode(['error' => 'DB connection failed']); exit; }

$params = [$year_start, $year_end, $borough];

$sql = "
    SELECT
        t.col_hour,
        t.day_of_week,
        COUNT(*) AS total_crash,
        SUM(fc.people_killed) AS total_killed
    FROM fact_collisions fc
    JOIN time_collision t     ON fc.time_id     = t.time_id
    JOIN location_collision l ON fc.location_id = l.location_id
    WHERE t.col_year BETWEEN \$1 AND \$2
      AND (\$3 = 'ALL' OR l.borough = \$3)
    GROUP BY t.col_hour, t.day_of_week
    ORDER BY t.col_hour, t.day_of_week
";

$result = pg_query_params($conn, $sql, $params);
echo json_encode(pg_fetch_all($result) ?: []);
pg_close($conn);