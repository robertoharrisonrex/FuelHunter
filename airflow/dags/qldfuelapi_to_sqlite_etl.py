import json
import pandas as pd
import os
from datetime import datetime, timedelta
import requests
from airflow import DAG
from airflow.operators.bash import BashOperator
from airflow.operators.python import PythonOperator
from airflow.models import Variable
from sqlalchemy import create_engine, Table, Column, Integer, String, MetaData, DateTime, text


def _engine():
    url = os.environ.get(
        'FUELDB_URL',
        'sqlite:////opt/airflow/database/database.sqlite'
    )
    return create_engine(url, connect_args={'timeout': 30})


#  ------------------   BRANDS

def extract_brands():
    url = Variable.get("FUEL_REPORTING_SCHEME_URL") + "Subscriber/GetCountryBrands?countryId=21"

    response = requests.get(url, headers={
        "Accept": "application/json",
        "Authorization": Variable.get("FUEL_REPORTING_API_KEY"),
    })

    with open('/tmp/brands.json', 'w') as data:
        json.dump(response.json()['Brands'], data, indent=4)


def transform_brands():
    with open('/tmp/brands.json') as f:
        data = json.load(f)
    df = pd.DataFrame(data)
    df = df[['BrandId', 'Name']]
    df['created_at'] = datetime.now()
    df['updated_at'] = datetime.now()
    df.rename(columns={'BrandId': 'id', 'Name': 'name'}, inplace=True)

    # Create database connection

    engine = _engine()
    conn = engine.connect()

    # Create Temp table
    metadata = MetaData()
    brand_temp_table = Table("temp_brands", metadata,
                             Column("id", Integer, primary_key=True),
                             Column("name", String),
                             Column("created_at", DateTime),
                             Column("updated_at", DateTime))
    metadata.create_all(engine)

    # Write brands to temp table
    df.to_sql('temp_brands', con=conn, if_exists='replace', index=False)
    conn.close()


def load_brands():
    engine = _engine()
    conn = engine.connect()

    conn.execute(text("""
        UPDATE brands
        SET
            name = (SELECT name FROM temp_brands WHERE temp_brands.id = brands.id),
            updated_at = (SELECT updated_at FROM temp_brands WHERE temp_brands.id = brands.id)
        WHERE id IN (SELECT id FROM temp_brands);
    """))

    conn.execute(text("""
        INSERT INTO brands (id, name, created_at, updated_at)
        SELECT id, name, created_at, updated_at
        FROM temp_brands
        WHERE id NOT IN (SELECT id FROM brands);
    """))

    conn.close()


# --------------- REGIONS


def extract_regions():
    url = Variable.get("FUEL_REPORTING_SCHEME_URL") + "Subscriber/GetCountryGeographicRegions?countryId=21&countryId=21"

    response = requests.get(url, headers={
        "Accept": "application/json",
        "Authorization": Variable.get("FUEL_REPORTING_API_KEY"),
    })
    with open('/tmp/regions.json', 'w') as data:
        json.dump(response.json()['GeographicRegions'], data, indent=4)


def transform_regions():
    df = pd.read_json('/tmp/regions.json')

    # Group regions into suburbs, cities, and states
    suburb_ist = df[df['GeoRegionLevel'] == 1]
    city_list = df[df['GeoRegionLevel'] == 2]
    state_list = df[df['GeoRegionLevel'] == 3]

    # Apply transformations
    suburbs = sort_regions(suburb_ist, "Suburb")
    cities = sort_regions(city_list, "City")
    states = sort_regions(state_list, "State")

    # Create temporary tables
    engine = _engine()
    metadata = MetaData()

    for region_type in ["temp_suburbs", "temp_cities", "temp_states"]:
        region_type = Table(
            region_type,
            metadata,
            Column("id", Integer, primary_key=True),
            Column("region_level", Integer),
            Column("region_id", Integer),
            Column("type", String),
            Column("name", String),
            Column("abbreviation", String),
            Column("region_parent_id", String),
            Column("created_at", DateTime),
            Column("updated_at", DateTime),
        )
    metadata.create_all(engine)

    # Wrote tp temporary tables
    suburbs.to_sql('temp_suburbs', con=engine, if_exists='replace', index=False)
    cities.to_sql('temp_cities', con=engine, if_exists='replace', index=False)
    states.to_sql('temp_states', con=engine, if_exists='replace', index=False)


