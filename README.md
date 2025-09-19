# Oracle Docker Invoice Management System

**Version:** 2.0.0 - Final Release  
**Last Updated:** September 20, 2025  
**Author:** [@chisaelim](https://github.com/chisaelim)  
**Status:** Production Ready ✅

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Project Architecture](#project-architecture)
3. [System Features](#system-features)
4. [Quick Start Guide](#quick-start-guide)
5. [Database Schema](#database-schema)
6. [Web Application Features](#web-application-features)
7. [Docker Configuration](#docker-configuration)
8. [Configuration Files](#configuration-files)
9. [Sample Data](#sample-data)
10. [Development Guide](#development-guide)
11. [Security Considerations](#security-considerations)
12. [Performance Optimization](#performance-optimization)
13. [Monitoring and Maintenance](#monitoring-and-maintenance)
14. [Troubleshooting](#troubleshooting)
15. [Additional Resources](#additional-resources)
16. [Contributing](#contributing)
17. [License and Support](#license-and-support)

---

## Project Overview

A complete containerized invoice management system built with **Oracle XE Database** and **PHP** using Docker containers. This project provides a full-featured business invoice system with advanced client discount management, real-time current month analytics, product catalog, employee tracking, and comprehensive reporting capabilities.

### Key Benefits
- **Enterprise-grade Database**: Oracle XE for reliable data management
- **Containerized Deployment**: Docker-based architecture for easy deployment
- **Complete Business Solution**: End-to-end invoice management system with advanced discount integration
- **Real-time Analytics**: Current month focused dashboard with comprehensive business intelligence
- **Client-Specific Discounts**: Flexible discount system using client table discount rates
- **Modern Web Interface**: Responsive PHP application with Bootstrap UI and enhanced UX
- **Production Ready**: Optimized for business use with security considerations and performance tuning

### Latest Features (v2.0.0)
- **✅ Current Month Data Focus**: Dashboard displays September 2025 data for all metrics except totals
- **✅ Advanced Discount Integration**: Client-specific discount calculations throughout the system
- **✅ Enhanced Financial Reporting**: Net revenue calculations with discount impact analysis
- **✅ Real-time Business Intelligence**: Live dashboard with comprehensive KPIs
- **✅ Improved Performance**: Optimized queries with proper date filtering and indexing

## Project Architecture

### Directory Structure
```
oracleDocker/
├── init-scripts/              # Database initialization
│   ├── 01-create-user.sql     # Oracle user creation
│   └── 02-create-tables.sql   # Database schema and sample data
├── web-app/                   # PHP web application
│   ├── assets/                # CSS, JS, images
│   ├── config/                # Database configuration
│   ├── includes/              # Shared PHP components
│   ├── public/                # Web application files
│   ├── Dockerfile             # PHP container setup
│   └── apache-vhost.conf      # Apache configuration
├── docker-compose.yml         # Container orchestration
└── README.md                  # This documentation
```

### System Components
1. **Oracle XE Database Container**: Enterprise database with initialization scripts and client discount schema
2. **PHP Web Application Container**: Custom-built container with Oracle connectivity and advanced discount calculations
3. **Docker Network**: Secure internal communication between containers
4. **Volume Persistence**: Data persistence and development file mounting
5. **Enhanced Dashboard**: Current month analytics with client-specific discount integration

---

## System Features

### Core Business Functionality
- **Advanced Invoice Management**: Create, view, edit, and track invoices with client-specific discount calculations
- **Smart Client Management**: Customer database with type classifications, custom discount rates, and automatic discount application
- **Dynamic Product Catalog**: Comprehensive product inventory with real-time pricing, categories, and current month sales tracking
- **Employee Performance Tracking**: Staff database with current month sales performance and commission calculations
- **Real-time Business Intelligence**: Current month focused dashboard with discount impact analysis and financial KPIs
- **Enhanced Reporting**: Current month analytics with net revenue calculations and discount impact reporting
- **Data Export**: Export current month reports and analytics to various formats for business analysis

### Advanced Dashboard Features (v2.0.0)
- **Current Month Analytics**: All metrics show September 2025 data except total counts (Clients, Invoices, Products, Employees)
- **Client Discount Integration**: Automatic discount application from Clients table DISCOUNT column
- **Net Revenue Calculations**: Real-time net revenue after discount calculations
- **Discount Impact Analysis**: Visual representation of discount effects on revenue
- **Employee Performance**: Current month sales performance with discount-adjusted calculations
- **Top Products/Clients**: Current month leaders with net value calculations
- **Pending Invoice Tracking**: Current month pending invoices with discount impact

### Technical Features
- **Containerized Architecture**: Docker-based deployment for consistency across environments
- **Oracle Database Integration**: Enterprise-grade database with XE edition and advanced discount schema
- **PHP Web Application**: Modern PHP 8.1 with OCI8 Oracle connectivity and optimized discount calculations
- **Responsive UI Design**: Bootstrap-based responsive design with enhanced dashboard visualizations
- **Advanced Query Optimization**: Date-filtered queries for current month data with proper indexing
- **Real-time Data Processing**: Live calculations for discount impact and net revenue analysis
- **Tab State Persistence**: UI state management with localStorage for better user experience
- **AJAX Integration**: Dynamic data loading and form submissions without page reloads
- **Security Implementation**: Input validation, SQL injection prevention, and secure connections
- **Performance Optimization**: Efficient current month data filtering with minimal database load

---

## Quick Start Guide

### Prerequisites
Before starting, ensure you have:
- **Docker Desktop** installed and running (version 20.10+)
- **Docker Compose** v3.8 or higher
- **8GB+ RAM** recommended for Oracle container
- **Available Ports**: 1522 (Oracle), 8090 (Web App)
- **20GB+ disk space** for containers and data

### Installation Steps

**Step 1: Clone the Repository**
```bash
git clone https://github.com/chisaelim/oracleDocker.git
cd oracleDocker
```

**Step 2: Start the System**
```bash
docker-compose up -d
```

**Step 3: Wait for Database Initialization**
The Oracle database requires 5-10 minutes for initial setup:
```bash
# Monitor container logs
docker-compose logs -f oracle-db

# Look for: "DATABASE IS READY TO USE!"
```

**Step 4: Access the Application**
- **Enhanced Dashboard**: http://localhost:8080 (Main application with current month analytics)
- **Alternative Web Port**: http://localhost:8090 (Backup access if port 8080 is used)
- **Oracle Database**: localhost:1522 (SID: XE)
- **Database Credentials**: invoiceuser/invoice123

### First Run Verification

1. **Database Connection Test**
   - Navigate to: http://localhost:8080/database_info.php
   - Verify Oracle connection status shows "Connected"

2. **Dashboard Verification**
   - **Main Dashboard**: http://localhost:8080 - Verify current month data display (September 2025)
   - **Total Counts**: Verify Total Clients, Invoices, Products, Employees show all-time totals
   - **Current Month Data**: Verify all other metrics show September 2025 data only
   - **Discount Integration**: Verify discount calculations appear throughout the interface

3. **Sample Data Verification**
   - **Clients**: 5 client companies with different discount rates (visible in dashboard)
   - **Products**: 10 products across 5 categories with current month sales tracking
   - **Invoices**: Sample invoices with client-specific discount calculations
   - **Employees**: 5 staff members with current month performance metrics

4. **Feature Testing**
   - **Discount Analysis**: Check discount impact section shows current month statistics
   - **Recent Invoices**: Verify only September 2025 invoices display with discount details
   - **Employee Performance**: Confirm current month sales data with discount-adjusted calculations

---

## Current Implementation Status (v2.0.0)

### ✅ Completed Features

#### Dashboard Analytics (Current Month Focus)
- **✅ September 2025 Data Filtering**: All dashboard metrics show current month data only
- **✅ Client Discount Integration**: Automatic discount calculations using Clients.DISCOUNT column
- **✅ Net Revenue Calculations**: Real-time net revenue after client-specific discounts
- **✅ Discount Impact Analysis**: Visual discount impact with percentage and monetary values
- **✅ All-time Totals Preserved**: Total Clients, Invoices, Products, Employees show cumulative data

#### Business Intelligence Features
- **✅ Current Month Revenue**: Monthly revenue with net amounts after discounts
- **✅ Revenue Growth Tracking**: Month-over-month growth with discount impact comparison
- **✅ Employee Performance**: Current month sales performance with discount-adjusted metrics
- **✅ Top Client Analysis**: Current month's highest value client with net calculations
- **✅ Product Performance**: Current month top-selling products with revenue tracking
- **✅ Pending Invoice Management**: Current month pending invoices with discount tracking

#### Advanced Discount System
- **✅ Client-Specific Discounts**: Individual discount rates stored in Clients table
- **✅ Automatic Calculations**: System-wide discount application in all financial calculations
- **✅ Discount Analytics**: Comprehensive discount impact reporting and visualization
- **✅ Net vs Gross Reporting**: Clear distinction between gross and net revenue throughout system
- **✅ Recent Invoice Display**: Current month invoices with detailed discount breakdown

#### Technical Implementation
- **✅ Optimized Database Queries**: Efficient current month data filtering using Oracle date functions
- **✅ Performance Optimization**: Proper indexing and query optimization for date-based filtering
- **✅ Real-time Calculations**: Live discount and net revenue calculations without caching delays
- **✅ Responsive UI**: Enhanced dashboard visualization with discount impact charts
- **✅ Data Integrity**: Consistent discount application across all business metrics

### 🎯 Key Business Benefits Achieved

1. **Real-time Current Month Insights**: Focus on September 2025 performance for actionable business intelligence
2. **Accurate Financial Reporting**: Net revenue calculations with client-specific discount impact
3. **Enhanced Client Management**: Automatic discount application improves client relationship management
4. **Performance Tracking**: Current month employee and product performance with discount considerations
5. **Business Intelligence**: Comprehensive dashboard with discount-adjusted metrics for informed decision making

### 📊 Dashboard Metrics Overview

| Metric Category | Data Period | Discount Integration | Status |
|----------------|-------------|---------------------|--------|
| **Total Counts** | All-time | N/A | ✅ Complete |
| **Monthly Revenue** | Current Month | Client-specific | ✅ Complete |
| **Recent Invoices** | Current Month | Full discount details | ✅ Complete |
| **Employee Performance** | Current Month | Discount-adjusted | ✅ Complete |
| **Top Products** | Current Month | Net revenue based | ✅ Complete |
| **Top Clients** | Current Month | Net value calculated | ✅ Complete |
| **Discount Analysis** | Current Month | Comprehensive impact | ✅ Complete |
| **Pending Invoices** | Current Month | Discount tracking | ✅ Complete |

---

## Database Schema

### Entity Relationship Overview
The database follows a normalized relational design with proper foreign key relationships:

```
Client_Type (1:N) ──→ Clients (1:N) ──→ Invoices (1:N) ──→ Invoice_Details
      │                                                            │
      │                                                            │ (N:1)
      └── Product_Type (1:N) ──→ Products ──────────────────────────┘
                                     │
Jobs (1:N) ──→ Employees (1:N) ──────┘
```

### Core Tables

#### Client_Type Table
**Purpose**: Define client categories with default discount rates
- `TYPE_ID` (Primary Key) - Auto-generated unique identifier
- `TYPE_NAME` - Client category name (Premium Corporate, Government, etc.)
- `DISCOUNT_RATE` - Default discount percentage for this client type
- `REMARKS` - Description and additional notes about the client type

#### Clients Table
**Purpose**: Store customer information and contact details
- `CLIENT_NO` (Primary Key) - Auto-generated client number
- `CLIENTNAME` - Company or individual customer name
- `ADDRESS` - Street address for billing and shipping
- `CITY` - City location for regional analysis
- `PHONE` - Primary contact phone number
- `CLIENT_TYPE` (Foreign Key) - References Client_Type table
- `DISCOUNT` - Custom discount rate (may override default)

#### Product_Type Table
**Purpose**: Categorize products for inventory management
- `PRODUCTTYPE_ID` (Primary Key) - Auto-generated category identifier
- `PRODUCTTYPE_NAME` - Category name (Electronics, Furniture, Software, etc.)
- `REMARKS` - Category description and classification notes

#### Products Table
**Purpose**: Complete product catalog with pricing and inventory
- `PRODUCT_NO` (Primary Key) - Unique product identifier code
- `PRODUCTNAME` - Descriptive product name
- `PRODUCTTYPE` (Foreign Key) - References Product_Type table
- `PROFIT_PERCENT` - Markup percentage for pricing calculations
- `UNIT_MEASURE` - Measurement unit (pieces, meters, licenses, etc.)
- `REORDER_LEVEL` - Minimum stock level before reordering
- `SELL_PRICE` - Current selling price per unit
- `COST_PRICE` - Cost price for profit margin calculations
- `QTY_ON_HAND` - Current inventory quantity available

#### Jobs Table
**Purpose**: Define employee positions and salary ranges
- `JOB_ID` (Primary Key) - Auto-generated job identifier
- `JOB_TITLE` - Position name and role description
- `MIN_SALARY` - Minimum salary range for this position
- `MAX_SALARY` - Maximum salary range for this position

#### Employees Table
**Purpose**: Staff information and employment details
- `EMPLOYEEID` (Primary Key) - Auto-generated employee identifier
- `EMPLOYEENAME` - Full employee name
- `GENDER` - Gender information
- `BIRTHDATE` - Date of birth for age calculations
- `JOB_ID` (Foreign Key) - References Jobs table
- `ADDRESS` - Employee home address
- `PHONE` - Personal contact phone number
- `SALARY` - Current salary amount
- `REMARKS` - Additional notes about the employee

#### Invoices Table
**Purpose**: Invoice header information and status tracking
- `INVOICENO` (Primary Key) - Auto-generated invoice number
- `INVOICE_DATE` - Transaction date for the invoice
- `CLIENT_NO` (Foreign Key) - References Clients table
- `EMPLOYEEID` (Foreign Key) - References Employees table (sales representative)
- `INVOICE_STATUS` - Current status (Pending, Confirmed, Shipped, Delivered, Cancelled)
- `INVOICEMEMO` - Additional notes and comments for the invoice

#### Invoice_Details Table
**Purpose**: Line items for each invoice with product details
- `INVOICENO` (Foreign Key) - References Invoices table
- `PRODUCT_NO` (Foreign Key) - References Products table
- `QTY` - Quantity of product ordered
- `PRICE` - Unit price at the time of sale (may differ from current price)

---

## Web Application Features

### Main Application Pages

#### Enhanced Dashboard (index.php) - v2.0.0
**Purpose**: Real-time business intelligence with current month focus
- **Current Month Analytics**: September 2025 focused metrics for all business indicators
- **Client Discount Integration**: Automatic discount calculations using Clients table DISCOUNT column
- **Net Revenue Tracking**: Real-time net revenue calculations after client-specific discounts
- **Discount Impact Analysis**: Visual representation of discount effects on business metrics
- **Employee Performance**: Current month sales tracking with discount-adjusted calculations
- **Top Performers**: Current month top clients and products with net value calculations
- **Recent Activity**: Current month invoices with detailed discount information
- **All-time Totals**: Total Clients, Invoices, Products, and Employees (cumulative counts)
- **Responsive Design**: Enhanced mobile-friendly interface with improved visualization

#### Invoice Management (invoices.php)
**Purpose**: Complete invoice lifecycle management
- **Create New Invoices**: Add products, calculate totals with client discounts
- **View Invoice List**: Searchable and filterable invoice history
- **Edit Existing Invoices**: Modify details while maintaining audit trail
- **Invoice Details**: Complete line item display with calculations
- **Status Management**: Update invoice status through workflow
- **Print Functionality**: Generate printable invoice documents

#### Client Management (clients.php)
**Purpose**: Customer relationship management
- **Client Directory**: Comprehensive list of all customers
- **Add New Clients**: Complete contact information and type assignment
- **Edit Client Information**: Update contact details and discount rates
- **Client Type Management**: Assign and modify client categories
- **Transaction History**: View all invoices for specific clients
- **Discount Rate Management**: Custom pricing for individual clients

#### Product Catalog (products.php)
**Purpose**: Inventory and product information management
- **Product Inventory**: Complete catalog with search and filter capabilities
- **Add New Products**: Full product information including pricing and categories
- **Edit Product Details**: Update pricing, descriptions, and inventory levels
- **Category Management**: Organize products by type and category
- **Stock Level Monitoring**: Track inventory and reorder alerts
- **Price Management**: Cost and selling price tracking with profit margins

#### Employee Portal (employees.php)
**Purpose**: Human resources and staff management
- **Employee Directory**: Complete staff listing with contact information
- **Add New Employees**: Full employment details including job assignments
- **Edit Employee Information**: Update personal and employment details
- **Job Role Management**: Assign positions and salary information
- **Performance Tracking**: Sales assignments and productivity metrics
- **Salary Administration**: Compensation tracking and management

#### Business Reports (reports.php)
**Purpose**: Advanced business intelligence and analytics with discount integration
- **Current Month Financial Reports**: September 2025 revenue, profit, and performance metrics with discount impact
- **Discount Analysis Reports**: Client-specific discount utilization and revenue impact analysis
- **Net Revenue Analytics**: Product performance and sales trends with discount calculations
- **Client Profitability Analysis**: Customer activity and net profitability after discounts
- **Employee Productivity Reports**: Current month sales performance with commission calculations
- **Inventory Reports**: Stock levels, reorder requirements, and current month turnover
- **Discount Impact Studies**: Comprehensive analysis of discount effects on business metrics
- **Custom Date Range Reports**: Flexible reporting periods with discount integration
- **Tab State Persistence**: Active report tabs remain open after page refresh
- **Enhanced Export Functionality**: Download current month reports with discount analysis in multiple formats

### Advanced Features

#### Export Reports (export_reports.php)
**Purpose**: Data export and external reporting
- **Multiple Export Formats**: CSV, PDF, Excel compatibility
- **Custom Date Ranges**: Flexible reporting periods
- **Filtered Data Export**: Export specific data subsets
- **Scheduled Reports**: Automated report generation
- **Email Integration**: Send reports to stakeholders

#### Database Information (database_info.php)
**Purpose**: System monitoring and diagnostics
- **Connection Status**: Real-time database connectivity monitoring
- **Database Version Information**: Oracle version and configuration details
- **System Performance Metrics**: Response times and resource usage
- **Configuration Display**: Current database settings and parameters
- **Health Check Status**: Overall system health indicators

---

## Docker Configuration

### Container Architecture

The system uses a two-container architecture with Docker Compose orchestration:

#### Oracle Database Container (oracle-db)
**Configuration Details**:
- **Base Image**: `container-registry.oracle.com/database/express:21.3.0-xe`
- **Container Name**: `oracle-xe-db`
- **Port Mapping**: `1522:1521` (host:container)
- **Environment Variables**: 
  - `ORACLE_PWD=Oracle123` (configurable)
- **Volume Mounts**:
  - `oracle-data:/opt/oracle/oradata` (data persistence)
  - `./init-scripts:/docker-entrypoint-initdb.d/startup` (initialization)
- **Network**: `oracle-network` for internal communication
- **Resource Requirements**: 4GB+ RAM, 20GB+ storage

#### PHP Web Application Container (web-app)
**Configuration Details**:
- **Build Context**: `./web-app` (custom Dockerfile)
- **Container Name**: `php-web-app`
- **Port Mapping**: `8090:80` (host:container)
- **Dependencies**: `oracle-db` (startup order)
- **Volume Mounts**:
  - `./web-app/public:/var/www/html` (application files)
  - `./web-app/config:/var/www/html/config` (configuration)
  - `./web-app/includes:/var/www/html/includes` (shared components)
  - `./web-app/assets:/var/www/html/assets` (static assets)
- **Network**: `oracle-network` for database connectivity

### Container Build Process

#### PHP Container Build Steps:
1. **Base Image**: Ubuntu 22.04 LTS for stability
2. **System Packages**: Apache 2.4, PHP 8.1, development tools
3. **Oracle Instant Client**: Version 21.8 for database connectivity
4. **PHP Extensions**: OCI8 extension compilation and configuration
5. **Apache Configuration**: Virtual host setup and PHP integration
6. **Application Setup**: File permissions and directory structure
7. **Service Configuration**: Apache and PHP service startup

#### Build Command:
```dockerfile
FROM ubuntu:22.04
ENV DEBIAN_FRONTEND=noninteractive

# Install system packages
RUN apt-get update && apt-get install -y \
    apache2 php8.1 php8.1-dev libapache2-mod-php8.1 \
    libaio1 unzip wget curl build-essential

# Oracle Instant Client installation
RUN wget https://download.oracle.com/otn_software/linux/instantclient/218000/instantclient-basic-linux.x64-21.8.0.0.0dbru.zip
# ... (additional build steps)
```

---

## Configuration Files

### Database Configuration (config/database.php)
**Purpose**: Oracle database connection parameters
```php
<?php
// Oracle Database Connection Settings
$host = "oracle-db";           // Container hostname
$port = "1521";                // Oracle listener port
$service_name = "XE";          // Oracle service name
$username = "invoiceuser";     // Application database user
$password = "invoice123";      // Application user password

// Connection string construction
$connection_string = "(DESCRIPTION=
    (ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))
    (CONNECT_DATA=(SERVICE_NAME=$service_name))
)";
?>
```

### Apache Virtual Host Configuration (apache-vhost.conf)
**Purpose**: Web server configuration for PHP application
```apache
<VirtualHost *:80>
    DocumentRoot /var/www/html
    ServerName localhost
    
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # PHP Configuration
    DirectoryIndex index.php index.html
    
    # Error and Access Logging
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

### Docker Compose Configuration (docker-compose.yml)
**Purpose**: Container orchestration and service definition
```yaml
version: '3.8'
services:
  oracle-db:
    image: container-registry.oracle.com/database/express:21.3.0-xe
    container_name: oracle-xe-db
    ports:
      - "1522:1521"
    environment:
      - ORACLE_PWD=Oracle123
    volumes:
      - oracle-data:/opt/oracle/oradata
      - ./init-scripts:/docker-entrypoint-initdb.d/startup
    networks:
      - oracle-network

  web-app:
    build: ./web-app
    container_name: php-web-app
    ports:
      - "8090:80"
    depends_on:
      - oracle-db
    volumes:
      - ./web-app/public:/var/www/html
      - ./web-app/config:/var/www/html/config
      - ./web-app/includes:/var/www/html/includes
      - ./web-app/assets:/var/www/html/assets
    networks:
      - oracle-network

volumes:
  oracle-data:

networks:
  oracle-network:
```

---

## Sample Data

The system includes comprehensive sample data for immediate testing and demonstration:

### Client Types (5 Records)
1. **Premium Corporate** - 15% discount rate for high-volume corporate clients
2. **Standard Corporate** - 10% discount rate for regular corporate clients  
3. **Small Business** - 8% discount rate for small business clients
4. **Government** - 12% discount rate for government agencies
5. **Non-Profit** - 20% discount rate for non-profit organizations

### Sample Clients (5 Records)
1. **TechCorp Solutions** (Premium Corporate) - New York technology company
2. **Global Industries** (Standard Corporate) - Los Angeles manufacturing company
3. **City Hospital** (Small Business) - Chicago healthcare facility
4. **Metro University** (Government) - Boston educational institution
5. **SmallBiz Inc** (Non-Profit) - Austin small business

### Product Categories (5 Records)
1. **Electronics** - Electronic devices and components
2. **Office Supplies** - General office supplies and stationery
3. **Furniture** - Office and commercial furniture
4. **Software** - Software licenses and digital tools
5. **Hardware** - Computer hardware components

### Sample Products (10 Records)
1. **LAPTOP001** - Business Laptop Pro ($1,200.00) - Electronics
2. **DESK001** - Executive Office Desk ($800.00) - Furniture
3. **PAPER001** - Premium Copy Paper ($12.00) - Office Supplies
4. **SW001** - Office Suite License ($300.00) - Software
5. **ROUTER001** - Enterprise Router ($450.00) - Hardware
6. **CHAIR001** - Ergonomic Office Chair ($350.00) - Furniture
7. **PRINTER001** - Laser Printer 3000 ($550.00) - Electronics
8. **HDD001** - External Hard Drive 2TB ($120.00) - Hardware
9. **MOUSE001** - Wireless Mouse Pro ($65.00) - Electronics
10. **CABLE001** - Network Cable Cat6 ($8.00) - Hardware

### Employee Positions (5 Records)
1. **Software Developer** - Salary range: $50,000 - $90,000
2. **Project Manager** - Salary range: $70,000 - $120,000
3. **Database Administrator** - Salary range: $60,000 - $100,000
4. **System Analyst** - Salary range: $55,000 - $85,000
5. **HR Manager** - Salary range: $50,000 - $80,000

### Sample Employees (5 Records)
1. **John Smith** - Senior Software Developer, $75,000 salary
2. **Sarah Johnson** - Experienced Project Manager, $95,000 salary
3. **Michael Brown** - Oracle Database Administrator, $80,000 salary
4. **Emily Davis** - Business Systems Analyst, $70,000 salary
5. **David Wilson** - HR Team Lead, $65,000 salary

### Sample Invoices (20+ Records)
Complete invoice history with current month focus including:
- **Various Invoice Statuses**: Pending, Shipped, Delivered, Cancelled
- **Current Month Data**: September 2025 invoices prominently featured in dashboard
- **Historical Data**: Previous months available for growth comparison and reporting
- **Client Discount Application**: All invoices reflect client-specific discount rates
- **Multiple Client Assignments**: All sample clients represented with discount calculations
- **Diverse Product Combinations**: Various product types and quantities with discount impact
- **Realistic Transaction Amounts**: Range from hundreds to thousands of dollars with net calculations
- **Employee Sales Tracking**: All employees assigned to invoices with discount-adjusted performance metrics
- **Discount Transparency**: Clear display of gross amounts, discount amounts, and net totals

---

## Development Guide

### Local Development Setup

#### Prerequisites for Development
- Docker Desktop with development mode enabled
- Code editor with PHP and SQL syntax support
- Git for version control
- Oracle SQL Developer or similar database tool (optional)

#### Development Workflow
1. **Clone Repository**: `git clone https://github.com/chisaelim/oracleDocker.git`
2. **Start Development Environment**: `docker-compose up -d`
3. **Modify Source Files**: Changes in `./web-app/` directory reflect immediately
4. **Test Changes**: Access application at http://localhost:8090
5. **Database Changes**: Modify SQL files and restart containers if needed

### Adding New Features

#### Database Schema Changes
1. **Modify Table Structure**: Edit `02-create-tables.sql`
2. **Update Sample Data**: Modify `03-insert-sample-data.sql`
3. **Restart Database**: `docker-compose restart oracle-db`
4. **Verify Changes**: Check database through web interface

#### PHP Application Changes
1. **New Pages**: Add PHP files to `web-app/public/`
2. **Shared Components**: Add reusable code to `web-app/includes/`
3. **Database Configuration**: Update connection settings in `web-app/config/`
4. **Styling and Scripts**: Modify files in `web-app/assets/`

#### Testing New Features
```bash
# View application logs
docker-compose logs web-app

# Access container for debugging
docker exec -it php-web-app bash

# Test database connectivity
docker exec -it php-web-app php -r "echo 'PHP version: ' . phpversion();"
```

### Code Organization Best Practices

#### PHP Code Structure
- **Separation of Concerns**: Keep database logic separate from presentation
- **Error Handling**: Implement proper error handling for database operations
- **Input Validation**: Validate all user inputs to prevent security issues
- **Code Reusability**: Use include files for common functionality

#### Database Best Practices
- **Prepared Statements**: Use parameterized queries to prevent SQL injection
- **Transaction Management**: Implement proper transaction handling
- **Index Optimization**: Ensure proper indexing for query performance
- **Data Validation**: Implement constraints at the database level

---

## Security Considerations

### Production Deployment Security

#### Authentication and Authorization
- **User Authentication**: Implement secure login system for production use
- **Role-Based Access Control**: Define user roles and permissions
- **Session Management**: Secure session handling with proper timeouts
- **Password Policies**: Enforce strong password requirements

#### Database Security
- **Change Default Passwords**: Replace all default passwords with strong alternatives
- **User Privilege Management**: Grant minimum required privileges to application users
- **Connection Encryption**: Enable SSL/TLS for database connections
- **Regular Security Updates**: Keep Oracle database updated with security patches

#### Application Security
- **Input Sanitization**: Validate and sanitize all user inputs
- **SQL Injection Prevention**: Use prepared statements exclusively
- **Cross-Site Scripting (XSS) Protection**: Implement output encoding
- **HTTPS Implementation**: Use SSL certificates for encrypted communication

#### Infrastructure Security
- **Firewall Configuration**: Restrict database access to application servers only
- **Network Security**: Use private networks for container communication
- **Regular Backups**: Implement encrypted backup procedures
- **Audit Logging**: Enable comprehensive audit trails

### Security Configuration Examples

#### Secure Database Connection
```php
// Secure connection with error handling
try {
    $connection = oci_connect($username, $password, $connection_string);
    if (!$connection) {
        $error = oci_error();
        throw new Exception("Database connection failed: " . $error['message']);
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    // Handle error appropriately
}
```

#### Input Validation Example
```php
// Secure input handling
function validateInput($input, $type) {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
        case 'number':
            return filter_var($input, FILTER_VALIDATE_INT);
        case 'string':
            return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
        default:
            return false;
    }
}
```

---

## Performance Optimization

### Database Performance

#### Query Optimization
- **Index Management**: Ensure proper indexing on frequently queried columns
- **Query Analysis**: Use Oracle's execution plan analysis tools
- **Prepared Statements**: Reduce parsing overhead with prepared statements
- **Connection Pooling**: Implement connection pooling for high-traffic scenarios

#### Database Configuration
```sql
-- Example index creation for performance
CREATE INDEX idx_invoices_date ON INVOICES(INVOICE_DATE);
CREATE INDEX idx_invoice_details_invoice ON INVOICE_DETAILS(INVOICENO);
CREATE INDEX idx_clients_type ON CLIENTS(CLIENT_TYPE);
```

### Application Performance

#### PHP Optimization
- **Opcode Caching**: Enable OPcache for improved PHP performance
- **Memory Management**: Optimize memory usage for large datasets
- **Efficient Queries**: Minimize database round trips
- **Caching Strategy**: Implement caching for frequently accessed data

#### Web Server Optimization
```apache
# Apache performance tuning
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule expires_module modules/mod_expires.so

# Enable compression
LoadModule deflate_module modules/mod_deflate.so
<Location />
    SetOutputFilter DEFLATE
</Location>

# Set cache headers
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### Resource Monitoring

#### Container Resource Usage
```bash
# Monitor container performance
docker stats

# Check memory usage
docker exec oracle-xe-db free -h

# Monitor disk usage
docker system df
```

---

## Monitoring and Maintenance

### Health Check Procedures

#### System Health Monitoring
```bash
# Check all container status
docker-compose ps

# Verify database connectivity
curl -f http://localhost:8090/database_info.php || exit 1

# Check disk space
docker system df
```

#### Application Health Checks
- **Database Connection**: Automated connectivity testing
- **Response Time Monitoring**: Track application response times
- **Error Rate Monitoring**: Monitor application error logs
- **Resource Usage**: Track CPU, memory, and disk usage

### Backup and Recovery

#### Database Backup Procedures
```bash
# Export database schema and data
docker exec oracle-xe-db expdp invoiceuser/invoice123 \
    schemas=invoiceuser directory=DATA_PUMP_DIR \
    dumpfile=backup_$(date +%Y%m%d).dmp

# Backup application files
tar -czf app_backup_$(date +%Y%m%d).tar.gz web-app/
```

#### Recovery Procedures
```bash
# Restore database from backup
docker exec oracle-xe-db impdp invoiceuser/invoice123 \
    schemas=invoiceuser directory=DATA_PUMP_DIR \
    dumpfile=backup_YYYYMMDD.dmp

# Restore application files
tar -xzf app_backup_YYYYMMDD.tar.gz
```

### Maintenance Tasks

#### Regular Maintenance Schedule
- **Daily**: Monitor system health and error logs
- **Weekly**: Review performance metrics and optimize queries
- **Monthly**: Update security patches and backup verification
- **Quarterly**: Full system performance review and optimization

#### Log Management
```bash
# Application logs
docker-compose logs --tail=100 web-app

# Database logs
docker exec oracle-xe-db tail -f /opt/oracle/diag/rdbms/xe/XE/trace/alert_XE.log

# System logs
docker logs oracle-xe-db --since=24h
```

---

## Troubleshooting

### Common Issues and Solutions

#### Oracle Container Startup Issues

**Problem**: Oracle container exits immediately or fails to start
**Symptoms**: 
- Container status shows "Exited" immediately after startup
- Error messages about insufficient memory
- Port binding failures

**Solutions**:
1. **Increase Docker Memory**: Allocate at least 8GB to Docker Desktop
2. **Check Port Availability**: Ensure port 1522 is not in use
   ```bash
   netstat -an | grep 1522
   ```
3. **Verify Oracle Image**: Ensure proper Oracle image download
   ```bash
   docker pull container-registry.oracle.com/database/express:21.3.0-xe
   ```
4. **Check Disk Space**: Ensure sufficient disk space (20GB+)
   ```bash
   docker system df
   ```

#### Database Connection Issues

**Problem**: Web application cannot connect to Oracle database
**Symptoms**:
- "Cannot connect to database" error messages
- Database info page shows connection failed
- Application pages show database errors

**Solutions**:
1. **Wait for Database Initialization**: Oracle requires 10+ minutes for first startup
   ```bash
   docker-compose logs -f oracle-db
   # Wait for "DATABASE IS READY TO USE!"
   ```
2. **Verify Network Connectivity**: Check Docker network configuration
   ```bash
   docker network ls
   docker network inspect oracledocker_oracle-network
   ```
3. **Check Database Credentials**: Verify username/password in config files
4. **Test Manual Connection**: Use SQL client to test connectivity
   ```bash
   sqlplus invoiceuser/invoice123@localhost:1522/XE
   ```

#### PHP and OCI8 Extension Issues

**Problem**: PHP cannot load OCI8 extension for Oracle connectivity
**Symptoms**:
- "Call to undefined function oci_connect()" errors
- PHP info shows OCI8 extension not loaded
- Oracle functions not available in PHP

**Solutions**:
1. **Rebuild Web Application Container**:
   ```bash
   docker-compose build --no-cache web-app
   docker-compose up -d
   ```
2. **Verify Oracle Instant Client Installation**: Check container build logs
3. **Test PHP Extension Loading**:
   ```bash
   docker exec php-web-app php -m | grep oci8
   ```
4. **Check PHP Configuration**: Verify extension loading in php.ini

#### File Permission Issues

**Problem**: Web application cannot read/write files
**Symptoms**:
- Permission denied errors in logs
- Unable to upload files
- Configuration files not readable

**Solutions**:
1. **Fix File Ownership**:
   ```bash
   docker exec php-web-app chown -R www-data:www-data /var/www/html
   ```
2. **Set Proper Permissions**:
   ```bash
   docker exec php-web-app chmod -R 755 /var/www/html
   docker exec php-web-app chmod -R 644 /var/www/html/*.php
   ```
3. **Check Volume Mounts**: Verify Docker volume mounting in docker-compose.yml

### Diagnostic Commands

#### Container Diagnostics
```bash
# Check container status and resource usage
docker-compose ps
docker stats

# View container logs
docker-compose logs web-app --tail=50
docker-compose logs oracle-db --tail=50

# Access container shells for debugging
docker exec -it php-web-app bash
docker exec -it oracle-xe-db bash
```

#### Network Diagnostics
```bash
# Test network connectivity between containers
docker exec php-web-app ping oracle-db

# Check network configuration
docker network inspect oracledocker_oracle-network

# Test port connectivity
docker exec php-web-app telnet oracle-db 1521
```

#### Database Diagnostics
```bash
# Connect to Oracle database
docker exec -it oracle-xe-db sqlplus / as sysdba

# Check database status
SELECT instance_name, status FROM v$instance;

# Check user connections
SELECT username, count(*) FROM v$session GROUP BY username;
```

### Performance Troubleshooting

#### Slow Application Response
1. **Check Resource Usage**: Monitor CPU and memory consumption
2. **Analyze Database Queries**: Identify slow-running queries
3. **Review Error Logs**: Look for bottlenecks and errors
4. **Optimize Queries**: Add indexes or rewrite inefficient queries

#### High Resource Consumption
1. **Monitor Container Stats**: Use `docker stats` to identify resource usage
2. **Database Tuning**: Optimize Oracle memory parameters
3. **Application Optimization**: Review PHP code for efficiency
4. **Scale Resources**: Increase allocated memory/CPU if needed

### Current Month Data Issues

#### Dashboard Shows No Current Month Data
**Problem**: Dashboard displays zero values for current month metrics
**Symptoms**:
- Monthly revenue shows $0
- Recent invoices section is empty
- Employee performance shows no data
- No current month statistics

**Solutions**:
1. **Check Current Date Settings**: Verify system date is September 2025
   ```bash
   docker exec php-web-app date
   ```
2. **Verify Sample Data**: Ensure sample invoices exist for September 2025
   ```sql
   SELECT COUNT(*) FROM Invoices 
   WHERE EXTRACT(MONTH FROM INVOICE_DATE) = 9 
   AND EXTRACT(YEAR FROM INVOICE_DATE) = 2025;
   ```
3. **Update Sample Data**: Add current month test data if needed
4. **Check Oracle Date Functions**: Verify SYSDATE returns correct date

#### Discount Calculations Not Displaying
**Problem**: Client discounts not appearing in calculations
**Symptoms**:
- Invoice amounts show gross values only
- Discount columns show "None" or zero
- Net revenue equals gross revenue
- Discount analysis section shows no impact

**Solutions**:
1. **Verify Client Discount Data**: Check DISCOUNT column in Clients table
   ```sql
   SELECT CLIENTNAME, DISCOUNT FROM Clients WHERE DISCOUNT > 0;
   ```
2. **Check Discount Calculations**: Verify discount logic in SQL queries
3. **Update Client Data**: Add discount rates to sample clients if missing
4. **Test Discount Display**: Verify discount formatting in PHP output

---

## Additional Resources

### Documentation and Learning Materials

#### Oracle Database Resources
- [Oracle Database XE Documentation](https://docs.oracle.com/en/database/oracle/oracle-database/21/xeinl/)
- [Oracle SQL Developer Guide](https://docs.oracle.com/en/database/oracle/sql-developer/)
- [PL/SQL Language Reference](https://docs.oracle.com/en/database/oracle/oracle-database/21/lnpls/)
- [Oracle Database Performance Tuning Guide](https://docs.oracle.com/en/database/oracle/oracle-database/21/tgdba/)

#### PHP and Web Development
- [PHP Official Documentation](https://www.php.net/docs.php)
- [PHP OCI8 Extension Manual](https://www.php.net/manual/en/book.oci8.php)
- [Bootstrap Framework Documentation](https://getbootstrap.com/docs/)
- [Apache HTTP Server Documentation](https://httpd.apache.org/docs/)

#### Docker and Containerization
- [Docker Official Documentation](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/)
- [Docker Best Practices](https://docs.docker.com/develop/best-practices/)
- [Container Security Best Practices](https://docs.docker.com/engine/security/)

### Online Learning Resources

#### Recommended Courses and Tutorials
- **Oracle Database Fundamentals**: Understanding relational database concepts
- **PHP Web Development**: Server-side programming with PHP
- **Docker Containerization**: Container orchestration and deployment
- **Bootstrap UI Framework**: Responsive web design principles
- **SQL Query Optimization**: Database performance tuning

#### Community Resources
- **Stack Overflow**: Technical questions and community support
- **Oracle Community Forums**: Database-specific discussions
- **PHP Community**: PHP development best practices and support
- **Docker Community**: Container deployment and troubleshooting

### Tools and Utilities

#### Development Tools
- **Oracle SQL Developer**: Database management and query development
- **Visual Studio Code**: Code editor with PHP and SQL extensions
- **Postman**: API testing and development
- **Docker Desktop**: Container management interface

#### Database Tools
- **Oracle Enterprise Manager**: Advanced database administration
- **Toad for Oracle**: Database development and administration
- **DBeaver**: Universal database tool with Oracle support
- **SQL*Plus**: Command-line Oracle database interface

---

## Contributing

### Development Workflow

#### Getting Started with Contributions
1. **Fork the Repository**: Create a personal fork on GitHub
2. **Clone Your Fork**: 
   ```bash
   git clone https://github.com/yourusername/oracleDocker.git
   cd oracleDocker
   ```
3. **Create Feature Branch**: 
   ```bash
   git checkout -b feature/your-feature-name
   ```
4. **Set Up Development Environment**: Follow the Quick Start Guide
5. **Make Your Changes**: Implement features or fixes
6. **Test Thoroughly**: Ensure all functionality works correctly
7. **Commit Changes**: 
   ```bash
   git add .
   git commit -m "Add detailed description of changes"
   ```
8. **Push to Your Fork**: 
   ```bash
   git push origin feature/your-feature-name
   ```
9. **Create Pull Request**: Submit PR with detailed description

### Code Standards and Guidelines

#### PHP Coding Standards
- **PSR-12 Compliance**: Follow PHP Standards Recommendations
- **Error Handling**: Implement proper exception handling
- **Documentation**: Comment complex logic and functions
- **Security**: Use prepared statements and input validation

#### SQL Coding Standards
- **Uppercase Keywords**: Use uppercase for SQL keywords (SELECT, FROM, WHERE)
- **Consistent Formatting**: Proper indentation and line breaks
- **Meaningful Names**: Use descriptive table and column names
- **Comments**: Document complex queries and business logic

#### JavaScript and CSS Standards
- **ES6+ Features**: Use modern JavaScript features
- **Consistent Formatting**: Proper indentation and semicolons
- **Error Handling**: Implement proper error handling for AJAX calls
- **CSS Organization**: Organized structure with meaningful class names

### Contribution Areas

#### High-Priority Contribution Areas
- **User Authentication System**: Implement secure login functionality
- **Advanced Reporting Features**: Additional business intelligence reports
- **API Development**: REST API for external integrations
- **Mobile Optimization**: Enhanced mobile user experience
- **Performance Optimization**: Query optimization and caching
- **Security Enhancements**: Additional security measures
- **Documentation Improvements**: Enhanced user guides and API documentation

#### Bug Reports and Feature Requests
- **Issue Reporting**: Use GitHub Issues for bug reports
- **Feature Requests**: Submit detailed feature proposals
- **Documentation Issues**: Report unclear or missing documentation
- **Performance Issues**: Report slow queries or application bottlenecks

### Testing Guidelines

#### Testing Requirements
- **Functional Testing**: Verify all features work as expected
- **Database Testing**: Ensure data integrity and relationships
- **Security Testing**: Test for common vulnerabilities
- **Performance Testing**: Verify acceptable response times
- **Cross-Browser Testing**: Test in multiple browsers

#### Testing Procedures
```bash
# Start test environment
docker-compose up -d

# Run basic functionality tests
curl -f http://localhost:8090/database_info.php
curl -f http://localhost:8090/index.php

# Test database connectivity
docker exec php-web-app php -r "
  \$conn = oci_connect('invoiceuser', 'invoice123', 'oracle-db:1521/XE');
  echo \$conn ? 'Connected' : 'Failed';
"
```

---

## License and Support

### License Information

This project is licensed under the **MIT License** - see the LICENSE file for complete details.

#### MIT License Summary
- **Commercial Use**: Permitted for commercial applications
- **Distribution**: Can be distributed and modified
- **Modification**: Modifications are allowed
- **Private Use**: Can be used privately
- **Liability**: No warranty or liability provided
- **License and Copyright Notice**: Must be included in all copies

### Support Channels

#### Community Support
- **GitHub Issues**: Primary support channel for bug reports and feature requests
- **GitHub Discussions**: Community discussions and general questions
- **Documentation**: Comprehensive documentation for common scenarios

#### Professional Support
- **Consulting Services**: Available for enterprise implementations
- **Custom Development**: Tailored solutions for specific business needs
- **Training Services**: Oracle and PHP development training
- **Maintenance Contracts**: Ongoing support and maintenance services

### Contact Information

#### Project Maintainers
- **Primary Maintainer**: [@chisaelim](https://github.com/chisaelim)
- **Email**: Available through GitHub profile
- **Response Time**: Issues typically reviewed within 48 hours

#### Community Guidelines
- **Be Respectful**: Maintain professional and respectful communication
- **Provide Details**: Include relevant information when reporting issues
- **Search First**: Check existing issues before creating new ones
- **Follow Templates**: Use provided issue and PR templates

### System Requirements Summary

#### Minimum Requirements
- **Operating System**: Windows 10+, macOS 10.15+, Ubuntu 18.04+
- **Memory**: 4GB RAM (8GB+ recommended)
- **Storage**: 20GB free disk space
- **Docker**: Docker Desktop 20.10+ with Docker Compose
- **Network**: Internet connection for initial image downloads

#### Recommended Configuration
- **Memory**: 8GB+ RAM for optimal Oracle performance
- **Storage**: 50GB+ SSD storage for better I/O performance
- **CPU**: Multi-core processor for container orchestration
- **Network**: High-speed internet for faster image downloads

---

## Conclusion

The Oracle Docker Invoice Management System (v2.0.0) provides a complete, production-ready solution for modern business invoice management with advanced discount integration and real-time analytics. With its containerized architecture, enterprise-grade database, current month focused dashboard, and intelligent client discount system, it offers:

- **Advanced Business Intelligence**: Real-time current month analytics with discount impact analysis
- **Client-Specific Discount Management**: Automatic discount calculations using flexible client-based rates
- **Scalability**: Designed to grow with your business needs while maintaining performance
- **Reliability**: Built on proven Oracle database technology with optimized current month data filtering
- **Maintainability**: Well-documented code with clear architecture and comprehensive feature documentation
- **Security**: Implements industry-standard security practices with enhanced input validation
- **Flexibility**: Easily customizable discount rates and reporting periods for specific business requirements
- **Performance**: Optimized queries for current month data with minimal database load

### What Makes This Solution Unique

1. **Current Month Focus**: Unlike traditional systems, this solution emphasizes current month performance for actionable business insights
2. **Integrated Discount System**: Client-specific discounts are automatically applied throughout all calculations and reports
3. **Net Revenue Transparency**: Clear separation between gross and net revenue with discount impact visualization
4. **Real-time Analytics**: Live calculations provide immediate insights into business performance and discount effectiveness
5. **Comprehensive Integration**: Discount calculations are seamlessly integrated into every aspect of the system

Whether you're looking to implement a complete invoice management solution with advanced discount capabilities, learn about containerized database applications, or study real-time business analytics implementation, this project provides a solid foundation with comprehensive documentation and community support.

**Experience the power of Oracle Database with intelligent discount management in a modern, containerized environment!**

### Ready to Deploy?

🚀 **Production Ready**: Current version (v2.0.0) includes all enterprise features  
📊 **Business Intelligence**: Real-time current month dashboard with discount analytics  
💰 **Discount Management**: Flexible client-specific discount system  
🔧 **Easy Setup**: Complete Docker containerization with one-command deployment  
📚 **Full Documentation**: Comprehensive guides for setup, usage, and customization  

**Start your intelligent invoice management journey today!**

---

*Last Updated: September 2025*  
*Version: 1.0.0*  
*Built with ❤️ using Oracle Database, PHP, and Docker*