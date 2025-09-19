-- DROP ALL EXISTING TABLES AND CONSTRAINTS
-- Note: Tables must be dropped in reverse order due to foreign key dependencies

-- Drop tables in dependency order (child tables first)
BEGIN
    EXECUTE IMMEDIATE 'DROP TABLE INVOICE_DETAILS CASCADE CONSTRAINTS';
    DBMS_OUTPUT.PUT_LINE('Dropped table: INVOICE_DETAILS');
EXCEPTION
    WHEN OTHERS THEN
        IF SQLCODE != -942 THEN -- Table does not exist
            RAISE;
        END IF;
END;
/

BEGIN
    EXECUTE IMMEDIATE 'DROP TABLE INVOICES CASCADE CONSTRAINTS';
    DBMS_OUTPUT.PUT_LINE('Dropped table: INVOICES');
EXCEPTION
    WHEN OTHERS THEN
        IF SQLCODE != -942 THEN
            RAISE;
        END IF;
END;
/

BEGIN
    EXECUTE IMMEDIATE 'DROP TABLE Employees CASCADE CONSTRAINTS';
    DBMS_OUTPUT.PUT_LINE('Dropped table: Employees');
EXCEPTION
    WHEN OTHERS THEN
        IF SQLCODE != -942 THEN
            RAISE;
        END IF;
END;
/

BEGIN
    EXECUTE IMMEDIATE 'DROP TABLE JOBS CASCADE CONSTRAINTS';
    DBMS_OUTPUT.PUT_LINE('Dropped table: JOBS');
EXCEPTION
    WHEN OTHERS THEN
        IF SQLCODE != -942 THEN
            RAISE;
        END IF;
END;
/

BEGIN
    EXECUTE IMMEDIATE 'DROP TABLE Products CASCADE CONSTRAINTS';
    DBMS_OUTPUT.PUT_LINE('Dropped table: Products');
EXCEPTION
    WHEN OTHERS THEN
        IF SQLCODE != -942 THEN
            RAISE;
        END IF;
END;
/

BEGIN
    EXECUTE IMMEDIATE 'DROP TABLE Product_Type CASCADE CONSTRAINTS';
    DBMS_OUTPUT.PUT_LINE('Dropped table: Product_Type');
EXCEPTION
    WHEN OTHERS THEN
        IF SQLCODE != -942 THEN
            RAISE;
        END IF;
END;
/

BEGIN
    EXECUTE IMMEDIATE 'DROP TABLE Clients CASCADE CONSTRAINTS';
    DBMS_OUTPUT.PUT_LINE('Dropped table: Clients');
EXCEPTION
    WHEN OTHERS THEN
        IF SQLCODE != -942 THEN
            RAISE;
        END IF;
END;
/

BEGIN
    EXECUTE IMMEDIATE 'DROP TABLE Client_Type CASCADE CONSTRAINTS';
    DBMS_OUTPUT.PUT_LINE('Dropped table: Client_Type');
EXCEPTION
    WHEN OTHERS THEN
        IF SQLCODE != -942 THEN
            RAISE;
        END IF;
END;
/

-- Display cleanup summary
SELECT 'All existing tables and constraints dropped successfully!' AS STATUS FROM DUAL;

-- CREATE NEW TABLES
-- a. Table: Client_Type
CREATE TABLE Client_Type (
    CLIENT_TYPE NUMBER(3,0) GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    TYPE_NAME VARCHAR2(30) UNIQUE NOT NULL,
    DISCOUNT_RATE NUMBER(5,2) DEFAULT 0,
    REMARKS VARCHAR2(50)
);

-- b. Table: Clients
CREATE TABLE Clients (
    CLIENT_NO NUMBER(8,0) GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    CLIENTNAME VARCHAR2(50) UNIQUE NOT NULL,
    ADDRESS VARCHAR2(150),
    CITY VARCHAR2(50),
    PHONE VARCHAR2(15) UNIQUE NOT NULL,
    CLIENT_TYPE NUMBER(3,0),
    DISCOUNT NUMBER(5,2),
    FOREIGN KEY (CLIENT_TYPE) REFERENCES Client_Type(CLIENT_TYPE)
);

-- c. Table: Product_Type
CREATE TABLE Product_Type (
    PRODUCTTYPE_ID NUMBER(3,0) GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    PRODUCTTYPE_NAME VARCHAR2(50) UNIQUE NOT NULL,
    REMARKS VARCHAR2(30)
);

