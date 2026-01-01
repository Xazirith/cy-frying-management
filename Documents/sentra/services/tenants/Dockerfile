FROM python:3.11-slim

WORKDIR /app

RUN pip install --no-cache-dir requests

COPY base_service.py .
COPY tenants/tenants_service.py tenants_service.py

EXPOSE 8088

CMD ["python", "tenants_service.py"]
