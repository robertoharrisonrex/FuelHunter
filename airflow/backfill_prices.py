#!/usr/bin/env python3
"""
One-time backfill: copies prices from local SQLite into production PostgreSQL,
skipping any (site_id, fuel_id) pairs that already exist in production.

Usage:
    FUELDB_URL='postgresql+psycopg2://user:pass@host:25060/db?sslmode=require' \
        python airflow/backfill_prices.py
"""

import os
import sys
import sqlite3
import pandas as pd
from sqlalchemy import create_engine, text

SQLITE_PATH = os.path.join(os.path.dirname(__file__), '..', 'database', 'database.sqlite')
STAGING_TABLE = 'backfill_staging_prices'


def main():
    prod_url = os.environ.get('FUELDB_URL')
    if not prod_url:
        sys.exit(
            "ERROR: FUELDB_URL not set.\n"
            "Example: FUELDB_URL='postgresql+psycopg2://user:pass@host:25060/db?sslmode=require' "
            "python airflow/backfill_prices.py"
        )

    # ── 1. Read from local SQLite ─────────────────────────────────────────────
    print("Reading prices from local SQLite...")
    with sqlite3.connect(SQLITE_PATH) as sqlite_conn:
        df = pd.read_sql(
            "SELECT site_id, fuel_id, collection_method, "
            "       transaction_date_utc, price, created_at, updated_at "
            "FROM prices",
            sqlite_conn,
        )
    df['transaction_date_utc'] = pd.to_datetime(df['transaction_date_utc'], errors='coerce', format='ISO8601')
    df['created_at'] = pd.to_datetime(df['created_at'], errors='coerce', format='ISO8601')
    df['updated_at'] = pd.to_datetime(df['updated_at'], errors='coerce', format='ISO8601')
    null_dates = df['transaction_date_utc'].isna().sum()
    if null_dates:
        print(f"  WARNING: dropping {null_dates} rows with unparseable transaction_date_utc")
        df = df.dropna(subset=['transaction_date_utc'])
    print(f"  {len(df)} records across {df['site_id'].nunique()} sites")

    # ── 2. Connect to production PostgreSQL ───────────────────────────────────
    print("Connecting to production database...")
    engine = create_engine(prod_url, connect_args={'connect_timeout': 30})

    with engine.connect() as conn:
        before = conn.execute(text("SELECT COUNT(*) FROM prices")).scalar()
        before_sites = conn.execute(text("SELECT COUNT(DISTINCT site_id) FROM prices")).scalar()
    print(f"  Production currently has {before} prices ({before_sites} sites)")

    # ── 3. Write to staging table, then INSERT with ON CONFLICT DO NOTHING ────
    print(f"Writing {len(df)} rows to staging table...")
    with engine.begin() as conn:
        conn.execute(text(f"DROP TABLE IF EXISTS {STAGING_TABLE}"))

    df.to_sql(STAGING_TABLE, engine, if_exists='replace', index=False)

    print("Inserting missing prices (skipping existing site/fuel pairs)...")
    with engine.begin() as conn:
        result = conn.execute(text(f"""
            INSERT INTO prices
                (site_id, fuel_id, collection_method, transaction_date_utc,
                 price, created_at, updated_at)
            SELECT
                site_id, fuel_id, collection_method, transaction_date_utc,
                price, created_at, updated_at
            FROM {STAGING_TABLE}
            ON CONFLICT (site_id, fuel_id) DO NOTHING
        """))
        inserted = result.rowcount

        conn.execute(text(f"DROP TABLE IF EXISTS {STAGING_TABLE}"))

    # ── 4. Report ─────────────────────────────────────────────────────────────
    with engine.connect() as conn:
        after = conn.execute(text("SELECT COUNT(*) FROM prices")).scalar()
        after_sites = conn.execute(text("SELECT COUNT(DISTINCT site_id) FROM prices")).scalar()

    print(f"\nDone.")
    print(f"  Inserted : {inserted} new price records")
    print(f"  Skipped  : {len(df) - inserted} already existed in production")
    print(f"  Before   : {before} prices ({before_sites} sites)")
    print(f"  After    : {after} prices ({after_sites} sites)")


if __name__ == '__main__':
    main()
