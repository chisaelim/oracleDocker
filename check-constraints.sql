-- Check the constraints on JOBS table
SELECT constraint_name, constraint_type, search_condition
FROM user_constraints 
WHERE table_name = 'JOBS';

-- Check which columns have unique constraints
SELECT cols.table_name, cols.column_name, cols.position, cons.status, cons.owner
FROM user_constraints cons, user_cons_columns cols
WHERE cols.table_name = 'JOBS'
AND cons.constraint_name = cols.constraint_name
AND cons.owner = cols.owner
ORDER BY cols.table_name, cols.position;

EXIT;