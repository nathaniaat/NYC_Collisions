import pandas as pd
from neo4j import GraphDatabase

driver = GraphDatabase.driver(
    "bolt://localhost:7687",
    auth=("neo4j", "neo4jneo4j")
)

print("Loading crash data...")
crash_df   = pd.read_csv("data/crash_final.csv",   low_memory=False)
vehicle_df = pd.read_csv("data/vehicle_final.csv", low_memory=False)

# ─────────────────────────────────────────
# Step 4A: Create constraints & indexes
# ─────────────────────────────────────────
with driver.session() as s:
    s.run("CREATE CONSTRAINT IF NOT EXISTS FOR (f:Factor)  REQUIRE f.factor_id IS UNIQUE")
    s.run("CREATE CONSTRAINT IF NOT EXISTS FOR (c:Crash)   REQUIRE c.collision_id IS UNIQUE")
    s.run("CREATE CONSTRAINT IF NOT EXISTS FOR (o:Outcome) REQUIRE o.label IS UNIQUE")
    print("✓ Constraints created")

# ─────────────────────────────────────────
# Step 4B: Create 14 Factor nodes
# ─────────────────────────────────────────
factors = crash_df["CONTRIBUTING FACTOR VEHICLE 1"].unique().tolist()
FACTOR_CATEGORY = {
    "Driver Inattention/Distraction":           "Human Error",
    "Failure to Yield Right-of-Way":            "Human Error",
    "Following Too Closely":                    "Human Error",
    "Backing Unsafely":                         "Human Error",
    "Passing Too Closely":                      "Human Error",
    "Unsafe Speed":                             "Human Error",
    "Traffic Control Disregarded":              "Human Error",
    "Turning Improperly":                       "Human Error",
    "Unsafe Lane Changing":                     "Human Error",
    "Passing or Lane Usage Improper":           "Human Error",
    "Reaction to Uninvolved Vehicle":           "Environmental",
    "Pedestrian/Bicyclist/Other Pedestrian Error/Confusion": "Human Error",
    "Other Vehicular":                          "Mechanical",
    "Unspecified":                              "Unspecified",
}

with driver.session() as s:
    for i, fname in enumerate(sorted(factors)):
        s.run("""
            MERGE (f:Factor {factor_id: $fid})
            SET f.factor_name     = $name,
                f.factor_category = $cat
        """, fid=i+1, name=fname, cat=FACTOR_CATEGORY.get(fname, "Other"))
    print(f"✓ {len(factors)} Factor nodes created")

# ─────────────────────────────────────────
# Step 4C: Create 2 Outcome nodes
# ─────────────────────────────────────────
with driver.session() as s:
    s.run("MERGE (:Outcome {label: 'Fatal'})")
    s.run("MERGE (:Outcome {label: 'NonFatal'})")
    print("✓ 2 Outcome nodes created")

# ─────────────────────────────────────────
# Step 4D: Create Crash nodes + RESULTED_IN
# Done in batches of 1000
# ─────────────────────────────────────────
print("Creating Crash nodes + RESULTED_IN relationships...")
BATCH = 1000
crash_batch = []

for _, row in crash_df.iterrows():
    crash_batch.append({
        "collision_id": int(row["COLLISION_ID"]),
        "is_fatal":     bool(row["IS_FATAL"]),
        "borough":      str(row["BOROUGH"]),
        "crash_hour":   int(row["CRASH HOUR"]),
        "crash_month":  int(row["CRASH MONTH"]),
        "outcome":      "Fatal" if row["IS_FATAL"] else "NonFatal"
    })

    if len(crash_batch) >= BATCH:
        with driver.session() as s:
            s.run("""
                UNWIND $rows AS row
                MERGE (c:Crash {collision_id: row.collision_id})
                SET c.is_fatal    = row.is_fatal,
                    c.borough     = row.borough,
                    c.crash_hour  = row.crash_hour,
                    c.crash_month = row.crash_month
                WITH c, row
                MATCH (o:Outcome {label: row.outcome})
                MERGE (c)-[:RESULTED_IN]->(o)
            """, rows=crash_batch)
        crash_batch = []
        print(f"  Processed up to collision_id {int(row['COLLISION_ID'])}...", end="\r")

