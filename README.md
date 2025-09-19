# Scheduly · Event Planner (Zeitplaner) 📅

A **web-based scheduling tool** for events, shifts, and team organization.  
Easily manage participants, locations, and time slots with a clear tabular visualization.  
Created with the help of ChatGPT-5.

## ✨ Features

- **Event Management**: create events with start/end times, generate shareable token-based URLs  
- **Participants**: sign up with name, station, date & time; attending / not attending; optional T-shirt size  
- **Views**: by user (timeline), by station (grouped); dynamic hour range; multi-day support  
- **Action Row**: manage actions (setup, event, teardown), displayed below the hour headers  
- **Administration**: manage locations, price lists with payment options & variants, CSV export (e.g., SumUp)  
- **Tech Stack**: PHP & MySQL backend · HTML, CSS, JS frontend · centralized `style.css`  

## 🚀 Installation

### Clone repository
   ```bash
   git clone https://github.com/USERNAME/scheduly.git
   cd scheduly
   ```

### Database
- Import `db.sql`  
- Adjust credentials in `config.php`  

### Webserver
- PHP >= 7.4  
- MySQL / MariaDB  
- Writable permissions for uploads if needed 

## License

This project is licensed under the MIT License.
