<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$conn = pg_connect("host=localhost port=5434 dbname=collision user=postgres password=password");
if (!$conn) { echo json_encode(['error' => 'DB connection failed']); exit; }

$borough = $_GET['borough'] ?? 'ALL';
$yearStart = isset($_GET['year_start']) ? intval($_GET['year_start']) : 2020;
$yearEnd = isset($_GET['year_end']) ? intval($_GET['year_end']) : 2025;
if ($yearEnd < $yearStart) { $yearEnd = $yearStart; }

// License status in crashes where VRU was killed (from crash table)
$sql_license = "
    SELECT
        fv.driver_license_status,
        COUNT(*) AS total,
        ROUND(COUNT(*)::numeric / SUM(COUNT(*)) OVER () * 100, 1) AS pct
    FROM fact_vehicles fv
    JOIN fact_collisions fc ON fv.collision_id = fc.collision_id
    JOIN location_collision lc ON fc.location_id = lc.location_id
    JOIN time_collision tc ON fc.time_id = tc.time_id
    WHERE ($1 = 'ALL' OR lc.borough = $1)
      AND tc.col_year BETWEEN $2 AND $3
      AND (fc.pedestrians_killed + fc.cyclists_killed) > 0
      AND fv.driver_license_status NOT IN ('Unknown', '-', '')
      AND fv.driver_license_status IS NOT NULL
    GROUP BY fv.driver_license_status
    ORDER BY total DESC
";

// Pre-collision action in crashes where VRU was killed
$sql_precollision = "
    SELECT
        fv.pre_collision,
        COUNT(*) AS total,
        ROUND(COUNT(*)::numeric / SUM(COUNT(*)) OVER () * 100, 1) AS pct
    FROM fact_vehicles fv
    JOIN fact_collisions fc ON fv.collision_id = fc.collision_id
    JOIN location_collision lc ON fc.location_id = lc.location_id
    JOIN time_collision tc ON fc.time_id = tc.time_id
    WHERE ($1 = 'ALL' OR lc.borough = $1)
      AND tc.col_year BETWEEN $2 AND $3
      AND (fc.pedestrians_killed + fc.cyclists_killed) > 0
      AND fv.pre_collision NOT IN ('Unknown', '-', '')
      AND fv.pre_collision IS NOT NULL
    GROUP BY fv.pre_collision
    ORDER BY total DESC
    LIMIT 10
";

$r1 = pg_query_params($conn, $sql_license, [$borough, $yearStart, $yearEnd]);
$r2 = pg_query_params($conn, $sql_precollision, [$borough, $yearStart, $yearEnd]);

echo json_encode([
    'license_status' => pg_fetch_all($r1) ?: [],
    'pre_collision'  => pg_fetch_all($r2) ?: [],
]);
pg_close($conn);