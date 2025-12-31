FROM python:3.11-slim

WORKDIR /app

# Install WireGuard tools
RUN apt-get update && \
    apt-get install -y wireguard-tools iproute2 && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN pip install --no-cache-dir requests

COPY ../base_service.py .
COPY vpn_service.py .

EXPOSE 8086

CMD ["python", "vpn_service.py"]