if crash_batch:
    with driver.session() as s:
        s.run("""
            UNWIND $rows AS row
            MERGE (c:Crash {collision_id: row.collision_id})
            SET c.is_fatal = row.is_fatal, c.borough = row.borough,
                c.crash_hour = row.crash_hour, c.crash_month = row.crash_month
            WITH c, row
            MATCH (o:Outcome {label: row.outcome})
            MERGE (c)-[:RESULTED_IN]->(o)
        """, rows=crash_batch)

print(f"\n✓ {len(crash_df):,} Crash nodes + RESULTED_IN created")

# ─────────────────────────────────────────
# Step 4E: Create INVOLVED_IN relationships
# (Factor) -[:INVOLVED_IN]-> (Crash)
# From crash table: CONTRIBUTING FACTOR VEHICLE 1
# ─────────────────────────────────────────
print("Creating INVOLVED_IN relationships...")
involved_batch = []

for _, row in crash_df.iterrows():
    fname = row["CONTRIBUTING FACTOR VEHICLE 1"]
    if pd.notna(fname) and fname != "Unspecified":
        involved_batch.append({
            "factor_name":  str(fname),
            "collision_id": int(row["COLLISION_ID"])
        })

    if len(involved_batch) >= BATCH:
        with driver.session() as s:
            s.run("""
                UNWIND $rows AS row
                MATCH (f:Factor {factor_name: row.factor_name})
                MATCH (c:Crash  {collision_id: row.collision_id})
                MERGE (f)-[:INVOLVED_IN]->(c)
            """, rows=involved_batch)
        involved_batch = []

if involved_batch:
    with driver.session() as s:
        s.run("""
            UNWIND $rows AS row
            MATCH (f:Factor {factor_name: row.factor_name})
            MATCH (c:Crash  {collision_id: row.collision_id})
            MERGE (f)-[:INVOLVED_IN]->(c)
        """, rows=involved_batch)

print(f"✓ INVOLVED_IN relationships created")

# Also add from vehicle table (factor per vehicle)
print("Adding INVOLVED_IN from vehicle table...")
veh_involved = []
valid_crash_ids = set(crash_df["COLLISION_ID"].astype(int))

for _, row in vehicle_df.iterrows():
    cid = int(row["COLLISION_ID"])
    fname = row.get("CONTRIBUTING_FACTOR_1", None)
    if cid in valid_crash_ids and pd.notna(fname) and fname not in ("Unspecified", "Unknown", "Other"):
        veh_involved.append({
            "factor_name":  str(fname),
            "collision_id": cid
        })

    if len(veh_involved) >= BATCH:
        with driver.session() as s:
            s.run("""
                UNWIND $rows AS row
                MATCH (f:Factor {factor_name: row.factor_name})
                MATCH (c:Crash  {collision_id: row.collision_id})
                MERGE (f)-[:INVOLVED_IN]->(c)
            """, rows=veh_involved)
        veh_involved = []

if veh_involved:
    with driver.session() as s:
        s.run("""
            UNWIND $rows AS row
            MATCH (f:Factor {factor_name: row.factor_name})
            MATCH (c:Crash  {collision_id: row.collision_id})
            MERGE (f)-[:INVOLVED_IN]->(c)
        """, rows=veh_involved)

print("✓ INVOLVED_IN from vehicle table added")

# ─────────────────────────────────────────
# Step 4F: Build CO_OCCURS_WITH
# This is the key relationship for P2
# ─────────────────────────────────────────
print("\nBuilding CO_OCCURS_WITH relationships (this takes a few minutes)...")
with driver.session() as s:
    s.run("""
        MATCH (f1:Factor)-[:INVOLVED_IN]->(c:Crash)<-[:INVOLVED_IN]-(f2:Factor)
        WHERE f1.factor_id < f2.factor_id
        WITH f1, f2,
             COUNT(c)                             AS co_count,
             SUM(CASE WHEN c.is_fatal THEN 1 ELSE 0 END) AS fatal_count
        MERGE (f1)-[r:CO_OCCURS_WITH]-(f2)
        SET r.count       = co_count,
            r.fatal_count = fatal_count,
            r.fatal_rate  = CASE WHEN co_count > 0
                                 THEN toFloat(fatal_count) / co_count
                                 ELSE 0.0 END
    """)
    print("✓ CO_OCCURS_WITH relationships created")

driver.close()
print("\n✓ Neo4j ETL complete!")
print("Open Neo4j Browser → http://localhost:7474")
print("Run: MATCH (f:Factor)-[r:CO_OCCURS_WITH]-(f2:Factor) RETURN f,r,f2 LIMIT 50")