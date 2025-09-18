-- Update Products table to use filename instead of BLOB for images
-- This script changes the PHOTO column from BLOB to VARCHAR2

-- Connect to the pluggable database
ALTER SESSION SET CONTAINER = XEPDB1;

-- Drop the PHOTO column and recreate it as VARCHAR2
ALTER TABLE Products DROP COLUMN PHOTO;
ALTER TABLE Products ADD PHOTO VARCHAR2(255);

-- Update any existing products to have NULL photo initially
UPDATE Products SET PHOTO = NULL;

COMMIT;

-- Display confirmation
SELECT 'PHOTO column updated successfully' AS STATUS FROM DUAL;