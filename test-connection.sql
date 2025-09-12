-- Quick test to verify Oracle database setup
-- Connect to XEPDB1 and query the sample table

SELECT 'Database connection successful!' as status FROM dual;

SELECT * FROM sample_table;

SELECT username, account_status, default_tablespace 
FROM user_users;

EXIT;