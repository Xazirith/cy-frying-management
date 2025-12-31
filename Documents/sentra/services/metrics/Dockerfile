FROM python:3.11-slim

WORKDIR /app

RUN pip install --no-cache-dir requests psutil

COPY base_service.py .
COPY metrics/metrics_service.py metrics_service.py

EXPOSE 8087

CMD ["python", "metrics_service.py"]
