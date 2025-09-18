# Oracle Business Admin Web Application

A modern, responsive web application for managing Oracle Database business data with full CRUD operations for Client Types and expandable architecture for other entities.

## 🎯 Features

### ✅ Implemented Features
- **Client Types Management**: Complete CRUD operations for the `Client_Type` table
- **Modern UI**: Bootstrap 5 with custom styling and responsive design
- **Oracle Database Integration**: Full OCI8 support for Oracle Database XE 21c
- **Dashboard**: Overview with statistics and quick actions
- **Database Monitoring**: Connection status and schema information
- **Security**: CSRF protection, input validation, and sanitization
- **Interactive Tables**: DataTables with search, sort, and pagination
- **User Experience**: SweetAlert2 confirmations, loading spinners, and flash messages

### 🚧 Expandable Architecture
The application is designed to easily add CRUD operations for other tables:
- **Clients Management** (ready to implement)
- **Products Management** (ready to implement)  
- **Employees Management** (ready to implement)
- **Invoices and Invoice Details** (ready to implement)

## 📁 Project Structure

```
web-app/
├── assets/                 # Static assets
│   ├── css/
│   │   └── style.css      # Custom styles with Oracle theme
│   └── js/
│       └── app.js         # Custom JavaScript functionality
├── config/                # Configuration files
│   ├── config.php         # Application configuration
│   └── database.php       # Database connection class
├── includes/              # Common PHP includes
│   ├── header.php         # HTML header and navigation
│   ├── footer.php         # HTML footer and scripts
│   └── utils.php          # Utility functions
├── public/                # Web pages
│   ├── index.php          # Dashboard
│   ├── client_types.php   # Client Types CRUD
│   ├── database_info.php  # Database information
│   ├── clients.php        # Clients (placeholder)
│   ├── products.php       # Products (placeholder)
│   └── employees.php      # Employees (placeholder)
├── Dockerfile             # Docker container configuration
├── apache-vhost.conf      # Apache virtual host configuration
└── README.md             # This file
```

## 🚀 Getting Started

### Prerequisites
- Docker and Docker Compose
- Oracle Database XE 21c (via docker-compose.yml in parent directory)
- At least 4GB RAM available

### Installation

1. **Start the Oracle Database**:
   ```bash
   # From the project root directory
   docker-compose up -d oracle-db
   ```

2. **Wait for Database Initialization**:
   ```bash
   # Check database logs
   docker-compose logs -f oracle-db
   ```
   Wait until you see "DATABASE IS READY TO USE!"

3. **Build and Start the Web Application**:
   ```bash
   docker-compose up -d web-app
   ```

4. **Access the Application**:
   - Open your browser to: http://localhost:8090
   - Default landing page: Dashboard with system overview

### Quick Test
1. Navigate to **Client Types** from the main navigation
2. Click "Add New Client Type"
3. Fill in the form:
   - Type Name: "Premium"
   - Discount Rate: 5.00
   - Remarks: "Premium customers"
4. Click "Save Client Type"
5. Verify the new client type appears in the table

## 📊 Database Schema

The application works with the following Oracle tables:

### Client_Type Table (Primary Focus)
```sql
CLIENT_TYPE    NUMBER(3,0)   -- Primary Key (Auto-generated)
TYPE_NAME      VARCHAR2(30)  -- Unique, Not Null
DISCOUNT_RATE  NUMBER(5,2)   -- Default 0
REMARKS        VARCHAR2(50)  -- Optional
```

### Related Tables (For Future Implementation)
- **Clients**: Customer information with foreign key to Client_Type
- **Products**: Product catalog with pricing and inventory
- **Employees**: Staff information with job assignments
- **Invoices**: Sales transactions
- **Invoice_Details**: Line items for invoices

## 🔧 Technical Details

### Technologies Used
- **Backend**: PHP 8.1 with OCI8 extension
- **Frontend**: HTML5, Bootstrap 5, jQuery
- **Database**: Oracle Database XE 21c
- **Web Server**: Apache 2.4
- **Containerization**: Docker

### Key Features

#### Security
- CSRF token validation on all forms
- Input sanitization and validation
- SQL injection prevention with prepared statements
- XSS protection with proper output encoding

#### User Experience
- Responsive design for mobile and desktop
- DataTables for advanced table functionality
- SweetAlert2 for elegant confirmations
- Loading spinners for better feedback
- Toast notifications for actions

#### Database Integration
- Singleton database connection pattern
- Transaction support with commit/rollback
- Error handling and logging
- Connection status monitoring

## 🎨 Customization

### Styling
The application uses a custom Oracle-themed color scheme:
- Primary: Oracle Blue (#1f4788)
- Secondary: Oracle Red (#c74634)
- Custom CSS variables for easy theming

### Adding New CRUD Pages

To add CRUD operations for another table (e.g., Products):

1. **Create the PHP page** (`public/products_crud.php`):
   ```php
   <?php
   // Copy structure from client_types.php
   // Modify table name and field mappings
   // Update validation rules
   ?>
   ```

2. **Update navigation** in `includes/header.php`:
   ```php
   <li class="nav-item">
       <a class="nav-link" href="products_crud.php">Products</a>
   </li>
   ```

3. **Add to dashboard statistics** in `public/index.php`

### Configuration
Edit `config/config.php` to modify:
- Database connection parameters
- Application settings
- Security configuration

## 🐛 Troubleshooting

### Common Issues

#### Database Connection Failed
```
Error: Connection failed: ORA-12514: TNS:listener does not currently know of service requested
```
**Solution**: Wait for Oracle database to fully initialize (can take 2-3 minutes)

#### OCI8 Extension Not Found
```
Error: Call to undefined function oci_connect()
```
**Solution**: Ensure the Docker container is built with OCI8 extension (check Dockerfile)

#### Permission Denied
```
Error: Permission denied
```
**Solution**: Ensure proper file permissions in Docker container

### Development Mode
For development, add to `config/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📈 Performance Optimization

### Implemented Optimizations
- Database connection pooling via singleton pattern
- Prepared statements for all queries
- CSS/JS minification ready
- Apache compression enabled
- Proper caching headers

### Monitoring
- Built-in database connection monitoring
- Error logging to PHP error log
- Performance metrics on dashboard

## 🔒 Security Considerations

### Implemented Security Measures
- CSRF protection on all forms
- Input validation and sanitization
- SQL injection prevention
- XSS protection
- Proper error handling (no sensitive data exposure)

### Additional Recommendations
- Implement user authentication and authorization
- Add rate limiting for API endpoints
- Use HTTPS in production
- Regular security updates

## 🤝 Contributing

### Code Style
- Follow PSR-12 coding standards
- Use meaningful variable and function names
- Add comments for complex logic
- Maintain consistent indentation

### Database Conventions
- Use uppercase for Oracle SQL keywords
- Follow existing naming patterns
- Add proper constraints and indexes
- Document schema changes

## 📝 License

This project is created for educational and demonstration purposes.

## 🆘 Support

For issues and questions:
1. Check the troubleshooting section above
2. Review Docker logs: `docker-compose logs web-app`
3. Check database connectivity: Visit `/database_info.php`
4. Verify Oracle database status: `docker-compose logs oracle-db`

---

**Created**: September 2025  
**Version**: 1.0.0  
**Compatibility**: Oracle Database XE 21c, PHP 8.1+, Modern Browsers