# airflow/dags/oilprice_etl.py
from datetime import datetime, timedelta
import os

import requests
from airflow import DAG
from airflow.models import Variable
from airflow.operators.python import PythonOperator
from sqlalchemy import create_engine, text
import time

COMMODITY_CODES = ['WTI_USD', 'BRENT_CRUDE_USD', 'NATURAL_GAS_USD', 'GASOLINE_USD']


def _engine():
    url = os.environ.get('FUELDB_URL', 'sqlite:////opt/airflow/database/database.sqlite')
    return create_engine(url)


def fetch_and_store_oil_prices():
    import logging
    api_token = Variable.get('OILPRICE_API_TOKEN')
    headers = {
        'Authorization': f'Token {api_token}',
        'Accept': 'application/json',
    }

    engine = _engine()
    driver = engine.dialect.name  # 'sqlite' or 'postgresql'

    if driver == 'sqlite':
        insert_sql = """
            INSERT OR IGNORE INTO oil_prices (code, price, currency, recorded_at, created_at, updated_at)
            VALUES (:code, :price, :currency, :recorded_at, datetime('now'), datetime('now'))
        """
    else:
        insert_sql = """
            INSERT INTO oil_prices (code, price, currency, recorded_at, created_at, updated_at)
            VALUES (:code, :price, :currency, :recorded_at, now(), now())
            ON CONFLICT (code, recorded_at) DO NOTHING
        """

    with engine.begin() as conn:
        for code in COMMODITY_CODES:
            try:
                time.sleep(5)
                resp = requests.get(
                    'https://api.oilpriceapi.com/v1/prices/latest',
                    headers=headers,
                    params={'by_code': code},
                    timeout=30,
                )
                resp.raise_for_status()
                data = resp.json()['data']
                logging.info(data)
                conn.execute(text(insert_sql), {
                    'code':        data['code'],
                    'price':       float(data['price']),
                    'currency':    data.get('currency', 'USD'),
                    'recorded_at': data['created_at'],
                })
            except Exception as e:
                logging.warning('oilprice_etl: failed to fetch %s: %s', code, e)


dag = DAG(
    dag_id='oilprice_etl',
    start_date=datetime(2026, 4, 14),
    schedule_interval=timedelta(minutes=20),
    catchup=False,
    default_args={'owner': 'Roberto', 'email': ['roberto@boffincentral.com']},
)

fetch_task = PythonOperator(
    task_id='fetch_and_store_oil_prices',
    python_callable=fetch_and_store_oil_prices,
    dag=dag,
)