-- d. Table: Products
CREATE TABLE Products (
    PRODUCT_NO VARCHAR2(20) PRIMARY KEY,
    PRODUCTNAME VARCHAR2(40) UNIQUE NOT NULL,
    PRODUCTTYPE NUMBER(3,0),
    PROFIT_PERCENT NUMBER(5,2) NOT NULL,
    UNIT_MEASURE VARCHAR2(15) NOT NULL,
    REORDER_LEVEL NUMBER(3,0) NOT NULL,
    SELL_PRICE NUMBER(12,2) CHECK (SELL_PRICE >= 0) NOT NULL,
    COST_PRICE NUMBER(12,2) CHECK (COST_PRICE >= 0) NOT NULL,
    QTY_ON_HAND NUMBER(6,0),
    PHOTO VARCHAR2(255),
    FOREIGN KEY (PRODUCTTYPE) REFERENCES Product_Type(PRODUCTTYPE_ID)
);

-- e. Table: JOBS (Fixed CHECK constraint)
CREATE TABLE JOBS (
    JOB_ID NUMBER(3,0) GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    JOB_TITLE VARCHAR2(50) UNIQUE NOT NULL,
    MIN_SALARY NUMBER(8,0),
    MAX_SALARY NUMBER(8,0)
);

-- Add CHECK constraint separately for JOBS table
ALTER TABLE JOBS ADD CONSTRAINT chk_salary_range CHECK (MAX_SALARY >= MIN_SALARY);

-- f. Table: Employees
CREATE TABLE Employees (
    EMPLOYEEID NUMBER(6,0) GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    EMPLOYEENAME VARCHAR2(50) NOT NULL,
    GENDER VARCHAR2(6),
    BIRTHDATE DATE,
    JOB_ID NUMBER(3,0),
    ADDRESS VARCHAR2(150),
    PHONE VARCHAR2(15) UNIQUE,
    SALARY NUMBER(8,0),
    REMARKS VARCHAR2(50),
    PHOTO VARCHAR2(255),
    FOREIGN KEY (JOB_ID) REFERENCES JOBS(JOB_ID)
);

-- g. Table: INVOICES
CREATE TABLE INVOICES (
    INVOICENO NUMBER(12,0) GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    INVOICE_DATE DATE DEFAULT SYSDATE,
    CLIENT_NO NUMBER(8,0),
    EMPLOYEEID NUMBER(6,0),
    INVOICE_STATUS VARCHAR2(30),
    INVOICEMEMO VARCHAR2(100),
    FOREIGN KEY (CLIENT_NO) REFERENCES Clients(CLIENT_NO),
    FOREIGN KEY (EMPLOYEEID) REFERENCES Employees(EMPLOYEEID)
);

-- h. Table: INVOICE_DETAILS
CREATE TABLE INVOICE_DETAILS (
    INVOICENO NUMBER(12,0),
    PRODUCT_NO VARCHAR2(20),
    QTY NUMBER(8,0),
    PRICE NUMBER(12,2),
    PRIMARY KEY (INVOICENO, PRODUCT_NO),
    FOREIGN KEY (INVOICENO) REFERENCES INVOICES(INVOICENO),
    FOREIGN KEY (PRODUCT_NO) REFERENCES Products(PRODUCT_NO)
);

-- CREATE TRIGGER TO AUTOMATICALLY UPDATE PRODUCT STOCK (QTY_ON_HAND)
-- This trigger handles INSERT, UPDATE, DELETE operations on INVOICE_DETAILS
-- Stock is only reduced when invoice status is not 'Cancelled'
-- Stock is restored when invoice status changes to 'Cancelled'

CREATE OR REPLACE TRIGGER trg_update_product_stock
    AFTER INSERT OR UPDATE OR DELETE ON INVOICE_DETAILS
    FOR EACH ROW
DECLARE
    v_old_status VARCHAR2(30);
    v_new_status VARCHAR2(30);
