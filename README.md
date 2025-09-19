# Scheduly

# Event Planner (Zeitplaner)

A **web-based scheduling tool** for events, shifts, and team organization.  
It provides an easy way to manage participants, locations, and time slots with a clear tabular visualization.

## Features

- **Event Management**
  - Create events with start and end times  
  - Generate shareable, token-based event URLs  

- **Participant Management**
  - Sign up with name, station, date, and time  
  - Option: *attending / not attending*  
  - Optional T-shirt size collection  

- **Views**
  - **By User**: timeline per participant with hourly grid  
  - **By Station**: grouped by station, showing all participants per block  
  - Dynamic hour range (only the actual event duration is displayed)  
  - Multi-day event support  

- **Action Row**
  - Manage event actions (e.g., setup, event, teardown)  
  - Displayed below the hour headers with highlighted style  

- **Administration**
  - Manage locations via dedicated admin pages  
  - Price list management with payment options, order, and variants  
  - Export function (e.g., CSV for external tools such as SumUp)  

- **Tech Stack**
  - **Frontend**: HTML, CSS, JavaScript (mobile-friendly)  
  - **Backend**: PHP, MySQL database  
  - Centralized styling (`style.css`)  

## Installation

1. Clone repository:
   ```bash
   git clone https://github.com/USERNAME/event-planner.git
   cd event-planner
   ```
2. Setup database:
  - Import db.sql
  - Adjust credentials in config.php

4. Configure webserver:
  - PHP >= 7.4
  - MySQL/MariaDB
  - Writable permissions for uploads if needed

6. Open project in browser.
