"""
fetch_weather.py
Fetches historical hourly weather for Parque Ibirapuera, São Paulo
from the Open-Meteo archive API and writes data/weather.csv.

Sampled hours: 0, 3, 6, 9, 12, 15, 18, 21  (8 per day)
Date range:    2025-11-01 → 2026-06-08
"""

import csv
import urllib.request
import urllib.parse
import json
import os
from datetime import datetime, timezone

# ── Config ────────────────────────────────────────────────────────────────────
LAT = -23.588333
LON = -46.658890
START_DATE = "2025-11-01"
END_DATE = "2026-06-08"
SAMPLED_HOURS = {0, 3, 6, 9, 12, 15, 18, 21}
OUT_FILE = os.path.join(os.path.dirname(__file__), "data", "weather.csv")
CSV_SEPARATOR = ";"

# ── API request ───────────────────────────────────────────────────────────────
def fetch_raw() -> dict:
    params = {
        "latitude": LAT,
        "longitude": LON,
        "start_date": START_DATE,
        "end_date": END_DATE,
        "hourly": "temperature_2m,apparent_temperature,relative_humidity_2m,precipitation",
        "timezone": "America/Sao_Paulo",
    }
    url = "https://archive-api.open-meteo.com/v1/archive?" + urllib.parse.urlencode(params)
    print(f"Requesting: {url}")
    with urllib.request.urlopen(url, timeout=30) as resp:
        return json.loads(resp.read())

# ── Filter & write ─────────────────────────────────────────────────────────────
def build_csv(raw: dict) -> None:
    hourly = raw["hourly"]
    times            = hourly["time"]
    temperatures     = hourly["temperature_2m"]
    feels_like       = hourly["apparent_temperature"]
    humidity         = hourly["relative_humidity_2m"]
    precipitation    = hourly["precipitation"]

    os.makedirs(os.path.dirname(OUT_FILE), exist_ok=True)

    rows_written = 0
    with open(OUT_FILE, "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f, delimiter=CSV_SEPARATOR)
        writer.writerow([
            "date_time", "latitude", "longitude",
            "temperatura", "sensacao_termica", "umidade_relativa", "chuva_mm"
        ])
        for i, ts in enumerate(times):
            dt = datetime.fromisoformat(ts)
            if dt.hour not in SAMPLED_HOURS:
                continue
            writer.writerow([
                ts, LAT, LON,
                temperatures[i],
                feels_like[i],
                humidity[i],
                precipitation[i],
            ])
            rows_written += 1

    print(f"Wrote {rows_written} rows to {OUT_FILE}")

# ── Validate ──────────────────────────────────────────────────────────────────
def validate_csv() -> None:
    with open(OUT_FILE, encoding="utf-8") as f:
        reader = csv.DictReader(f, delimiter=CSV_SEPARATOR)
        rows = list(reader)

    # Expect 8 months × ~30 days × 8 samples ≈ ≤ 1984 rows
    print(f"\nValidation")
    print(f"  Total rows  : {len(rows)}")

    temps = [float(r["temperatura"]) for r in rows if r["temperatura"]]
    print(f"  Temp range  : {min(temps):.1f}°C – {max(temps):.1f}°C")

    months = sorted({r["date_time"][:7] for r in rows})
    print(f"  Months found: {months}")

    hours = sorted({datetime.fromisoformat(r["date_time"]).hour for r in rows})
    print(f"  Hours found : {hours}")

    assert hours == sorted(SAMPLED_HOURS), f"Unexpected hours: {hours}"
    assert len(months) == 8, f"Expected 8 months, got {len(months)}: {months}"
    print("  OK All checks passed")

# ── Main ──────────────────────────────────────────────────────────────────────
if __name__ == "__main__":
    raw = fetch_raw()
    build_csv(raw)
    validate_csv()
