# Oracle PHP Web Application

A modern PHP web application that connects to Oracle Database XE 21c, built with Docker for easy deployment and development.

## Features

- **Modern PHP Web Interface**: Built with PHP 8.2, Bootstrap 5, and Font Awesome
- **Oracle Database Integration**: Full PDO support for Oracle Database XE 21c
- **Responsive Design**: Mobile-friendly interface with modern UI components
- **Database Management**: View clients, products, employees, and invoices
- **Real-time Connection Status**: Monitor database connectivity
- **Docker-based**: Easy deployment with Docker Compose
- **Health Checks**: Built-in health monitoring for both database and web application

## Architecture

```
├── docker-compose.yml          # Docker services configuration
├── init-scripts/              # Database initialization scripts
│   ├── 01-create-user.sql
│   ├── 02-create-tables.sql
│   ├── 03-insert-sample-data.sql
│   └── 04-query-sample-data.sql
└── web-app/                   # PHP Web Application
    ├── Dockerfile             # PHP Apache container with Oracle support
    ├── apache-vhost.conf      # Apache virtual host configuration
    ├── assets/                # CSS and JavaScript files
    │   ├── css/style.css
    │   └── js/app.js
    ├── config/                # Application configuration
    │   ├── config.php
    │   └── database.php
    ├── includes/              # Common PHP includes
    │   ├── header.php
    │   ├── footer.php
    │   └── utils.php
    └── public/                # Web application pages
        ├── index.php          # Dashboard
        ├── clients.php        # Client management
        └── database_info.php  # Database information
```

## Prerequisites

- Docker Desktop (Windows/macOS) or Docker Engine (Linux)
- Docker Compose
- At least 4GB of available RAM
- At least 10GB of free disk space

## Quick Start

1. **Clone or navigate to the project directory**
   ```bash
   cd oracleProject
   ```

2. **Start the services**
   ```bash
   docker-compose up -d
   ```

3. **Wait for services to be ready**
   - Oracle Database: Takes 2-3 minutes to initialize on first run
   - PHP Web App: Available once database is healthy

4. **Access the application**
   - Web Application: http://localhost:8080
   - Oracle EM Express: http://localhost:5501/em
   - Database Port: localhost:1522

## Default Credentials

### Oracle Database
- **System User**: `system` / `Oracle123`
- **Application User**: `appuser` / `appuser123`
- **Database**: `XEPDB1` (Pluggable Database)

### Connection Details
- **Host**: `oracle-db` (internal) or `localhost` (external)
- **Port**: `1521` (internal) or `1522` (external)
- **Service Name**: `XEPDB1`

## Web Application Features

### Dashboard (`/index.php`)
- Real-time statistics (clients, products, employees, invoices)
- Recent data overview
- Quick action buttons
- Connection status indicator

### Database Information (`/database_info.php`)
- Connection status and testing
- Database version and details
- Session information
- Table structure overview

### Client Management (`/clients.php`)
- List all clients with pagination
- Search functionality
- Client details with contact information
- CRUD operations (View/Edit/Delete)

## Development

### Project Structure
```
web-app/
├── config/
│   ├── config.php      # Application configuration
│   └── database.php    # Database connection class
├── includes/
│   ├── header.php      # Common header with navigation
│   ├── footer.php      # Common footer
│   └── utils.php       # Utility functions
├── assets/
│   ├── css/style.css   # Custom styles
│   └── js/app.js       # JavaScript functionality
└── public/
    └── *.php           # Web pages
```

### Key Classes

#### `DatabaseConfig` (`config/database.php`)
- Oracle PDO connection management
- Connection testing and validation
- Database information retrieval

#### `AppConfig` (`config/config.php`)
- Application-wide settings
- Environment configuration
- Session management

#### `Utils` (`includes/utils.php`)
- Input sanitization
- Pagination generation
- Table rendering
- Message handling

### Adding New Pages

1. Create new PHP file in `public/` directory
2. Include header: `require_once 'includes/header.php';`
3. Set page title: `$page_title = 'Your Page Title';`
4. Add navigation link in `includes/header.php`
5. Include footer: `require_once 'includes/footer.php';`

## Database Schema

The application includes several pre-created tables:

- **Client_Type**: Client categories and discount rates
- **Clients**: Customer information and contacts
- **Product_Type**: Product categories
- **Products**: Product inventory and pricing
- **Employees**: Staff information
- **Invoices**: Sales transactions
- **Invoice_Details**: Line items for invoices

## Troubleshooting

### Common Issues

1. **Oracle Database won't start**
   - Ensure you have enough disk space (10GB+)
   - Check if port 1522 is available
   - Wait for initialization (2-3 minutes on first run)

2. **Web application can't connect to database**
   - Verify Oracle service is healthy: `docker-compose ps`
   - Check logs: `docker-compose logs oracle-db`
   - Ensure network connectivity between containers

3. **PHP Oracle extensions not loaded**
   - Rebuild the web application: `docker-compose build web-app`
   - Check PHP configuration: Visit http://localhost:8080/database_info.php

### Useful Commands

```bash
# View service status
docker-compose ps

# View logs
docker-compose logs oracle-db
docker-compose logs web-app

# Restart services
docker-compose restart

# Rebuild web application
docker-compose build web-app

# Connect to Oracle database
docker exec -it oracle-xe-db sqlplus appuser/appuser123@XEPDB1

# Shell access to web container
docker exec -it php-web-app bash
```

## Performance Tips

1. **Oracle Database**
   - Increase shared memory if running on Linux
   - Use persistent volumes for data storage
   - Configure Oracle memory parameters for your environment

2. **PHP Application**
   - Enable OPcache for better performance
   - Use connection pooling for high traffic
   - Implement caching for frequently accessed data

## Security Considerations

- Change default passwords in production
- Use environment variables for sensitive configuration
- Enable SSL/TLS for production deployments
- Implement proper input validation and sanitization
- Use prepared statements for all database queries

## Contributing

1. Follow PSR-12 coding standards for PHP
2. Use meaningful commit messages
3. Add comments for complex business logic
4. Test database connections and queries
5. Ensure responsive design compatibility

## License

This project is for educational and development purposes.

## Support

For issues and questions:
1. Check the troubleshooting section above
2. Review Docker and Oracle documentation
3. Check application logs for detailed error messages