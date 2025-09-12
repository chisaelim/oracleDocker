# Oracle Database Docker Container# Oracle Database Docker Setup



## Quick StartThis repository contains Docker configuration for running Oracle Database XE 21c in a container, compatible with SQL Developer and MS Access.

```powershell

# Start Oracle container## Quick Start

docker-compose up -d

1. **Start the Oracle Database Container:**

# Check container status   ```powershell

docker-compose ps   docker-compose up -d

   ```

# View logs

docker-compose logs -f oracle-db2. **Wait for Database Initialization:**

   The database takes about 2-3 minutes to fully initialize. Check the logs:

# Stop container   ```powershell

docker-compose down   docker-compose logs -f oracle-db

```   ```



## Connection Details3. **Verify Database is Ready:**

- **Host:** localhost   Wait for the message "DATABASE IS READY TO USE!" in the logs.

- **Port:** 1522

- **Service:** XEPDB1## Database Connection Details

- **System User:** system / Oracle123

- **Application User:** appuser / appuser123### Default Credentials

- **System User:** `system` / `Oracle123`

## Notes- **Application User:** `appuser` / `appuser123` (Full permissions)

- Container takes 2-3 minutes to fully initialize

- Wait for "DATABASE IS READY TO USE!" message in logs before connecting### Connection Information
- **Host:** `localhost`
- **Port:** `1522`
- **Service Name:** `XEPDB1` (Pluggable Database)
- **SID:** `XE` (Container Database)

## SQL Developer Connection

1. **Create New Connection:**
   - Connection Name: `Oracle Docker`
   - Username: `appuser`
   - Password: `appuser123`
   - Connection Type: `Basic`
   - Hostname: `localhost`
   - Port: `1522`
   - Service name: `XEPDB1`

2. **Test Connection:**
   Click "Test" to verify the connection works.

## MS Access Connection (ODBC)

### Prerequisites
1. **Install Oracle Instant Client:**
   - Download from: https://www.oracle.com/database/technologies/instant-client/downloads.html
   - Install the Basic package and ODBC package

2. **Configure ODBC Data Source:**
   - Open "ODBC Data Sources (64-bit)" from Windows
   - Click "Add" and select "Oracle in instantclient"
   - Configure the connection:
     - **Data Source Name:** `OracleDocker`
     - **Description:** `Oracle Docker Container`
     - **TNS Service Name:** `localhost:1522/XEPDB1`
     - **User ID:** `appuser`

### Connecting from MS Access
1. **Link Tables:**
   - External Data → ODBC Database → Link to Data Source
   - Select "OracleDocker" data source
   - Enter password: `appuser123`
   - Select tables to link

2. **Import Data:**
   - External Data → ODBC Database → Import
   - Follow same steps as linking

## Alternative Connection Strings

### JDBC Connection String
```
jdbc:oracle:thin:@localhost:1522/XEPDB1
```

### TNS Connection String
```
(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1522))(CONNECT_DATA=(SERVICE_NAME=XEPDB1)))
```

## User Permissions

The `appuser` has been granted the following permissions:
- `CONNECT` - Basic connection privileges
- `RESOURCE` - Create tables, sequences, etc.
- `DBA` - Full database administration privileges
- `UNLIMITED TABLESPACE` - No storage limits
- `SELECT_CATALOG_ROLE` - Query system catalogs
- `EXECUTE_CATALOG_ROLE` - Execute system packages

## Management Commands

### Start/Stop Database
```powershell
# Start
docker-compose up -d

# Stop
docker-compose down

# Stop and remove data
docker-compose down -v
```

### Access Database Console
```powershell
# Connect as system user to container database
docker exec -it oracle-xe-db sqlplus system/Oracle123@XE

# Connect as application user to pluggable database
docker exec -it oracle-xe-db sqlplus appuser/appuser123@XEPDB1
```

### Oracle Enterprise Manager Express
Access the web-based management tool at: http://localhost:5501/em

- Username: `system`
- Password: `Oracle123`

## Troubleshooting

### Container Won't Start
- Ensure port 1522 is not in use by another service
- Check Docker logs: `docker-compose logs oracle-db`

### Connection Refused
- Verify container is running: `docker-compose ps`
- Wait for full initialization (check logs for "DATABASE IS READY TO USE!")

### MS Access Connection Issues
- Ensure Oracle Instant Client is properly installed
- Verify ODBC driver is 64-bit if using 64-bit MS Access
- Test ODBC connection using Windows ODBC Administrator

### Performance Issues
- Increase Docker memory allocation (minimum 2GB recommended)
- Consider using persistent volumes for better I/O performance

## PHP Web Application

The Employee Management System is a complete web application built with PHP that provides CRUD operations for managing employee data.

### Docker Deployment (Recommended)

The application is containerized and can be run alongside the Oracle database:

```bash
# Build and start all services (Oracle DB + PHP Web App)
docker-compose up -d --build

# Access the web application
# http://localhost:8080
```

**Services:**
- **Oracle Database**: Available on port 1522
- **Web Application**: Available on port 8080
- **Oracle EM Express**: Available on port 5501

### XAMPP Deployment (Alternative)

To use the application with XAMPP:

1. Copy the entire `app/` folder contents to your XAMPP `htdocs` directory
2. Install Oracle OCI extension for PHP
3. Start XAMPP and ensure Apache and PHP are running
4. Update database configuration in `app/config/config.php`
5. Access the application at `http://localhost/app`

### Application Features

- **Employee CRUD Operations**: Create, Read, Update, Delete employees
- **Search and Pagination**: Find employees quickly with advanced search
- **Job Position Management**: Dropdown selection from available job positions  
- **Statistics Dashboard**: View employee statistics and summary information
- **Responsive Design**: Bootstrap-based UI that works on desktop and mobile
- **Form Validation**: Client-side and server-side validation
- **Flash Messages**: Success/error notifications for user actions

### Application Structure

```
app/
├── config/
│   ├── config.php          # Application configuration and constants
│   └── database.php        # Database connection class
├── includes/
│   └── Employee.php        # Employee CRUD operations class
├── index.php               # Main employee listing page
├── add_employee.php        # Add new employee form
├── edit_employee.php       # Edit existing employee form
├── delete_employee.php     # Delete employee confirmation
├── Dockerfile              # Docker container configuration
├── apache-config.conf      # Apache virtual host configuration
└── .dockerignore          # Docker ignore file
```

## File Structure

```
oracleProject/
├── docker-compose.yml          # Main Docker Compose configuration
├── .env                        # Environment variables
├── init-scripts/
│   └── 01-create-user.sql     # User creation script
├── app/                        # PHP Web Application
│   ├── config/                 # Configuration files
│   ├── includes/               # PHP classes and includes
│   ├── *.php                   # Application pages
│   └── Dockerfile              # Web app container config
└── README.md                  # This file
```

## Security Notes

- Change default passwords in production environments
- Consider using Docker secrets for sensitive data
- Restrict network access in production deployments
- Regularly update the Oracle image for security patches