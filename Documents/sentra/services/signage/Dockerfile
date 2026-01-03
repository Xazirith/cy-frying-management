FROM python:3.11-slim

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    gcc \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install Python dependencies
RUN pip install --no-cache-dir requests

# Copy base service first
COPY base_service.py .

# Copy service files
COPY signage_service.py .
COPY player.html .
COPY manager.html .

# Create necessary directories
RUN mkdir -p /data /opt/sentra-storage/signage

# Expose port
EXPOSE 8095

# Set environment variables
ENV PYTHONUNBUFFERED=1
ENV SERVICE_HOST=0.0.0.0
ENV TENANTS_MODULES_DB=/data/tenants_modules.db

# Run the service
CMD ["python3", "signage_service.py"]