BEGIN
    -- Get invoice status for the operations
    IF INSERTING OR DELETING THEN
        -- For INSERT, get the status of the new invoice
        -- For DELETE, get the status of the old invoice
        SELECT INVOICE_STATUS INTO v_new_status 
        FROM INVOICES 
        WHERE INVOICENO = COALESCE(:NEW.INVOICENO, :OLD.INVOICENO);
        v_old_status := v_new_status; -- Same for single operation
    END IF;
    
    IF UPDATING THEN
        -- For UPDATE, get both old and new invoice statuses (could be different invoices)
        SELECT INVOICE_STATUS INTO v_old_status 
        FROM INVOICES 
        WHERE INVOICENO = :OLD.INVOICENO;
        
        SELECT INVOICE_STATUS INTO v_new_status 
        FROM INVOICES 
        WHERE INVOICENO = :NEW.INVOICENO;
    END IF;
    
    -- Handle INSERT operation (new invoice detail added)
    IF INSERTING THEN
        -- Only reduce stock if invoice is not cancelled
        IF v_new_status != 'Cancelled' THEN
            UPDATE Products
            SET QTY_ON_HAND = NVL(QTY_ON_HAND, 0) - :NEW.QTY
            WHERE PRODUCT_NO = :NEW.PRODUCT_NO;
            
            DBMS_OUTPUT.PUT_LINE('Stock decreased for product ' || :NEW.PRODUCT_NO || 
                               ' by ' || :NEW.QTY || ' units (Invoice Status: ' || v_new_status || ')');
        ELSE
            DBMS_OUTPUT.PUT_LINE('Stock not affected for cancelled invoice - Product: ' || :NEW.PRODUCT_NO);
        END IF;
    END IF;
    
    -- Handle UPDATE operation (invoice detail modified)
    IF UPDATING THEN
        -- First, reverse the old transaction effect if it wasn't cancelled
        IF v_old_status != 'Cancelled' THEN
            UPDATE Products
            SET QTY_ON_HAND = NVL(QTY_ON_HAND, 0) + :OLD.QTY
            WHERE PRODUCT_NO = :OLD.PRODUCT_NO;
        END IF;
        
        -- Then, apply the new transaction effect if it's not cancelled
        IF v_new_status != 'Cancelled' THEN
            UPDATE Products
            SET QTY_ON_HAND = NVL(QTY_ON_HAND, 0) - :NEW.QTY
            WHERE PRODUCT_NO = :NEW.PRODUCT_NO;
        END IF;
        
        DBMS_OUTPUT.PUT_LINE('Stock updated: Product ' || :OLD.PRODUCT_NO || 
                           ' (Old Status: ' || v_old_status || '), Product ' || :NEW.PRODUCT_NO || 
                           ' (New Status: ' || v_new_status || ')');
    END IF;
    
    -- Handle DELETE operation (invoice detail removed)
    IF DELETING THEN
        -- Only restore stock if the deleted invoice detail was not from a cancelled invoice
        IF v_old_status != 'Cancelled' THEN
            UPDATE Products
            SET QTY_ON_HAND = NVL(QTY_ON_HAND, 0) + :OLD.QTY
            WHERE PRODUCT_NO = :OLD.PRODUCT_NO;
            
            DBMS_OUTPUT.PUT_LINE('Stock restored for product ' || :OLD.PRODUCT_NO || 
                               ' by ' || :OLD.QTY || ' units (Invoice was: ' || v_old_status || ')');
        ELSE
            DBMS_OUTPUT.PUT_LINE('Stock not restored for cancelled invoice - Product: ' || :OLD.PRODUCT_NO);
        END IF;
    END IF;
    
EXCEPTION
    WHEN OTHERS THEN
        -- Log error but don't stop the transaction
        DBMS_OUTPUT.PUT_LINE('Warning: Stock update failed for product operation: ' || SQLERRM);
        -- Note: We're not re-raising the exception to allow negative stock and continue transactions
END trg_update_product_stock;
/

-- CREATE TRIGGER TO HANDLE INVOICE STATUS CHANGES
-- This trigger monitors changes to INVOICE_STATUS and adjusts stock accordingly
-- When status changes from 'Cancelled' to active: reduce stock
-- When status changes from active to 'Cancelled': restore stock

CREATE OR REPLACE TRIGGER trg_invoice_status_stock
    AFTER UPDATE OF INVOICE_STATUS ON INVOICES
    FOR EACH ROW
    WHEN (OLD.INVOICE_STATUS != NEW.INVOICE_STATUS)
