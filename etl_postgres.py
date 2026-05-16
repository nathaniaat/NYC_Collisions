import pandas as pd
import psycopg2
from psycopg2.extras import execute_values

# ── Connection ──
conn = psycopg2.connect(
    host="localhost", port=5433,
    database="nyc_collisions",
    user="postgres", password="admin"
)
cur = conn.cursor()

print("Loading CSVs...")
# Note: Ensure these paths match your folder structure (e.g., 'data/crash_clean.csv')
crash_df   = pd.read_csv("data/crash_clean.csv", low_memory=False)
vehicle_df = pd.read_csv("data/vehicle_clean.csv", low_memory=False)
person_df  = pd.read_csv("data/person_clean.csv", low_memory=False)

# 1. factor_collision
print("Inserting factor_collision...")
factors = crash_df["CONTRIBUTING FACTOR VEHICLE 1"].dropna().unique()
factor_rows = [(f,) for f in sorted(factors)]
execute_values(cur, "INSERT INTO factor_collision (factor_name) VALUES %s ON CONFLICT DO NOTHING", factor_rows)
conn.commit()

cur.execute("SELECT factor_name, factor_id FROM factor_collision")
factor_map = {row[0]: row[1] for row in cur.fetchall()}

# 2. time_collision
print("Inserting time_collision...")
crash_df["CRASH DATE"] = pd.to_datetime(crash_df["CRASH DATE"], errors="coerce")
time_df = crash_df[["CRASH DATE","CRASH TIME","CRASH HOUR","CRASH MONTH","CRASH YEAR"]].drop_duplicates(subset=["CRASH HOUR","CRASH MONTH","CRASH YEAR"]).copy()

time_rows = []
for _, r in time_df.iterrows():
    dt = r["CRASH DATE"]
    time_rows.append((
        str(dt.date()) if pd.notna(dt) else None,
        str(r["CRASH TIME"])[:5] if pd.notna(r["CRASH TIME"]) else None,
        int(r["CRASH HOUR"]), int(r["CRASH MONTH"]), int(r["CRASH YEAR"]),
        dt.day_name() if pd.notna(dt) else None,
        bool(dt.dayofweek >= 5) if pd.notna(dt) else False,
        int(r["CRASH HOUR"]) in [7,8,9,16,17,18]
    ))

execute_values(cur, """INSERT INTO time_collision (col_date, col_time, col_hour, col_month, col_year, day_of_week, is_weekend, is_rushour) VALUES %s""", time_rows)
conn.commit()

cur.execute("SELECT time_id, col_hour, col_month, col_year FROM time_collision")
time_map = {(r[1], r[2], r[3]): r[0] for r in cur.fetchall()}

# 3. location_collision
print("Inserting location_collision...")
loc_df = crash_df[["BOROUGH","ZIP CODE","LATITUDE","LONGITUDE"]].drop_duplicates(subset=["LATITUDE","LONGITUDE"]).copy()
loc_rows = []
for _, r in loc_df.iterrows():
    loc_rows.append((
        str(r["BOROUGH"]) if pd.notna(r["BOROUGH"]) else "Unknown",
        str(int(float(r["ZIP CODE"]))) if pd.notna(r["ZIP CODE"]) else None,
        float(r["LATITUDE"]) if pd.notna(r["LATITUDE"]) else None,
        float(r["LONGITUDE"]) if pd.notna(r["LONGITUDE"]) else None
    ))
execute_values(cur, "INSERT INTO location_collision (borough, zip_code, latitude, longitude) VALUES %s", loc_rows)
conn.commit()

cur.execute("SELECT location_id, latitude, longitude FROM location_collision")
loc_map = {(r[1], r[2]): r[0] for r in cur.fetchall()}

