# Custom Oracle Database XE Dockerfile
# Based on Oracle's official image with additional configurations

FROM container-registry.oracle.com/database/express:21.3.0-xe

# Set environment variables
ENV ORACLE_PWD=Oracle123
ENV ORACLE_CHARACTERSET=AL32UTF8

# Copy initialization scripts
COPY init-scripts/ /opt/oracle/scripts/startup/

# Expose Oracle Database port
EXPOSE 1521 5500

# Use the default Oracle entrypoint
# The base image handles database initialization and user creation