BEGIN
    -- Handle status change from 'Cancelled' to active status
    IF :OLD.INVOICE_STATUS = 'Cancelled' AND :NEW.INVOICE_STATUS != 'Cancelled' THEN
        -- Reduce stock for all products in this invoice
        FOR rec IN (SELECT PRODUCT_NO, QTY FROM INVOICE_DETAILS WHERE INVOICENO = :NEW.INVOICENO) LOOP
            UPDATE Products
            SET QTY_ON_HAND = NVL(QTY_ON_HAND, 0) - rec.QTY
            WHERE PRODUCT_NO = rec.PRODUCT_NO;
            
            DBMS_OUTPUT.PUT_LINE('Stock reduced for product ' || rec.PRODUCT_NO || 
                               ' by ' || rec.QTY || ' units (Invoice ' || :NEW.INVOICENO || 
                               ' status changed from Cancelled to ' || :NEW.INVOICE_STATUS || ')');
        END LOOP;
    END IF;
    
    -- Handle status change from active status to 'Cancelled'
    IF :OLD.INVOICE_STATUS != 'Cancelled' AND :NEW.INVOICE_STATUS = 'Cancelled' THEN
        -- Restore stock for all products in this invoice
        FOR rec IN (SELECT PRODUCT_NO, QTY FROM INVOICE_DETAILS WHERE INVOICENO = :NEW.INVOICENO) LOOP
            UPDATE Products
            SET QTY_ON_HAND = NVL(QTY_ON_HAND, 0) + rec.QTY
            WHERE PRODUCT_NO = rec.PRODUCT_NO;
            
            DBMS_OUTPUT.PUT_LINE('Stock restored for product ' || rec.PRODUCT_NO || 
                               ' by ' || rec.QTY || ' units (Invoice ' || :NEW.INVOICENO || 
                               ' status changed from ' || :OLD.INVOICE_STATUS || ' to Cancelled)');
        END LOOP;
    END IF;
    
EXCEPTION
    WHEN OTHERS THEN
        -- Log error but don't stop the transaction
        DBMS_OUTPUT.PUT_LINE('Warning: Stock update failed for invoice status change: ' || SQLERRM);
        -- Note: We're not re-raising the exception to allow the status change to proceed
END trg_invoice_status_stock;
/

-- Create a comprehensive view to monitor stock levels and sales
-- This view shows current stock status and sales data excluding cancelled invoices
CREATE OR REPLACE VIEW v_product_stock_status AS
SELECT 
    p.PRODUCT_NO,
    p.PRODUCTNAME,
    p.QTY_ON_HAND,
    p.REORDER_LEVEL,
    CASE 
        WHEN p.QTY_ON_HAND < 0 THEN 'NEGATIVE STOCK'
        WHEN p.QTY_ON_HAND = 0 THEN 'OUT OF STOCK'
        WHEN p.QTY_ON_HAND <= p.REORDER_LEVEL THEN 'LOW STOCK'
        WHEN p.QTY_ON_HAND <= (p.REORDER_LEVEL * 1.5) THEN 'MODERATE'
        ELSE 'ADEQUATE'
    END AS STOCK_STATUS,
    pt.PRODUCTTYPE_NAME,
    p.SELL_PRICE,
    p.COST_PRICE,
    -- Calculate total sales for this product (excluding cancelled invoices)
    NVL((SELECT SUM(id.QTY) 
         FROM INVOICE_DETAILS id 
         JOIN INVOICES i ON id.INVOICENO = i.INVOICENO
         WHERE id.PRODUCT_NO = p.PRODUCT_NO 
         AND i.INVOICE_STATUS != 'Cancelled'), 0) AS TOTAL_SOLD,
    -- Calculate total quantity from all invoice details (including cancelled for reference)
    NVL((SELECT SUM(id.QTY) 
         FROM INVOICE_DETAILS id 
         WHERE id.PRODUCT_NO = p.PRODUCT_NO), 0) AS TOTAL_INVOICED,
    -- Calculate cancelled quantity
    NVL((SELECT SUM(id.QTY) 
         FROM INVOICE_DETAILS id 
         JOIN INVOICES i ON id.INVOICENO = i.INVOICENO
         WHERE id.PRODUCT_NO = p.PRODUCT_NO 
         AND i.INVOICE_STATUS = 'Cancelled'), 0) AS CANCELLED_QTY
FROM Products p
LEFT JOIN Product_Type pt ON p.PRODUCTTYPE = pt.PRODUCTTYPE_ID
ORDER BY 
    CASE 
        WHEN p.QTY_ON_HAND < 0 THEN 1  -- Negative stock first
        WHEN p.QTY_ON_HAND = 0 THEN 2  -- Out of stock second
        WHEN p.QTY_ON_HAND <= p.REORDER_LEVEL THEN 3  -- Low stock third
        ELSE 4
    END,
    p.QTY_ON_HAND ASC;

