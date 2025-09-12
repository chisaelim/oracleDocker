-- Create a user with full permissions for Oracle Database XE 21c
-- This script will be executed during container startup
-- Oracle XE 21c is a multitenant database, so we create users in the pluggable database

-- Connect to the pluggable database XEPDB1
ALTER SESSION SET CONTAINER = XEPDB1;

-- Create the user in the pluggable database
CREATE USER appuser IDENTIFIED BY appuser123
DEFAULT TABLESPACE USERS
TEMPORARY TABLESPACE TEMP;

-- Grant comprehensive permissions to the user
GRANT CONNECT TO appuser;
GRANT RESOURCE TO appuser;
GRANT DBA TO appuser;
GRANT UNLIMITED TABLESPACE TO appuser;

-- Additional privileges for SQL Developer and external tools compatibility
GRANT SELECT_CATALOG_ROLE TO appuser;
GRANT EXECUTE_CATALOG_ROLE TO appuser;
GRANT SELECT ANY DICTIONARY TO appuser;

-- Grant system privileges for complete database management
GRANT CREATE USER TO appuser;
GRANT DROP USER TO appuser;
GRANT ALTER USER TO appuser;
GRANT CREATE ROLE TO appuser;
GRANT DROP ANY ROLE TO appuser;
GRANT GRANT ANY ROLE TO appuser;

-- Create a sample table to verify setup
CREATE TABLE appuser.sample_table (
    id NUMBER PRIMARY KEY,
    name VARCHAR2(100),
    created_date DATE DEFAULT SYSDATE
);

-- Insert sample data
INSERT INTO appuser.sample_table (id, name) VALUES (1, 'Test Record - Oracle Container Ready');
COMMIT;

EXIT;