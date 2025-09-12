# Oracle Database Docker Setup

This repository contains Docker configuration for running Oracle Database XE 21c in a container, compatible with SQL Developer and MS Access.

## Quick Start

1. **Start the Oracle Database Container:**
   ```powershell
   docker-compose up -d
   ```

2. **Wait for Database Initialization:**
   The database takes about 2-3 minutes to fully initialize. Check the logs:
   ```powershell
   docker-compose logs -f oracle-db
   ```

3. **Verify Database is Ready:**
   Wait for the message "DATABASE IS READY TO USE!" in the logs.

## Database Connection Details

### Default Credentials
- **System User:** `system` / `Oracle123`
- **Application User:** `appuser` / `appuser123` (Full permissions)

### Connection Information
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

## File Structure

```
oracleProject/
├── docker-compose.yml          # Main Docker Compose configuration
├── .env                        # Environment variables
├── init-scripts/
│   └── 01-create-user.sql     # User creation script
└── README.md                  # This file
```

## Security Notes

- Change default passwords in production environments
- Consider using Docker secrets for sensitive data
- Restrict network access in production deployments
- Regularly update the Oracle image for security patches