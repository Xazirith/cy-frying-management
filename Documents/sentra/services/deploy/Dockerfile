FROM python:3.11-slim

WORKDIR /app

# Install Docker CLI
RUN apt-get update && \
    apt-get install -y curl && \
    curl -fsSL https://get.docker.com -o get-docker.sh && \
    sh get-docker.sh && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* get-docker.sh

RUN pip install --no-cache-dir requests

COPY ../base_service.py .
COPY deploy_service.py .

EXPOSE 8083

CMD ["python", "deploy_service.py"]