# 4. fact_collisions
print("Inserting fact_collisions...")
collision_rows = []
for _, r in crash_df.iterrows():
    tk = (int(r["CRASH HOUR"]), int(r["CRASH MONTH"]), int(r["CRASH YEAR"]))
    lk = (r["LATITUDE"], r["LONGITUDE"])
    collision_rows.append((
        int(r["COLLISION_ID"]), time_map.get(tk), loc_map.get(lk),
        int(r["NUMBER OF PERSONS INJURED"]), int(r["NUMBER OF PERSONS KILLED"]),
        int(r["NUMBER OF PEDESTRIANS INJURED"]), int(r["NUMBER OF PEDESTRIANS KILLED"]),
        int(r["NUMBER OF CYCLIST INJURED"]), int(r["NUMBER OF CYCLIST KILLED"]),
        int(r["NUMBER OF MOTORIST INJURED"]), int(r["NUMBER OF MOTORIST KILLED"]),
        bool(r["IS_FATAL"])
    ))
execute_values(cur, """INSERT INTO fact_collisions (collision_id, time_id, location_id, people_injured, people_killed, pedestrians_injured, pedestrians_killed, cyclists_injured, cyclists_killed, motorists_injured, motorists_killed, is_fatal) VALUES %s ON CONFLICT DO NOTHING""", collision_rows)
conn.commit()

# 5. fact_vehicles
print("Inserting fact_vehicles...")
valid_ids = set(crash_df["COLLISION_ID"])
veh_df = vehicle_df[vehicle_df["COLLISION_ID"].isin(valid_ids)].copy()
veh_rows = []
for _, r in veh_df.iterrows():
    fid = factor_map.get(r.get("CONTRIBUTING_FACTOR_1"))
    veh_rows.append((
        int(r["UNIQUE_ID"]), int(r["COLLISION_ID"]), str(r.get("VEHICLE_ID")),
        str(r.get("STATE_REGISTRATION")), str(r.get("VEHICLE_TYPE_CLEAN")), str(r.get("VEHICLE_MAKE")),
        int(r["VEHICLE_YEAR"]) if pd.notna(r.get("VEHICLE_YEAR")) else 0,
        int(r["VEHICLE_OCCUPANTS"]) if pd.notna(r.get("VEHICLE_OCCUPANTS")) else 0,
        None, str(r.get("DRIVER_LICENSE_STATUS")), str(r.get("PRE_CRASH")),
        str(r.get("POINT_OF_IMPACT")), str(r.get("VEHICLE_DAMAGE")), fid
    ))
execute_values(cur, """INSERT INTO fact_vehicles (unique_id, collision_id, vehicle_id, state_registration, vehicle_type, vehicle_make, vehicle_year, vehicle_occupants, driver_sex, driver_license_status, pre_collision, point_of_impact, vehicle_damage, factor_id) VALUES %s ON CONFLICT DO NOTHING""", veh_rows)
conn.commit()

# 6. fact_people (Corrected Column List)
print("Inserting fact_people...")
ppl_df = person_df[person_df["COLLISION_ID"].isin(valid_ids)].copy()
ppl_rows = []
for _, r in ppl_df.iterrows():
    ppl_rows.append((
        int(r["UNIQUE_ID"]),
        int(r["COLLISION_ID"]),
        str(r["PERSON_ID"]),
        str(r["PERSON_TYPE"]),
        str(r.get("PED_ROLE", "Unknown")),
        str(r.get("PERSON_SEX", "U")),
        int(r["PERSON_AGE"]) if pd.notna(r.get("PERSON_AGE")) else None,
        str(r.get("PERSON_INJURY", "Unspecified")),
        str(r.get("VEHICLE_ID")),
        str(r.get("EJECTION", "Not Ejected")),
        str(r.get("BODILY_INJURY", "None")),
        str(r.get("POSITION_IN_VEHICLE", "Unknown")),
        str(r.get("SAFETY_EQUIPMENT", "None")),
        str(r.get("AGE_GROUP", "Unknown")),
    ))

# This SQL string MUST match the 14 items in the ppl_rows tuple above
execute_values(cur, """
    INSERT INTO fact_people (
        unique_id, collision_id, person_id, person_type, person_role, 
        person_sex, person_age, person_injury, vehicle_id, ejection, 
        bodily_injury, position_in_vehicle, safety_equipment, age_group
    ) VALUES %s ON CONFLICT DO NOTHING""", ppl_rows)

conn.commit()
cur.close()
conn.close()
print("\n✓ PostgreSQL ETL complete!")