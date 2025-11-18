#!/bin/bash
set -e

# NPSFlow Database Migration Script
echo "================================================"
echo "NPSFlow Database Migration Script"
echo "================================================"

# Configuration
AWS_REGION="${AWS_REGION:-us-east-1}"

# Get Terraform outputs
echo "Getting infrastructure information..."
cd "$(dirname "$0")/.."

CLUSTER_NAME=$(terraform output -raw ecs_cluster_name 2>/dev/null)

if [ -z "$CLUSTER_NAME" ]; then
    echo "Error: Could not get ECS cluster name. Is infrastructure deployed?"
    exit 1
fi

echo "ECS Cluster: $CLUSTER_NAME"

# Get running task
echo ""
echo "Finding running ECS task..."
TASK_ARN=$(aws ecs list-tasks \
    --cluster $CLUSTER_NAME \
    --desired-status RUNNING \
    --region $AWS_REGION \
    --query 'taskArns[0]' \
    --output text)

if [ -z "$TASK_ARN" ] || [ "$TASK_ARN" = "None" ]; then
    echo "Error: No running tasks found in cluster $CLUSTER_NAME"
    exit 1
fi

echo "Task ARN: $TASK_ARN"

# Execute migration command
echo ""
echo "Executing database migrations..."
aws ecs execute-command \
    --cluster $CLUSTER_NAME \
    --task $TASK_ARN \
    --container app \
    --interactive \
    --command "php artisan migrate --force" \
    --region $AWS_REGION

echo ""
echo "================================================"
echo "Migration command executed!"
echo "================================================"