COMMIT;

/*
===============================================================================
ENHANCED STOCK MANAGEMENT SYSTEM - SUMMARY
===============================================================================

The system now includes intelligent stock management with the following features:

1. INVOICE_DETAILS TRIGGER (trg_update_product_stock):
   - Only reduces stock when invoice status is NOT 'Cancelled'
   - Handles INSERT, UPDATE, DELETE operations on invoice details
   - Considers invoice status when making stock adjustments
   - Provides detailed logging of all stock movements

2. INVOICE STATUS TRIGGER (trg_invoice_status_stock):
   - Monitors changes to INVOICE_STATUS field
   - When status changes FROM 'Cancelled' TO active: reduces stock
   - When status changes FROM active TO 'Cancelled': restores stock
   - Processes all invoice details automatically

3. STOCK STATUS VIEW (v_product_stock_status):
   - Shows current stock levels and status
   - Displays total sold (excluding cancelled invoices)
   - Shows total invoiced and cancelled quantities for analysis
   - Provides comprehensive stock monitoring

BUSINESS LOGIC:
- Cancelled invoices do not affect stock levels
- Stock is automatically adjusted when invoice status changes
- Negative stock levels are allowed for business flexibility
- All stock movements are logged for audit purposes

USAGE EXAMPLES:
- New invoice created: Stock reduced immediately (if not cancelled)
- Invoice cancelled: Stock restored automatically
- Invoice reactivated: Stock reduced again
- Invoice details modified: Stock adjusted based on both old and new status

===============================================================================
*/

-- Insert sample data for all tables
-- This script will populate all tables with meaningful test data

-- Sample Data Insertion Script for Oracle Database
-- Note: This script handles auto-generated identity columns properly

-- Clear existing data first (in dependency order)
-- DELETE FROM INVOICE_DETAILS;
-- DELETE FROM INVOICES;
-- DELETE FROM Employees;
-- DELETE FROM JOBS;
-- DELETE FROM Products;
-- DELETE FROM Product_Type;
-- DELETE FROM Clients;
-- DELETE FROM Client_Type;

-- Commit the deletions
COMMIT;

-- 1. Insert Client Types (5 records) - Manual PK values
INSERT INTO Client_Type (TYPE_NAME, DISCOUNT_RATE, REMARKS) VALUES ('Premium Corporate', 15.00, 'High-volume corporate clients');
INSERT INTO Client_Type (TYPE_NAME, DISCOUNT_RATE, REMARKS) VALUES ('Standard Corporate', 10.00, 'Regular corporate clients');
INSERT INTO Client_Type (TYPE_NAME, DISCOUNT_RATE, REMARKS) VALUES ('Small Business', 8.00, 'Small business clients');
INSERT INTO Client_Type (TYPE_NAME, DISCOUNT_RATE, REMARKS) VALUES ('Government', 12.00, 'Government agencies');
INSERT INTO Client_Type (TYPE_NAME, DISCOUNT_RATE, REMARKS) VALUES ('Non-Profit', 20.00, 'Non-profit organizations');

-- Commit Client Types
COMMIT;

-- 2. Insert Product Types (5 records) - Manual PK values
INSERT INTO Product_Type (PRODUCTTYPE_NAME, REMARKS) VALUES ('Electronics', 'Electronic devices and components');
INSERT INTO Product_Type (PRODUCTTYPE_NAME, REMARKS) VALUES ('Office Supplies', 'General office supplies');
INSERT INTO Product_Type (PRODUCTTYPE_NAME, REMARKS) VALUES ('Furniture', 'Office and home furniture');
INSERT INTO Product_Type (PRODUCTTYPE_NAME, REMARKS) VALUES ('Software', 'Software licenses and tools');
INSERT INTO Product_Type (PRODUCTTYPE_NAME, REMARKS) VALUES ('Hardware', 'Computer hardware components');

-- Commit Product Types
COMMIT;

-- 3. Insert Jobs (5 records) - Manual PK values
INSERT INTO JOBS (JOB_TITLE, MIN_SALARY, MAX_SALARY) VALUES ('Software Developer', 50000, 90000);
INSERT INTO JOBS (JOB_TITLE, MIN_SALARY, MAX_SALARY) VALUES ('Project Manager', 70000, 120000);
INSERT INTO JOBS (JOB_TITLE, MIN_SALARY, MAX_SALARY) VALUES ('Database Administrator', 60000, 100000);
INSERT INTO JOBS (JOB_TITLE, MIN_SALARY, MAX_SALARY) VALUES ('System Analyst', 55000, 85000);
INSERT INTO JOBS (JOB_TITLE, MIN_SALARY, MAX_SALARY) VALUES ('HR Manager', 50000, 80000);

