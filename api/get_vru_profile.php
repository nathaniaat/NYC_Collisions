<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$conn = pg_connect("host=localhost port=5434 dbname=collision user=postgres password=password");
if (!$conn) { echo json_encode(['error' => 'DB connection failed']); exit; }

$borough = $_GET['borough'] ?? 'ALL';
$yearStart = isset($_GET['year_start']) ? intval($_GET['year_start']) : 2020;
$yearEnd = isset($_GET['year_end']) ? intval($_GET['year_end']) : 2025;
if ($yearEnd < $yearStart) { $yearEnd = $yearStart; }

// Age group of VRU victims (injured - killed not in person table)
$sql_age = "
    SELECT
        fp.age_group,
        COUNT(*) AS total,
        ROUND(COUNT(*)::numeric / SUM(COUNT(*)) OVER () * 100, 1) AS pct
    FROM fact_people fp
    JOIN fact_collisions fc ON fp.collision_id = fc.collision_id
    JOIN location_collision lc ON fc.location_id = lc.location_id
    JOIN time_collision tc ON fc.time_id = tc.time_id
    WHERE fp.person_injury = 'Injured'
      AND fp.person_type IN ('Pedestrian', 'Bicyclist')
      AND ($1 = 'ALL' OR lc.borough = $1)
      AND tc.col_year BETWEEN $2 AND $3
      AND fp.age_group NOT IN ('Unknown', '-', '')
      AND fp.age_group IS NOT NULL
    GROUP BY fp.age_group
    ORDER BY
        CASE fp.age_group
            WHEN 'Under 18' THEN 1
            WHEN '18-24'    THEN 2
            WHEN '25-34'    THEN 3
            WHEN '35-44'    THEN 4
            WHEN '45-54'    THEN 5
            WHEN '55-64'    THEN 6
            WHEN '65+'      THEN 7
            ELSE 8
        END
";

// Bodily injury location of VRU victims
$sql_injury = "
    SELECT
        fp.bodily_injury,
        COUNT(*) AS total,
        ROUND(COUNT(*)::numeric / SUM(COUNT(*)) OVER () * 100, 1) AS pct
    FROM fact_people fp
    JOIN fact_collisions fc ON fp.collision_id = fc.collision_id
    JOIN location_collision lc ON fc.location_id = lc.location_id
    JOIN time_collision tc ON fc.time_id = tc.time_id
    WHERE fp.person_injury = 'Injured'
      AND fp.person_type IN ('Pedestrian', 'Bicyclist')
      AND ($1 = 'ALL' OR lc.borough = $1)
      AND tc.col_year BETWEEN $2 AND $3
      AND fp.bodily_injury IS NOT NULL
      AND fp.bodily_injury NOT IN ('Unknown', '-', 'Does Not Apply', 'None', '')
    GROUP BY fp.bodily_injury
    ORDER BY total DESC
    LIMIT 10
";

// Safety equipment of VRU victims
$sql_safety = "
    SELECT
        COALESCE(fp.safety_equipment, 'None/Unknown') AS safety_equipment,
        COUNT(*) AS total,
        ROUND(COUNT(*)::numeric / SUM(COUNT(*)) OVER () * 100, 1) AS pct
    FROM fact_people fp
    JOIN fact_collisions fc ON fp.collision_id = fc.collision_id
    JOIN location_collision lc ON fc.location_id = lc.location_id
    JOIN time_collision tc ON fc.time_id = tc.time_id
    WHERE fp.person_injury = 'Injured'
      AND fp.person_type IN ('Pedestrian', 'Bicyclist')
      AND ($1 = 'ALL' OR lc.borough = $1)
      AND tc.col_year BETWEEN $2 AND $3
    GROUP BY COALESCE(fp.safety_equipment, 'None/Unknown')
    ORDER BY total DESC
    LIMIT 8
";

$r1 = pg_query_params($conn, $sql_age, [$borough, $yearStart, $yearEnd]);
$r2 = pg_query_params($conn, $sql_injury, [$borough, $yearStart, $yearEnd]);
$r3 = pg_query_params($conn, $sql_safety, [$borough, $yearStart, $yearEnd]);

echo json_encode([
    'age_group'        => pg_fetch_all($r1) ?: [],
    'bodily_injury'    => pg_fetch_all($r2) ?: [],
    'safety_equipment' => pg_fetch_all($r3) ?: [],
]);
pg_close($conn);