# Oracle Database Tables Creation Summary

## ✅ Successfully Created Database Schema

Your Oracle Docker container now contains a complete business database schema with the following tables:

### 📊 Database Tables Created

1. **CLIENT_TYPE** (4 columns)
   - CLIENT_TYPE (Primary Key, Auto-generated)
   - TYPE_NAME (Unique, Not Null)
   - DISCOUNT_RATE (Default 0)
   - REMARKS

2. **CLIENTS** (7 columns)
   - CLIENT_NO (Primary Key, Auto-generated)
   - CLIENTNAME (Unique, Not Null)
   - ADDRESS, CITY, PHONE (Unique, Not Null)
   - CLIENT_TYPE (Foreign Key)
   - DISCOUNT

3. **PRODUCT_TYPE** (3 columns)
   - PRODUCTTYPE_ID (Primary Key, Auto-generated)
   - PRODUCTTYPE_NAME (Unique, Not Null)
   - REMARKS

4. **PRODUCTS** (10 columns)
   - PRODUCT_NO (Primary Key)
   - PRODUCTNAME (Unique, Not Null)
   - PRODUCTTYPE (Foreign Key)
   - PROFIT_PERCENT, UNIT_MEASURE, REORDER_LEVEL
   - SELL_PRICE, COST_PRICE (with CHECK constraints)
   - QTY_ON_HAND, PHOTO (BLOB)

5. **JOBS** (4 columns)
   - JOB_ID (Primary Key, Auto-generated)
   - JOB_TITLE (Unique, Not Null)
   - MIN_SALARY, MAX_SALARY (with CHECK constraint)

6. **EMPLOYEES** (10 columns)
   - EMPLOYEEID (Primary Key, Auto-generated)
   - EMPLOYEENAME, GENDER, BIRTHDATE
   - JOB_ID (Foreign Key)
   - ADDRESS, PHONE (Unique), SALARY
   - REMARKS, PHOTO (BLOB)

7. **INVOICES** (6 columns)
   - INVOICENO (Primary Key, Auto-generated)
   - INVOICE_DATE (Default SYSDATE)
   - CLIENT_NO, EMPLOYEEID (Foreign Keys)
   - INVOICE_STATUS, INVOICEMEMO

8. **INVOICE_DETAILS** (4 columns)
   - INVOICENO, PRODUCT_NO (Composite Primary Key, Foreign Keys)
   - QTY, PRICE

### 📈 Sample Data Populated

- **4 Client Types**: Regular, VIP, Corporate, Wholesale
- **5 Clients**: Various customer types with contact information
- **2 Product Types**: Electronics, Clothing
- **4 Products**: Smartphones, headphones, jeans, t-shirts
- **5 Job Positions**: Sales rep, manager, cashier, etc.
- **4 Employees**: Complete employee records with salaries
- **4 Invoices**: Sample transactions with different statuses
- **8 Invoice Details**: Line items for the invoices

### 🔗 Database Relationships

- Clients → Client_Type (Many-to-One)
- Products → Product_Type (Many-to-One)
- Employees → Jobs (Many-to-One)
- Invoices → Clients (Many-to-One)
- Invoices → Employees (Many-to-One)
- Invoice_Details → Invoices (Many-to-One)
- Invoice_Details → Products (Many-to-One)

### 🚀 Ready for Use

**Connection Details for SQL Developer/MS Access:**
- Host: `localhost`
- Port: `1522`
- Service: `XEPDB1`
- Username: `appuser`
- Password: `appuser123`

**Files Created:**
- `create-tables-fixed.sql` - Table creation script
- `insert-sample-data.sql` - Sample data insertion
- `query-sample-data.sql` - Data verification queries

### 💡 Usage Examples

You can now:
- Connect from SQL Developer using the provided credentials
- Set up ODBC connection for MS Access
- Run queries, reports, and data analysis
- Add more data using INSERT statements
- Create views, stored procedures, and triggers
- Export/import data as needed

### 📋 Test Queries

Try these sample queries:
```sql
-- View all invoices with customer information
SELECT i.INVOICENO, c.CLIENTNAME, i.INVOICE_DATE, i.INVOICE_STATUS
FROM INVOICES i JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO;

-- Calculate total sales by product
SELECT p.PRODUCTNAME, SUM(id.QTY * id.PRICE) as TOTAL_SALES
FROM INVOICE_DETAILS id JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
GROUP BY p.PRODUCTNAME;

-- Find products needing reorder
SELECT PRODUCTNAME, QTY_ON_HAND, REORDER_LEVEL
FROM Products 
WHERE QTY_ON_HAND <= REORDER_LEVEL;
```

Your Oracle database is now fully operational with a complete business schema and sample data! 🎉