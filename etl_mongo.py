import pandas as pd
from pymongo import MongoClient, InsertOne
from pymongo.errors import BulkWriteError

client = MongoClient("mongodb://nath:150206@localhost:27017/")
db = client["nyc_collisions"]

print("Loading CSVs...")
crash_df   = pd.read_csv("data/crash_final.csv",   low_memory=False)
vehicle_df = pd.read_csv("data/vehicle_final.csv", low_memory=False)
person_df  = pd.read_csv("data/person_final.csv",  low_memory=False)

# ─────────────────────────────────────────────────────────────
# Normalize COLLISION_ID across all 3 tables
# ─────────────────────────────────────────────────────────────
for df in [crash_df, vehicle_df, person_df]:
    df["COLLISION_ID"] = pd.to_numeric(
        df["COLLISION_ID"], errors="coerce"
    ).astype("Int64")

# Stats
crash_ids   = set(crash_df["COLLISION_ID"].dropna())
vehicle_ids = set(vehicle_df["COLLISION_ID"].dropna())
person_ids  = set(person_df["COLLISION_ID"].dropna())
common_ids  = crash_ids & vehicle_ids & person_ids

print(f"  Total crash IDs       : {len(crash_ids):,}")
print(f"  With vehicle data     : {len(crash_ids & vehicle_ids):,}")
print(f"  With person data      : {len(crash_ids & person_ids):,}")
print(f"  With both (old logic) : {len(common_ids):,}")
print(f"  → Now inserting ALL   : {len(crash_ids):,}  ✓")

# ─────────────────────────────────────────────────────────────
# Index vehicle and person for fast lookup
# ─────────────────────────────────────────────────────────────
veh_grouped = vehicle_df.groupby("COLLISION_ID")
ppl_grouped = person_df.groupby("COLLISION_ID")

# ─────────────────────────────────────────────────────────────
# Helper — safe value extraction
# ─────────────────────────────────────────────────────────────
def safe_int(val, default=0):
    try:
        return int(val) if pd.notna(val) else default
    except:
        return default

def safe_float(val, default=None):
    try:
        return float(val) if pd.notna(val) else default
    except:
        return default

def safe_str(val, default="-"):
    return str(val).strip() if pd.notna(val) and str(val).strip() not in ["", "nan", "None"] else default

# ─────────────────────────────────────────────────────────────
# Collection 1: crash_events
# ALL crashes — vehicles[] and persons[] empty if no match
# ─────────────────────────────────────────────────────────────
print("\nBuilding crash_events collection...")
db.crash_events.drop()

BATCH = 1000
batch = []
count = 0
count_with_vehicles = 0
count_with_persons  = 0

for _, row in crash_df.iterrows():
    cid = int(row["COLLISION_ID"])

    # ── Vehicles array (empty [] if no vehicle data for this crash) ──
    vehicles = []
    if cid in veh_grouped.groups:
        for _, v in veh_grouped.get_group(cid).iterrows():
            vehicles.append({
                "unique_id":             safe_int(v.get("UNIQUE_ID")),
                "vehicle_id":            safe_str(v.get("VEHICLE_ID")),
                "vehicle_type":          safe_str(v.get("VEHICLE_TYPE_CLEAN")),
                "vehicle_make":          safe_str(v.get("VEHICLE_MAKE")),
                "vehicle_year":          safe_int(v.get("VEHICLE_YEAR")),
                "state_registration":    safe_str(v.get("STATE_REGISTRATION")),
                "vehicle_occupants":     safe_int(v.get("VEHICLE_OCCUPANTS")),
                "driver_license_status": safe_str(v.get("DRIVER_LICENSE_STATUS")),
                "pre_collision":         safe_str(v.get("PRE_CRASH")),
                "point_of_impact":       safe_str(v.get("POINT_OF_IMPACT")),
                "vehicle_damage":        safe_str(v.get("VEHICLE_DAMAGE")),
                "contributing_factor":   safe_str(v.get("CONTRIBUTING_FACTOR_1")),
            })
        count_with_vehicles += 1

    # ── Persons array (empty [] if no person data for this crash) ──
    persons = []
    if cid in ppl_grouped.groups:
        for _, p in ppl_grouped.get_group(cid).iterrows():
            persons.append({
                "unique_id":        safe_int(p.get("UNIQUE_ID")),
                "person_id":        safe_str(p.get("PERSON_ID")),
                "person_type":      safe_str(p.get("PERSON_TYPE")),
                "person_role":      safe_str(p.get("PED_ROLE")),
                "person_sex":       safe_str(p.get("PERSON_SEX")),
                "person_age":       safe_int(p.get("PERSON_AGE"), default=None),
                "age_group":        safe_str(p.get("AGE_GROUP")),
                "person_injury":    safe_str(p.get("PERSON_INJURY")),
                "ejection":         safe_str(p.get("EJECTION")),
                "bodily_injury":    safe_str(p.get("BODILY_INJURY")),
                "safety_equipment": safe_str(p.get("SAFETY_EQUIPMENT")),
            })
        count_with_persons += 1

    # ── Crash document ──
    doc = {
        "collision_id":        cid,
        "crash_date":          safe_str(row.get("CRASH DATE")),
        "crash_hour":          safe_int(row.get("CRASH HOUR")),
        "crash_month":         safe_int(row.get("CRASH MONTH")),
        "borough":             safe_str(row.get("BOROUGH")),
        "latitude":            safe_float(row.get("LATITUDE")),
        "longitude":           safe_float(row.get("LONGITUDE")),
        "is_fatal":            bool(safe_int(row.get("IS_FATAL"))),
        "contributing_factor": safe_str(row.get("CONTRIBUTING FACTOR VEHICLE 1")),
        "people_killed":       safe_int(row.get("NUMBER OF PERSONS KILLED")),
        "people_injured":      safe_int(row.get("NUMBER OF PERSONS INJURED")),
        "pedestrians_killed":  safe_int(row.get("NUMBER OF PEDESTRIANS KILLED")),
        "cyclists_killed":     safe_int(row.get("NUMBER OF CYCLIST KILLED")),

        # ← KEY CHANGE: always present, empty array if no data
        "vehicles":            vehicles,
        "persons":             persons,

        # ← Metadata flag so frontend knows what's available
        "has_vehicle_detail":  len(vehicles) > 0,
        "has_person_detail":   len(persons) > 0,
    }

    batch.append(InsertOne(doc))
    count += 1

    if len(batch) >= BATCH:
        try:
            db.crash_events.bulk_write(batch, ordered=False)
        except BulkWriteError as e:
            print(f"\n  ⚠ BulkWriteError at {count:,}: {e.details['nInserted']} inserted")
        batch = []
        print(f"  Inserted {count:,} / {len(crash_df):,} documents...", end="\r")

