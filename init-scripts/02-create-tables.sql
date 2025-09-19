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
-- and allows negative stock levels for business flexibility

CREATE OR REPLACE TRIGGER trg_update_product_stock
    AFTER INSERT OR UPDATE OR DELETE ON INVOICE_DETAILS
    FOR EACH ROW
BEGIN
    -- Handle INSERT operation (new invoice detail added - reduce stock)
    IF INSERTING THEN
        UPDATE Products
        SET QTY_ON_HAND = NVL(QTY_ON_HAND, 0) - :NEW.QTY
        WHERE PRODUCT_NO = :NEW.PRODUCT_NO;
        
        DBMS_OUTPUT.PUT_LINE('Stock decreased for product ' || :NEW.PRODUCT_NO || 
                           ' by ' || :NEW.QTY || ' units');
    END IF;
    
    -- Handle UPDATE operation (invoice detail modified)
    IF UPDATING THEN
        -- Check if product number changed
        IF :OLD.PRODUCT_NO != :NEW.PRODUCT_NO THEN
            -- Restore stock for old product
            UPDATE Products
            SET QTY_ON_HAND = NVL(QTY_ON_HAND, 0) + :OLD.QTY
            WHERE PRODUCT_NO = :OLD.PRODUCT_NO;
            
            -- Reduce stock for new product
            UPDATE Products
            SET QTY_ON_HAND = NVL(QTY_ON_HAND, 0) - :NEW.QTY
            WHERE PRODUCT_NO = :NEW.PRODUCT_NO;
            
            DBMS_OUTPUT.PUT_LINE('Stock updated: Product ' || :OLD.PRODUCT_NO || 
                               ' increased by ' || :OLD.QTY || 
                               ', Product ' || :NEW.PRODUCT_NO || 
                               ' decreased by ' || :NEW.QTY);
        ELSE
            -- Same product, but quantity changed
            IF :OLD.QTY != :NEW.QTY THEN
                -- Adjust stock by the difference (OLD - NEW because we want to reverse old effect and apply new)
                UPDATE Products
                SET QTY_ON_HAND = NVL(QTY_ON_HAND, 0) + (:OLD.QTY - :NEW.QTY)
                WHERE PRODUCT_NO = :NEW.PRODUCT_NO;
                
                DBMS_OUTPUT.PUT_LINE('Stock adjusted for product ' || :NEW.PRODUCT_NO || 
                                   ' by ' || (:OLD.QTY - :NEW.QTY) || ' units');
            END IF;
        END IF;
    END IF;
    
    -- Handle DELETE operation (invoice detail removed - restore stock)
    IF DELETING THEN
        UPDATE Products
        SET QTY_ON_HAND = NVL(QTY_ON_HAND, 0) + :OLD.QTY
        WHERE PRODUCT_NO = :OLD.PRODUCT_NO;
        
        DBMS_OUTPUT.PUT_LINE('Stock restored for product ' || :OLD.PRODUCT_NO || 
                           ' by ' || :OLD.QTY || ' units');
    END IF;
    
EXCEPTION
    WHEN OTHERS THEN
        -- Log error but don't stop the transaction
        DBMS_OUTPUT.PUT_LINE('Warning: Stock update failed for product operation: ' || SQLERRM);
        -- Note: We're not re-raising the exception to allow negative stock and continue transactions
END trg_update_product_stock;
/

-- Create a view to monitor stock levels including negative stock warnings
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
    -- Calculate total sales for this product
    NVL((SELECT SUM(id.QTY) 
         FROM INVOICE_DETAILS id 
         JOIN INVOICES i ON id.INVOICENO = i.INVOICENO
         WHERE id.PRODUCT_NO = p.PRODUCT_NO 
         AND i.INVOICE_STATUS != 'CANCELLED'), 0) AS TOTAL_SOLD
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