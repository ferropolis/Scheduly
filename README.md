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
   git clone https://github.com/ferropolis/scheduly.git
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

## Pictures
<img height="300" alt="Testveranstaltung – Eintragen" src="https://github.com/user-attachments/assets/0b4607f1-0f8f-4240-9141-672880c5d8d9" />
<img height="300" alt="Testveranstaltung – Veranstaltungen adminansicht" src="https://github.com/user-attachments/assets/baf95f0b-83ea-4639-801f-1b189124627b" />
<img height="300" alt="Testveranstaltung – Station-Zeitplan" src="https://github.com/user-attachments/assets/3dd795c6-50e8-4de8-a5e7-e92a19903c6b" />
<img height="300" alt="Testveranstaltung – Einsatzplan nach Benutzer" src="https://github.com/user-attachments/assets/23558185-2058-4b1e-81dc-b8673add1a9f" />