-- Commit Jobs
COMMIT;

-- 4. Insert Clients (5 records) - Manual PK values with explicit CLIENT_NO
INSERT INTO Clients (CLIENTNAME, ADDRESS, CITY, PHONE, CLIENT_TYPE, DISCOUNT) VALUES ('TechCorp Solutions', '123 Business Ave', 'New York', '555-0101', 1, 15.00);
INSERT INTO Clients (CLIENTNAME, ADDRESS, CITY, PHONE, CLIENT_TYPE, DISCOUNT) VALUES ('Global Industries', '456 Corporate Blvd', 'Los Angeles', '555-0102', 2, 10.00);
INSERT INTO Clients (CLIENTNAME, ADDRESS, CITY, PHONE, CLIENT_TYPE, DISCOUNT) VALUES ('City Hospital', '789 Health St', 'Chicago', '555-0103', 3, 14.00);
INSERT INTO Clients (CLIENTNAME, ADDRESS, CITY, PHONE, CLIENT_TYPE, DISCOUNT) VALUES ('Metro University', '321 Education Way', 'Boston', '555-0104', 4, 18.00);
INSERT INTO Clients (CLIENTNAME, ADDRESS, CITY, PHONE, CLIENT_TYPE, DISCOUNT) VALUES ('SmallBiz Inc', '654 Startup Lane', 'Austin', '555-0105', 5, 8.00);

-- Commit Clients
COMMIT;

-- 5. Insert Products (5 records) - References to Product_Type auto-generated IDs
INSERT INTO Products (PRODUCT_NO, PRODUCTNAME, PRODUCTTYPE, PROFIT_PERCENT, UNIT_MEASURE, REORDER_LEVEL, SELL_PRICE, COST_PRICE, QTY_ON_HAND) VALUES ('LAPTOP001', 'Business Laptop Pro', 1, 25.00, 'pieces', 10, 1200.00, 960.00, 50);
INSERT INTO Products (PRODUCT_NO, PRODUCTNAME, PRODUCTTYPE, PROFIT_PERCENT, UNIT_MEASURE, REORDER_LEVEL, SELL_PRICE, COST_PRICE, QTY_ON_HAND) VALUES ('DESK001', 'Executive Office Desk', 3, 40.00, 'pieces', 5, 800.00, 571.43, 15);
INSERT INTO Products (PRODUCT_NO, PRODUCTNAME, PRODUCTTYPE, PROFIT_PERCENT, UNIT_MEASURE, REORDER_LEVEL, SELL_PRICE, COST_PRICE, QTY_ON_HAND) VALUES ('PAPER001', 'Premium Copy Paper', 2, 20.00, 'reams', 100, 12.00, 10.00, 500);
INSERT INTO Products (PRODUCT_NO, PRODUCTNAME, PRODUCTTYPE, PROFIT_PERCENT, UNIT_MEASURE, REORDER_LEVEL, SELL_PRICE, COST_PRICE, QTY_ON_HAND) VALUES ('SW001', 'Office Suite License', 4, 50.00, 'licenses', 20, 300.00, 200.00, 100);
INSERT INTO Products (PRODUCT_NO, PRODUCTNAME, PRODUCTTYPE, PROFIT_PERCENT, UNIT_MEASURE, REORDER_LEVEL, SELL_PRICE, COST_PRICE, QTY_ON_HAND) VALUES ('ROUTER001', 'Enterprise Router', 3, 30.00, 'pieces', 8, 450.00, 346.15, 25);
INSERT INTO Products (PRODUCT_NO, PRODUCTNAME, PRODUCTTYPE, PROFIT_PERCENT, UNIT_MEASURE, REORDER_LEVEL, SELL_PRICE, COST_PRICE, QTY_ON_HAND) VALUES ('CHAIR001', 'Ergonomic Office Chair', 3, 35.00, 'pieces', 12, 350.00, 259.26, 30);
INSERT INTO Products (PRODUCT_NO, PRODUCTNAME, PRODUCTTYPE, PROFIT_PERCENT, UNIT_MEASURE, REORDER_LEVEL, SELL_PRICE, COST_PRICE, QTY_ON_HAND) VALUES ('PRINTER001', 'Laser Printer 3000', 5, 22.00, 'pieces', 6, 550.00, 450.82, 18);
INSERT INTO Products (PRODUCT_NO, PRODUCTNAME, PRODUCTTYPE, PROFIT_PERCENT, UNIT_MEASURE, REORDER_LEVEL, SELL_PRICE, COST_PRICE, QTY_ON_HAND) VALUES ('HDD001', 'External Hard Drive 2TB', 5, 28.00, 'pieces', 15, 120.00, 93.75, 75);
INSERT INTO Products (PRODUCT_NO, PRODUCTNAME, PRODUCTTYPE, PROFIT_PERCENT, UNIT_MEASURE, REORDER_LEVEL, SELL_PRICE, COST_PRICE, QTY_ON_HAND) VALUES ('MOUSE001', 'Wireless Mouse Pro', 4, 45.00, 'pieces', 25, 65.00, 44.83, 120);
INSERT INTO Products (PRODUCT_NO, PRODUCTNAME, PRODUCTTYPE, PROFIT_PERCENT, UNIT_MEASURE, REORDER_LEVEL, SELL_PRICE, COST_PRICE, QTY_ON_HAND) VALUES ('CABLE001', 'Network Cable Cat6', 3, 60.00, 'meters', 200, 8.00, 5.00, 1000);