def load_regions():
    engine = _engine()
    conn = engine.connect()

    for region_type in ["suburbs", "cities", "states"]:
        print(region_type)
        conn.execute(text(f"""
        UPDATE {region_type}
        SET
            region_level = (SELECT region_level FROM temp_{region_type} where id = {region_type}.id),
            region_id = (SELECT region_id FROM temp_{region_type} where id = {region_type}.id),
            type = (SELECT type FROM temp_{region_type} where id = {region_type}.id),
            name = (SELECT name FROM temp_{region_type} where id = {region_type}.id),
            abbreviation = (SELECT abbreviation FROM temp_{region_type} where id = {region_type}.id),
            region_parent_id = (SELECT region_id FROM temp_{region_type} where id = {region_type}.id),
            updated_at = (SELECT updated_at FROM temp_{region_type} where id = {region_type}.id)
        where id in (select id FROM temp_{region_type});
        """))

        conn.execute(text(f"""
        insert into {region_type} (id, region_level, region_id, type, name, abbreviation, region_parent_id, created_at, updated_at)
            select id, region_level, region_id, type, name, abbreviation, region_parent_id, created_at, updated_at
            from temp_{region_type}
        where id not in (select id from {region_type})
        """))

    conn.close()


def sort_regions(df, region_type):
    region_df = pd.DataFrame()
    region_df['id'] = df['GeoRegionId']
    region_df['region_level'] = df['GeoRegionLevel'].astype("Int64")
    region_df['region_id'] = df['GeoRegionId'].astype("Int64")
    region_df['type'] = region_type
    region_df['name'] = df['Name'].astype(str)
    region_df['abbreviation'] = df['Abbrev'].astype(str)
    region_df['region_parent_id'] = df['GeoRegionParentId'].astype("Int64")
    region_df['created_at'] = datetime.now()
    region_df['updated_at'] = datetime.now()
    return region_df


# --------------- FUEL SITES

def extract_fuel_sites():
    url = Variable.get("FUEL_REPORTING_SCHEME_URL") + "Subscriber/GetFullSiteDetails?countryId=21&geoRegionLevel=3&geoRegionId=1"

    response = requests.get(url, headers={
        "Accept": "application/json",
        "Authorization": Variable.get("FUEL_REPORTING_API_KEY"),
    })
    with open('/tmp/fuelsites.json', 'w') as data:
        json.dump(response.json()['S'], data, indent=4)


def transform_fuel_sites():
    df = pd.read_json('/tmp/fuelsites.json')
    fuel_sites_df = pd.DataFrame()

    fuel_sites_df['id'] = df['S']
    fuel_sites_df['address'] = df['A'].astype(str)
    fuel_sites_df['name'] = df['N'].astype(str)
    fuel_sites_df['brand_id'] = df['B'].astype("Int64")
    fuel_sites_df['postcode'] = df['P'].astype("Int64")
    fuel_sites_df['latitude'] = pd.to_numeric(df['Lat'], errors='coerce')
    fuel_sites_df['longitude'] = pd.to_numeric(df['Lng'], errors='coerce')
    fuel_sites_df['geo_region_1'] = df['G1'].astype(int)
    fuel_sites_df['geo_region_2'] = df['G2'].astype(int)
    fuel_sites_df['geo_region_3'] = df['G3'].astype(int)
    fuel_sites_df['geo_region_4'] = df['G4'].astype(int)
    fuel_sites_df['geo_region_5'] = df['G5'].astype(int)
    fuel_sites_df['api_last_modified'] = pd.to_datetime(df['M'], errors='coerce')
    fuel_sites_df['google_place_id'] = df['GPI'].astype(str)
    fuel_sites_df['created_at'] = datetime.now()
    fuel_sites_df['updated_at'] = datetime.now()

    engine = _engine()
    metadata = MetaData()

    fuelsites = Table('temp_fuel_sites', metadata,
                      Column("id", Integer, primary_key=True),
                      Column("address", String),
                      Column("name", String),
                      Column("brand_id", Integer),
                      Column("postcode", String),
                      Column("latitude", String),
                      Column("longitude", String),
                      Column("geo_region_1", Integer),
                      Column("geo_region_2", Integer),
                      Column("geo_region_3", Integer),
                      Column("geo_region_4", Integer),
                      Column("geo_region_5", Integer),
                      Column("api_last_modified", DateTime),
                      Column("google_place_id", String),
                      Column("created_at", DateTime),
                      Column("updated_at", DateTime)
                      )
    metadata.create_all(engine)

    fuel_sites_df.to_sql('temp_fuel_sites', con=engine, if_exists='replace', index=False)


