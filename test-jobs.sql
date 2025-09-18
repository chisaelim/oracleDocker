-- Test query to verify jobs data and structure
SELECT JOB_ID, JOB_TITLE, MIN_SALARY, MAX_SALARY 
FROM JOBS 
ORDER BY JOB_ID;

-- Test to check if we can add a new job manually
INSERT INTO JOBS (JOB_TITLE, MIN_SALARY, MAX_SALARY) 
VALUES ('Test Manager', 60000, 90000);

-- Verify the new job was added
SELECT JOB_ID, JOB_TITLE, MIN_SALARY, MAX_SALARY 
FROM JOBS 
WHERE JOB_TITLE = 'Test Manager';

-- Check if any employees are assigned to jobs (for delete testing)
SELECT e.EMPLOYEENAME, j.JOB_TITLE 
FROM EMPLOYEES e 
JOIN JOBS j ON e.JOB_ID = j.JOB_ID 
WHERE ROWNUM <= 5;

COMMIT;
EXIT;