-- Commit Products
COMMIT;

-- 6. Insert Employees (5 records) - Manual PK values with explicit EMPLOYEEID
INSERT INTO Employees (EMPLOYEENAME, GENDER, BIRTHDATE, JOB_ID, ADDRESS, PHONE, SALARY, REMARKS) VALUES ('John Smith', 'Male', DATE '1985-03-15', 1, '123 Main St, New York', '555-1001', 75000, 'Senior developer');
INSERT INTO Employees (EMPLOYEENAME, GENDER, BIRTHDATE, JOB_ID, ADDRESS, PHONE, SALARY, REMARKS) VALUES ('Sarah Johnson', 'Female', DATE '1982-07-22', 2, '456 Oak Ave, Los Angeles', '555-1002', 95000, 'Experienced PM');
INSERT INTO Employees (EMPLOYEENAME, GENDER, BIRTHDATE, JOB_ID, ADDRESS, PHONE, SALARY, REMARKS) VALUES ('Michael Brown', 'Male', DATE '1980-11-08', 3, '789 Pine Rd, Chicago', '555-1003', 80000, 'Oracle specialist');
INSERT INTO Employees (EMPLOYEENAME, GENDER, BIRTHDATE, JOB_ID, ADDRESS, PHONE, SALARY, REMARKS) VALUES ('Emily Davis', 'Female', DATE '1988-05-12', 4, '321 Elm St, Houston', '555-1004', 70000, 'Business systems analyst');
INSERT INTO Employees (EMPLOYEENAME, GENDER, BIRTHDATE, JOB_ID, ADDRESS, PHONE, SALARY, REMARKS) VALUES ('David Wilson', 'Male', DATE '1975-09-30', 5, '654 Maple Dr, Phoenix', '555-1005', 65000, 'HR team lead');

-- Commit Employees
COMMIT;

-- 7. Insert Invoices (20 sample records from 2023) - Manual PK values with explicit INVOICENO
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES (DATE '2025-01-15', 1, 1, 'Pending', 'Invoice #1 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES (DATE '2025-01-20', 2, 2, 'Cancelled', 'Invoice #2 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES (DATE '2025-02-05', 3, 3, 'Shipped', 'Invoice #3 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES (DATE '2025-02-10', 4, 4, 'Pending', 'Invoice #4 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES (DATE '2025-02-25', 5, 5, 'Cancelled', 'Invoice #5 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES (DATE '2025-03-10', 3, 3, 'Delivered', 'Invoice #6 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES (DATE '2025-03-15', 4, 2, 'Pending', 'Invoice #7 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES (DATE '2025-03-30', 2, 3, 'Cancelled', 'Invoice #8 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES (DATE '2025-04-15', 1, 4, 'Delivered', 'Invoice #9 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES ( DATE '2025-04-20', 3, 1, 'Pending', 'Invoice #10 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES ( DATE '2025-05-05', 5, 1, 'Cancelled', 'Invoice #11 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES ( DATE '2025-05-10', 4, 3, 'Shipped', 'Invoice #12 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES ( DATE '2025-05-25', 2, 4, 'Pending', 'Invoice #13 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES ( DATE '2025-06-10', 1, 2, 'Cancelled', 'Invoice #14 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES ( DATE '2025-06-15', 2, 3, 'Delivered', 'Invoice #15 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES ( DATE '2025-07-01', 3, 4, 'Pending', 'Invoice #16 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES ( DATE '2025-07-15', 2, 1, 'Cancelled', 'Invoice #17 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES ( DATE '2025-08-01', 5, 5, 'Shipped', 'Invoice #18 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES ( DATE '2025-08-15', 4, 5, 'Pending', 'Invoice #19 - Business transaction');
INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) VALUES ( DATE '2025-09-01', 1, 2, 'Cancelled', 'Invoice #20 - Business transaction');

