
## Overview
This project extracts data from the **Queensland Fuel Price Reporting Scheme API**, transforms the data, and loads the data into a SQLite database.
Data orchestration is performed by Apache Airflow. 

A web app powered by Laravel lets users create and manage an account, view fuel station locations, prices, and statistics

## Project Setup

A few commands need to be run in order to generate tables, extract data from QLDs Fuel Reporting
API, load data into tables, and then build the project

- Run php artisan migrate:fresh --seed
- npm run dev
- Run php artisan serve --port=7025


## Queensland Fuel Price Reporting Scheme API
**Prod API Endpoint -** https://fppdirectapi-prod.fuelpricesqld.com.au

**API Manual -** https://www.fuelpricesqld.com.au/documents/FuelPricesQLDDirectAPI(OUT)v1.5.pdf
