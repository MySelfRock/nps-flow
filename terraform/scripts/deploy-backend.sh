#!/bin/bash
set -e

# NPSFlow Backend Deployment Script
echo "================================================"
echo "NPSFlow Backend Deployment Script"
echo "================================================"

# Configuration
AWS_REGION="${AWS_REGION:-us-east-1}"
ENVIRONMENT="${ENVIRONMENT:-production}"
PROJECT_NAME="npsflow"

# Get Terraform outputs
echo "Getting infrastructure information..."
cd "$(dirname "$0")/.."

ECR_REPO=$(terraform output -raw ecr_backend_repository_url 2>/dev/null)
if [ -z "$ECR_REPO" ]; then
    echo "Error: Could not get ECR repository URL. Is infrastructure deployed?"
    exit 1
fi

CLUSTER_NAME=$(terraform output -raw ecs_cluster_name 2>/dev/null)
SERVICE_NAME=$(terraform output -raw ecs_service_name 2>/dev/null)

echo "ECR Repository: $ECR_REPO"
echo "ECS Cluster: $CLUSTER_NAME"
echo "ECS Service: $SERVICE_NAME"

# Get version tag
if [ -z "$1" ]; then
    VERSION="latest"
    echo "No version specified, using 'latest'"
else
    VERSION="$1"
    echo "Using version: $VERSION"
fi

# Login to ECR
echo ""
echo "Logging in to Amazon ECR..."
aws ecr get-login-password --region $AWS_REGION | \
    docker login --username AWS --password-stdin $(echo $ECR_REPO | cut -d'/' -f1)

# Build Docker image
echo ""
echo "Building Docker image..."
cd ../../backend
docker build -t ${PROJECT_NAME}-backend:${VERSION} .

# Tag for ECR
echo ""
echo "Tagging image for ECR..."
docker tag ${PROJECT_NAME}-backend:${VERSION} ${ECR_REPO}:${VERSION}
docker tag ${PROJECT_NAME}-backend:${VERSION} ${ECR_REPO}:latest

# Push to ECR
echo ""
echo "Pushing image to ECR..."
docker push ${ECR_REPO}:${VERSION}
docker push ${ECR_REPO}:latest

# Update ECS service
echo ""
echo "Updating ECS service..."
aws ecs update-service \
    --cluster $CLUSTER_NAME \
    --service $SERVICE_NAME \
    --force-new-deployment \
    --region $AWS_REGION

echo ""
echo "================================================"
echo "Deployment initiated successfully!"
echo "================================================"
echo ""
echo "Monitor deployment status:"
echo "aws ecs describe-services --cluster $CLUSTER_NAME --services $SERVICE_NAME --region $AWS_REGION"
echo ""
echo "View logs:"
echo "aws logs tail /ecs/${PROJECT_NAME}-${ENVIRONMENT} --follow --region $AWS_REGION"
echo ""
