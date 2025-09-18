-- Check EMPLOYEES table schema and constraints
DESCRIBE EMPLOYEES;

-- Check constraints on EMPLOYEES table
SELECT constraint_name, constraint_type, search_condition
FROM user_constraints 
WHERE table_name = 'EMPLOYEES';

-- Check sample data
SELECT * FROM EMPLOYEES WHERE ROWNUM <= 3;

-- Check relationship with JOBS table
SELECT e.EMPLOYEENAME, e.SALARY, j.JOB_TITLE, j.MIN_SALARY, j.MAX_SALARY
FROM EMPLOYEES e
JOIN JOBS j ON e.JOB_ID = j.JOB_ID
WHERE ROWNUM <= 3;

EXIT;