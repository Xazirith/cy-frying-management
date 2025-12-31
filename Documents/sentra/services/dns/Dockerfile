FROM python:3.11-slim

WORKDIR /app

RUN pip install --no-cache-dir requests

COPY ../base_service.py .
COPY dns_service.py .

EXPOSE 8084

CMD ["python", "dns_service.py"]
