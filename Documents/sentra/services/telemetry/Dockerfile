FROM python:3.11-slim

WORKDIR /app

RUN pip install --no-cache-dir requests

COPY base_service.py .
COPY telemetry/telemetry_service.py telemetry_service.py

EXPOSE 8085

CMD ["python", "telemetry_service.py"]
