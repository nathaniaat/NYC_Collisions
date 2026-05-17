import pandas as pd
from pymongo import MongoClient, InsertOne
from pymongo.errors import BulkWriteError

client = MongoClient("mongodb://mongodb:mongodb@localhost:27017/")
db = client["pdds/collision"]

print("Loading CSVs...")
crash_df   = pd.read_csv("data/crash_final.csv",   low_memory=False)
vehicle_df = pd.read_csv("data/vehicle_final.csv", low_memory=False)
person_df  = pd.read_csv("data/person_final.csv",  low_memory=False)

# ─────────────────────────────────────────
# Collection 1: crash_events
# Only 85,512 collision_ids exist in all 3 tables
# ─────────────────────────────────────────
print("\nBuilding crash_events collection...")

# Find collision_ids present in all 3 tables
for df in [crash_df, vehicle_df, person_df]:
    df["COLLISION_ID"] = pd.to_numeric(df["COLLISION_ID"], errors="coerce").astype("Int64")

crash_ids   = set(crash_df["COLLISION_ID"].dropna())
vehicle_ids = set(vehicle_df["COLLISION_ID"].dropna())
person_ids  = set(person_df["COLLISION_ID"].dropna())
common_ids  = crash_ids & vehicle_ids & person_ids
print(f"  Common collision IDs: {len(common_ids):,}")

# Index for fast lookup
veh_grouped = vehicle_df.groupby("COLLISION_ID")
ppl_grouped = person_df.groupby("COLLISION_ID")

# Drop existing collection
db.crash_events.drop()

# Build documents in batches
BATCH = 1000
batch = []
count = 0

crash_sub = crash_df[crash_df["COLLISION_ID"].isin(common_ids)]

for _, row in crash_sub.iterrows():
    cid = int(row["COLLISION_ID"])

    # Build vehicles array
    vehicles = []
    if cid in veh_grouped.groups:
        for _, v in veh_grouped.get_group(cid).iterrows():
            vehicles.append({
                "unique_id":            int(v["UNIQUE_ID"]),
                "vehicle_id":           str(v["VEHICLE_ID"]),
                "vehicle_type":         str(v["VEHICLE_TYPE_CLEAN"]),
                "vehicle_make":         str(v["VEHICLE_MAKE"]),
                "vehicle_year":         int(v["VEHICLE_YEAR"]),
                "state_registration":   str(v["STATE_REGISTRATION"]),
                "vehicle_occupants":    int(v["VEHICLE_OCCUPANTS"]),
                "driver_license_status":str(v["DRIVER_LICENSE_STATUS"]),
                "pre_collision":        str(v["PRE_CRASH"]),
                "point_of_impact":      str(v["POINT_OF_IMPACT"]),
                "vehicle_damage":       str(v["VEHICLE_DAMAGE"]),
                "contributing_factor":  str(v["CONTRIBUTING_FACTOR_1"]),
            })

    # Build persons array
    persons = []
    if cid in ppl_grouped.groups:
        for _, p in ppl_grouped.get_group(cid).iterrows():
            persons.append({
                "unique_id":          int(p["UNIQUE_ID"]),
                "person_id":          str(p["PERSON_ID"]),
                "person_type":        str(p["PERSON_TYPE"]),
                "person_role":        str(p["PED_ROLE"]),
                "person_sex":         str(p["PERSON_SEX"]),
                "person_age":         int(p["PERSON_AGE"]) if pd.notna(p["PERSON_AGE"]) else None,
                "age_group":          str(p["AGE_GROUP"]),
                "person_injury":      str(p["PERSON_INJURY"]),
                "ejection":           str(p["EJECTION"]),
                "bodily_injury":      str(p["BODILY_INJURY"]) if pd.notna(p["BODILY_INJURY"]) else None,
                "safety_equipment":   str(p["SAFETY_EQUIPMENT"]) if pd.notna(p["SAFETY_EQUIPMENT"]) else None,
            })

    # Build crash document
    doc = {
        "collision_id":        cid,
        "crash_date":          str(row["CRASH DATE"]),
        "crash_hour":          int(row["CRASH HOUR"]),
        "crash_month":         int(row["CRASH MONTH"]),
        "borough":             str(row["BOROUGH"]),
        "latitude":            float(row["LATITUDE"]),
        "longitude":           float(row["LONGITUDE"]),
        "is_fatal":            bool(row["IS_FATAL"]),
        "contributing_factor": str(row["CONTRIBUTING FACTOR VEHICLE 1"]),
        "people_killed":       int(row["NUMBER OF PERSONS KILLED"]),
        "people_injured":      int(row["NUMBER OF PERSONS INJURED"]),
        "pedestrians_killed":  int(row["NUMBER OF PEDESTRIANS KILLED"]),
        "cyclists_killed":     int(row["NUMBER OF CYCLIST KILLED"]),
        "vehicles":            vehicles,
        "persons":             persons,
    }

    batch.append(InsertOne(doc))
    count += 1

    if len(batch) >= BATCH:
        db.crash_events.bulk_write(batch, ordered=False)
        batch = []
        print(f"  Inserted {count:,} documents...", end="\r")

if batch:
    db.crash_events.bulk_write(batch, ordered=False)

print(f"\n  ✓ crash_events: {count:,} documents inserted")

# Create indexes
db.crash_events.create_index("collision_id", unique=True)
db.crash_events.create_index("borough")
db.crash_events.create_index([("crash_hour", 1), ("borough", 1)])
db.crash_events.create_index("is_fatal")
print("  ✓ Indexes created on crash_events")

# ─────────────────────────────────────────
# Collection 2: vru_borough_hour
# 5 boroughs × 24 hours = 120 documents
# ─────────────────────────────────────────
print("\nBuilding vru_borough_hour collection...")
db.vru_borough_hour.drop()

vru_rows = crash_df[crash_df["BOROUGH"] != "Unknown"].groupby(
    ["BOROUGH", "CRASH HOUR"]
).agg(
    total_crashes         = ("COLLISION_ID", "count"),
    pedestrians_killed    = ("NUMBER OF PEDESTRIANS KILLED", "sum"),
    pedestrians_injured   = ("NUMBER OF PEDESTRIANS INJURED", "sum"),
    cyclists_killed       = ("NUMBER OF CYCLIST KILLED", "sum"),
    cyclists_injured      = ("NUMBER OF CYCLIST INJURED", "sum"),
).reset_index()

vru_docs = []
for _, r in vru_rows.iterrows():
    total_vru_killed   = int(r["pedestrians_killed"])  + int(r["cyclists_killed"])
    total_vru_injured  = int(r["pedestrians_injured"]) + int(r["cyclists_injured"])
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
db.vru_borough_hour.create_index([("borough", 1), ("crash_hour", 1)])
print(f"  ✓ vru_borough_hour: {len(vru_docs)} documents inserted")

print("\n✓ MongoDB ETL complete!")
print("Open MongoDB Compass → nyc_collisions → verify crash_events and vru_borough_hour")