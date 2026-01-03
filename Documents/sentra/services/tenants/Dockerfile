FROM python:3.11-slim

WORKDIR /app

# Install dependencies
RUN pip install --no-cache-dir requests

# Copy base service
COPY base_service.py /app/

# Copy tenants service and modules
COPY tenants_service.py /app/
COPY modules/ /app/modules/

# Create data directory
RUN mkdir -p /data && chmod 777 /data

# Disable signature enforcement in containers
ENV ENFORCE_SIGNING=false
ENV TENANTS_DB_PATH=/data/tenants.db
ENV TENANTS_MODULES_DB=/data/tenants_modules.db
ENV TENANTS_DATA_DIR=/data/tenants

EXPOSE 8088

CMD ["python", "tenants_service.py"]
