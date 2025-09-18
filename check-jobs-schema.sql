-- Check JOBS table schema and constraints
DESCRIBE JOBS;

-- Check constraints on JOBS table
SELECT constraint_name, constraint_type, search_condition
FROM user_constraints 
WHERE table_name = 'JOBS';

-- Check sample data
SELECT * FROM JOBS WHERE ROWNUM <= 5;

EXIT;