-- Commit Invoices
COMMIT;

-- 8. Insert Invoice Details (Sample records for each invoice)
-- Invoice 1 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (1, 'LAPTOP001', 2, 1200.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (1, 'MOUSE001', 5, 65.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (1, 'CABLE001', 50, 8.00);

-- Invoice 2 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (2, 'DESK001', 1, 800.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (2, 'CHAIR001', 1, 350.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (2, 'PRINTER001', 2, 550.00);

-- Invoice 3 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (3, 'PAPER001', 100, 12.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (3, 'MOUSE001', 10, 65.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (3, 'CABLE001', 25, 8.00);

-- Invoice 4 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (4, 'SW001', 5, 300.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (4, 'HDD001', 3, 120.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (4, 'LAPTOP001', 2, 1200.00);

-- Invoice 5 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (5, 'ROUTER001', 1, 450.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (5, 'CABLE001', 100, 8.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (5, 'PAPER001', 5, 12.00);

-- Invoice 6 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (6, 'PRINTER001', 1, 550.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (6, 'PAPER001', 50, 12.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (6, 'CHAIR001', 10, 350.00);

-- Invoice 7 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (7, 'LAPTOP001', 3, 1200.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (7, 'MOUSE001', 3, 65.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (7, 'SW001', 5, 300.00);

-- Invoice 8 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (8, 'HDD001', 4, 120.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (8, 'ROUTER001', 20, 450.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (8, 'CABLE001', 15, 8.00);

-- Invoice 9 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (9, 'DESK001', 1, 800.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (9, 'CABLE001', 30, 8.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (9, 'PRINTER001', 3, 550.00);

-- Invoice 10 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (10, 'LAPTOP001', 1, 1200.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (10, 'HDD001', 1, 120.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (10, 'MOUSE001', 1, 65.00);

-- Invoice 11 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (11, 'DESK001', 2, 800.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (11, 'CHAIR001', 2, 350.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (11, 'PAPER001', 20, 12.00);

-- Invoice 12 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (12, 'SW001', 10, 300.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (12, 'HDD001', 5, 120.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (12, 'PAPER001', 75, 12.00);

-- Invoice 13 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (13, 'ROUTER001', 2, 450.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (13, 'CABLE001', 200, 8.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (13, 'SW001', 15, 300.00);

-- Invoice 14 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (14, 'PRINTER001', 2, 550.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (14, 'LAPTOP001', 1, 1200.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (14, 'MOUSE001', 8, 65.00);

-- Invoice 15 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (15, 'CHAIR001', 8, 350.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (15, 'DESK001', 50, 800.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (15, 'HDD001', 10, 120.00);

-- Invoice 16 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (16, 'ROUTER001', 2, 450.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (16, 'CABLE001', 30, 8.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (16, 'PAPER001', 15, 12.00);

-- Invoice 17 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (17, 'LAPTOP001', 3, 1200.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (17, 'PRINTER001', 3, 550.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (17, 'MOUSE001', 6, 65.00);

-- Invoice 18 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (18, 'DESK001', 1, 800.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (18, 'CHAIR001', 1, 350.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (18, 'SW001', 3, 300.00);

-- Invoice 19 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (19, 'SW001', 8, 300.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (19, 'HDD001', 10, 120.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (19, 'CABLE001', 150, 8.00);

-- Invoice 20 Details
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (20, 'ROUTER001', 1, 450.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (20, 'PRINTER001', 1, 550.00);
INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) VALUES (20, 'PAPER001', 100, 12.00);

-- Commit Invoice Details
COMMIT;

-- Display completion message
SELECT 'Sample data insertion completed successfully!' AS STATUS FROM DUAL;