def load_fuel_sites():
    engine = _engine()
    conn = engine.connect()

    conn.execute(text("""
    UPDATE fuel_sites
    SET
        address = (select address from temp_fuel_sites where id = fuel_sites.id),
        name = (select name from temp_fuel_sites where id = fuel_sites.id),
        brand_id = (select brand_id from temp_fuel_sites where id = fuel_sites.id),
        postcode = (select postcode from temp_fuel_sites where id = fuel_sites.id),
        latitude = (select latitude from temp_fuel_sites where id = fuel_sites.id),
        longitude = (select longitude from temp_fuel_sites where id = fuel_sites.id),
        geo_region_1 = (select geo_region_1 from temp_fuel_sites where id = fuel_sites.id),
        geo_region_2 = (select geo_region_2 from temp_fuel_sites where id = fuel_sites.id),
        geo_region_3 = (select geo_region_3 from temp_fuel_sites where id = fuel_sites.id),
        geo_region_4 = (select geo_region_4 from temp_fuel_sites where id = fuel_sites.id),
        geo_region_5 = (select geo_region_5 from temp_fuel_sites where id = fuel_sites.id),
        api_last_modified = (select api_last_modified from temp_fuel_sites where id = fuel_sites.id),
        google_place_id = (select google_place_id from temp_fuel_sites where id = fuel_sites.id),
        updated_at = (select updated_at from temp_fuel_sites where id = fuel_sites.id)
    WHERE id in (select id from temp_fuel_sites);
    """))

    conn.execute(text("""
    INSERT INTO fuel_sites (id, address, name, brand_id, postcode, latitude, longitude, geo_region_1, geo_region_2, geo_region_3, geo_region_4, geo_region_5, api_last_modified, google_place_id, created_at, updated_at)
        select id, address, name, brand_id, postcode, latitude, longitude, geo_region_1, geo_region_2, geo_region_3, geo_region_4, geo_region_5, api_last_modified, google_place_id, created_at, updated_at
        from temp_fuel_sites
    WHERE id not in (select id from fuel_sites);
    """))
    conn.close()


# ------------- FUEL TYPES

def extract_fuel_types():
    url = Variable.get("FUEL_REPORTING_SCHEME_URL") + "Subscriber/GetCountryFuelTypes?CountryId=21"

    response = requests.get(url, headers={
        "Accept": "application/json",
        "Authorization": Variable.get("FUEL_REPORTING_API_KEY"),
    })
    with open('/tmp/fueltypes.json', 'w') as data:
        json.dump(response.json()['Fuels'], data, indent=4)


