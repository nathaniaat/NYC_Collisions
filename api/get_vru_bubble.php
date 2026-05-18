<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$conn = pg_connect("host=localhost port=5434 dbname=collision user=postgres password=password");
if (!$conn) { echo json_encode(['error' => 'DB connection failed']); exit; }

$borough = $_GET['borough'] ?? 'ALL';
$yearStart = isset($_GET['year_start']) ? intval($_GET['year_start']) : 2020;
$yearEnd = isset($_GET['year_end']) ? intval($_GET['year_end']) : 2025;
if ($yearEnd < $yearStart) { $yearEnd = $yearStart; }

// VRU kill rate per vehicle type
// Uses crash table for killed counts (person_injury='Killed' does not exist in data)
// person_type filter used for injured counts from fact_people
$sql = "
    SELECT
        fv.vehicle_type,
        COUNT(DISTINCT fv.collision_id)            AS crash_involving,
        SUM(fc.pedestrians_killed + fc.cyclists_killed) AS vru_killed,
        SUM(fc.pedestrians_injured + fc.cyclists_injured) AS vru_injured,
        ROUND(
            SUM(fc.pedestrians_killed + fc.cyclists_killed)::numeric
            / NULLIF(COUNT(DISTINCT fv.collision_id), 0) * 1000
        , 2) AS vru_kill_rate_per_1000
    FROM fact_vehicles fv
    JOIN fact_collisions fc ON fv.collision_id = fc.collision_id
    JOIN location_collision lc ON fc.location_id = lc.location_id
    JOIN time_collision tc ON fc.time_id = tc.time_id
    WHERE ($1 = 'ALL' OR lc.borough = $1)
      AND tc.col_year BETWEEN $2 AND $3
      AND fv.vehicle_type IS NOT NULL
      AND fv.vehicle_type NOT IN ('Unknown', '-', '')
    GROUP BY fv.vehicle_type
    HAVING COUNT(DISTINCT fv.collision_id) >= 50
    ORDER BY vru_kill_rate_per_1000 DESC
    LIMIT 20
";

$result = pg_query_params($conn, $sql, [$borough, $yearStart, $yearEnd]);
echo json_encode(pg_fetch_all($result) ?: []);
pg_close($conn);