# Final batch
if batch:
    db.crash_events.bulk_write(batch, ordered=False)

print(f"\n  ✓ crash_events: {count:,} documents total")
print(f"    with vehicle detail : {count_with_vehicles:,}")
print(f"    with person detail  : {count_with_persons:,}")
print(f"    crash-only (no join): {count - max(count_with_vehicles, count_with_persons):,}")

# Indexes
db.crash_events.create_index("collision_id", unique=True)
db.crash_events.create_index("borough")
db.crash_events.create_index([("crash_hour", 1), ("borough", 1)])
db.crash_events.create_index("is_fatal")
db.crash_events.create_index("has_vehicle_detail")
db.crash_events.create_index("has_person_detail")
print("  ✓ Indexes created on crash_events")

# ─────────────────────────────────────────────────────────────
# Collection 2: vru_borough_hour
# 5 boroughs × 24 hours = 120 documents (unchanged)
# ─────────────────────────────────────────────────────────────
print("\nBuilding vru_borough_hour collection...")
db.vru_borough_hour.drop()

vru_rows = crash_df[crash_df["BOROUGH"] != "Unknown"].groupby(
    ["BOROUGH", "CRASH HOUR"]
).agg(
    total_crashes        = ("COLLISION_ID", "count"),
    pedestrians_killed   = ("NUMBER OF PEDESTRIANS KILLED", "sum"),
    pedestrians_injured  = ("NUMBER OF PEDESTRIANS INJURED", "sum"),
    cyclists_killed      = ("NUMBER OF CYCLIST KILLED", "sum"),
    cyclists_injured     = ("NUMBER OF CYCLIST INJURED", "sum"),
).reset_index()

vru_docs = []
for _, r in vru_rows.iterrows():
    total_vru_killed  = int(r["pedestrians_killed"]) + int(r["cyclists_killed"])
    total_vru_injured = int(r["pedestrians_injured"]) + int(r["cyclists_injured"])
    vru_docs.append({
        "borough":             str(r["BOROUGH"]),
        "crash_hour":          int(r["CRASH HOUR"]),
        "total_crashes":       int(r["total_crashes"]),
        "pedestrians_killed":  int(r["pedestrians_killed"]),
        "pedestrians_injured": int(r["pedestrians_injured"]),
        "cyclists_killed":     int(r["cyclists_killed"]),
        "cyclists_injured":    int(r["cyclists_injured"]),
        "total_vru_killed":    total_vru_killed,
        "total_vru_injured":   total_vru_injured,
    })

db.vru_borough_hour.insert_many(vru_docs)
db.vru_borough_hour.create_index([("borough", 1), ("crash_hour", 1)], unique=True)
print(f"  ✓ vru_borough_hour: {len(vru_docs)} documents inserted")

print("\n✓ MongoDB ETL complete!")