def transform_fuel_types():
    # Load json into DataFrame
    fuel_types_df = pd.read_json('/tmp/fueltypes.json')

    # Transformations
    fuel_types_df.rename(columns={'FuelId': 'id', 'Name': 'name'}, inplace=True)
    fuel_types_df['created_at'] = datetime.now()
    fuel_types_df['updated_at'] = datetime.now()

    # Create temporary table
    engine = _engine()
    metadata = MetaData()
    temp_fuel_types = Table('temp_fuel_types', metadata,
                            Column('id', Integer),
                            Column('name', String),
                            Column('created_at', DateTime),
                            Column('updated_at', DateTime),
                            )
    metadata.create_all(engine)

    # Write DataFrame to temporary database table
    fuel_types_df.to_sql('temp_fuel_types', con=engine, if_exists='replace', index=False)


def load_fuel_types():
    engine = _engine()
    conn = engine.connect()

    # UPDATE existing fuel_type if it exists (based on id)
    conn.execute(text("""
    UPDATE fuel_types
        SET
            name = (select name from temp_fuel_types where id = fuel_types.id),
            updated_at = (select updated_at from temp_fuel_types where id = fuel_types.id)
    WHERE id in (select id from temp_fuel_types);
    """))

    # INSERT existing fuel_type if it exists (based on id)
    conn.execute(text("""
    INSERT INTO fuel_types (id, name, created_at, updated_at)
        SELECT id, name, created_at, updated_at FROM temp_fuel_types
        WHERE id not in (select id from fuel_types);
    """))
    conn.close()



def extract_fuel_prices():
    url = Variable.get("FUEL_REPORTING_SCHEME_URL") + "Price/GetSitesPrices?CountryId=21&GeoRegionLevel=3&GeoRegionId=1"

    response = requests.get(url, headers={
        "Accept": "application/json",
        "Authorization": Variable.get("FUEL_REPORTING_API_KEY"),
    })
    with open('/tmp/fuelprices.json', 'w') as data:
        json.dump(response.json()['SitePrices'], data, indent=4)

def transform_fuel_prices():
    fuel_prices_df = pd.read_json('/tmp/fuelprices.json')
    fuel_prices_df.rename(columns={'SiteId': 'site_id', 'FuelId': 'fuel_id', 'Price':'price', 'CollectionMethod': 'collection_method', 'TransactionDateUtc': 'transaction_date_utc'}, inplace=True)
    fuel_prices_df['transaction_date_utc'] = pd.to_datetime(fuel_prices_df['transaction_date_utc'], errors='coerce')
    fuel_prices_df = fuel_prices_df.dropna(subset=['transaction_date_utc'])
    fuel_prices_df.loc[fuel_prices_df['price'] == 9999.0, 'fuel_id'] = 0.0
    fuel_prices_df['price'] = fuel_prices_df['price'] / 10
    fuel_prices_df['created_at'] = datetime.now()
    fuel_prices_df['updated_at'] = datetime.now()
    # Deduplicate here so we don't need rowid/ctid SQL tricks later
    fuel_prices_df = fuel_prices_df.drop_duplicates(subset=['site_id', 'fuel_id'], keep='last')

    engine = _engine()
    metadata = MetaData()
    temp_fuel_prices = Table('temp_prices', metadata,
                             Column('site_id', Integer),
                             Column('fuel_id', Integer),
                             Column('price', Integer),
                             Column('collection_method', String),
                             Column('transaction_date_utc', String),
                             Column('created_at', DateTime),
                             Column('updated_at', DateTime),
                             )
    fuel_prices_df.to_sql('temp_prices', con=engine, if_exists='replace', index=False)


