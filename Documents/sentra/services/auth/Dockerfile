FROM python:3.11-slim

WORKDIR /app

# Install dependencies
RUN pip install --no-cache-dir requests

# Copy base service
COPY ../base_service.py .

# Copy auth service
COPY auth_service.py .

EXPOSE 8082

CMD ["python", "auth_service.py"]
