FROM python:3.11-slim

WORKDIR /opt/sentra-core

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    sqlite3 \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements
COPY core/requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy application code
COPY core/ .

# Create necessary directories
RUN mkdir -p db uploads static logs backups

# Set permissions
RUN chmod +x main.py

# Expose port
EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD python -c "import urllib.request; urllib.request.urlopen('http://localhost:8000/api/core/health').read()" || exit 1

# Run the application
CMD ["python", "main.py"]
