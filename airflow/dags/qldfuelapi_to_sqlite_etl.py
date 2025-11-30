import json
import pandas as pd
import os
from datetime import datetime, timedelta
import requests
from airflow import DAG
from airflow.operators.bash import BashOperator
from airflow.operators.python import PythonOperator
from airflow.models import Variable
from dotenv import load_dotenv
from sqlalchemy import create_engine, Table, Column, Integer, String, MetaData, DateTime



#  ------------------   BRANDS

def extract_brands():
    url = Variable.get("FUEL_REPORTING_SCHEME_URL") + "GetCountryBrands?countryId=21"

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
    df['created_at'] = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    df['updated_at'] = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    df.rename(columns={'BrandId': 'id', 'Name': 'name'}, inplace=True)

    # Create database connection

    engine = create_engine('sqlite:////opt/airflow/database/database.sqlite')
    con = engine.connect()

    # Create Temp table
    metadata = MetaData(bind=con)
    brand_temp_table = Table("temp_brands", metadata,
                             Column("id", Integer, primary_key=True),
                             Column("name", String),
                             Column("created_at", DateTime),
                             Column("updated_at", DateTime))
    metadata.create_all(con)

    # Write brands to temp table
    df.to_sql('temp_brands', con=con, if_exists='replace', index=False)
    con.close()

def load_brands():
    engine = create_engine('sqlite:////opt/airflow/database/database.sqlite')
    con = engine.connect()

    con.execute("""
        UPDATE brands
        SET
            name = (SELECT name FROM temp_brands WHERE temp_brands.id = brands.id),
            updated_at = (SELECT updated_at FROM temp_brands WHERE temp_brands.id = brands.id)
        WHERE id IN (SELECT id FROM temp_brands);
    """)

    con.execute("""
        INSERT INTO brands (id, name, created_at, updated_at)
        SELECT id, name, created_at, updated_at
        FROM temp_brands
        WHERE id NOT IN (SELECT id FROM brands);
    """)

    con.close()


# --------------- CITIES


def extract_regions():
    url = Variable.get("FUEL_REPORTING_SCHEME_URL") + "GetCountryGeographicRegions?countryId=21&countryId=21"

    response = requests.get(url, headers={
        "Accept": "application/json",
        "Authorization": Variable.get("FUEL_REPORTING_API_KEY"),
    })

    regions = response.json()['Regions']
    for region in regions:

        geo_region_level = region['GeoRegionLevel']

        match geo_region_level:
            case 1:
                type_ = "Suburb"
            case 2:
                type_ = "City"
            case 3:
                type_ = "State"
            case _:
                raise ValueError(f"{region_level} is not a valid region, must be 1, 2, or 3.")

        with open('/tmp/brands.json', 'w') as data:
            json.dump(response.json()['Brands'], data, indent=4)







dag = DAG(dag_id='qldfuelapi_to_sqlite_etl',
          start_date=datetime(2022, 11, 28),
          schedule_interval=timedelta(hours=12),
          default_args={"owner": "Roberto", "email": ["roberto@boffincentral.com"]})

bash_task = BashOperator(task_id='bash_task',
                         bash_command='pwd',
                         dag=dag)

extract_brands_task = PythonOperator(task_id='extract',
                                     python_callable=extract_brands,
                                     dag=dag)

transform_brands_task = PythonOperator(task_id='transform',
                                       python_callable=transform_brands,
                                       dag=dag)

load_brands_task = PythonOperator(task_id='load',
                                  python_callable=load_brands,
                                  dag=dag)

bash_task >> extract_brands_task >> transform_brands_task >> load_brands_task

# if __name__ == "__main__":
#     dag.test()