def load_fuel_prices():
    engine = _engine()
    conn = engine.connect()

    # Archive current prices that are being superseded by a newer incoming price.
    conn.execute(text("""
        INSERT INTO historical_site_prices
            (site_id, fuel_id, collection_method, transaction_date_utc, price, created_at, updated_at)
        SELECT p.site_id, p.fuel_id, p.collection_method, p.transaction_date_utc, p.price, p.created_at, p.updated_at
        FROM prices p
        JOIN temp_prices tp ON tp.site_id = p.site_id AND tp.fuel_id = p.fuel_id
        WHERE tp.transaction_date_utc > p.transaction_date_utc
    """))

    # Delete the now-archived (superseded) prices from the current prices table.
    conn.execute(text("""
        DELETE FROM prices
        WHERE id IN (
            SELECT p.id
            FROM prices p
            JOIN temp_prices tp ON tp.site_id = p.site_id AND tp.fuel_id = p.fuel_id
            WHERE tp.transaction_date_utc > p.transaction_date_utc
        )
    """))

    # Insert new prices for: (a) updated pairs just deleted above, and (b) brand-new pairs.
    # Pairs with the same date as the existing price are ignored (they remain unchanged).
    conn.execute(text("""
        INSERT INTO prices
            (site_id, fuel_id, collection_method, transaction_date_utc, price, created_at, updated_at)
        SELECT site_id, fuel_id, collection_method, transaction_date_utc, price, created_at, updated_at
        FROM temp_prices
        WHERE (site_id, fuel_id) NOT IN (SELECT site_id, fuel_id FROM prices)
    """))

    conn.close()



dag = DAG(dag_id='qldfuelapi_to_sqlite_etl',
          start_date=datetime(2026, 3, 18),
          schedule_interval=timedelta(minutes=30),
          catchup=False,
          default_args={"owner": "Roberto", "email": ["roberto@boffincentral.com"]})

bash_task = BashOperator(task_id='bash_task',
                         bash_command='pwd',
                         dag=dag)

# ----------------------
extract_brands_task = PythonOperator(task_id='extract_brands',
                                     python_callable=extract_brands,
                                     dag=dag)
transform_brands_task = PythonOperator(task_id='transform_brands',
                                       python_callable=transform_brands,
                                       dag=dag)
load_brands_task = PythonOperator(task_id='load_brands',
                                  python_callable=load_brands,
                                  dag=dag)

# ----------------------

extract_regions_task = PythonOperator(task_id='extract_regions',
                                      python_callable=extract_regions,
                                      dag=dag)
transform_regions_task = PythonOperator(task_id='transform_regions',
                                        python_callable=transform_regions,
                                        dag=dag)
load_regions_task = PythonOperator(task_id='load_regions',
                                   python_callable=load_regions,
                                   dag=dag)
# -----------------------
extract_fuel_sites_task = PythonOperator(task_id='extract_fuel_sites',
                                         python_callable=extract_fuel_sites,
                                         dag=dag)
transform_fuel_sites_task = PythonOperator(task_id='transform_fuel_sites',
                                           python_callable=transform_fuel_sites,
                                           dag=dag)
load_fuel_sites_task = PythonOperator(task_id='load_fuel_sites',
                                      python_callable=load_fuel_sites,
                                      dag=dag)
# -------------------------
extract_fuel_types_task = PythonOperator(task_id='extract_fuel_types',
                                         python_callable=extract_fuel_types,
                                         dag=dag)
transform_fuel_types_task = PythonOperator(task_id='transform_fuel_types',
                                           python_callable=transform_fuel_types,
                                           dag=dag)
load_fuel_types_task = PythonOperator(task_id='load_fuel_types',
                                      python_callable=load_fuel_types,
                                      dag=dag)
# -------------------------
extract_fuel_prices_task = PythonOperator(task_id='extract_fuel_prices',
                                         python_callable=extract_fuel_prices,
                                         dag=dag)
transform_fuel_prices_task = PythonOperator(task_id='transform_fuel_prices',
                                           python_callable=transform_fuel_prices,
                                           dag=dag)
load_fuel_prices_task = PythonOperator(task_id='load_fuel_prices',
                                           python_callable=load_fuel_prices,
                                           dag=dag)



bash_task >> extract_brands_task >> transform_brands_task >> load_brands_task >> extract_regions_task >> transform_regions_task >> load_regions_task >> extract_fuel_sites_task >> transform_fuel_sites_task >> load_fuel_sites_task >> extract_fuel_types_task >> transform_fuel_types_task >> load_fuel_types_task >> extract_fuel_prices_task >> transform_fuel_prices_task >> load_fuel_prices_task

if __name__ == "__main__":
    dag.test()
