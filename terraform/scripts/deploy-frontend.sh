#!/bin/bash
set -e

# NPSFlow Frontend Deployment Script
echo "================================================"
echo "NPSFlow Frontend Deployment Script"
echo "================================================"

# Configuration
AWS_REGION="${AWS_REGION:-us-east-1}"
ENVIRONMENT="${ENVIRONMENT:-production}"

# Get Terraform outputs
echo "Getting infrastructure information..."
cd "$(dirname "$0")/.."

S3_BUCKET=$(terraform output -raw frontend_s3_bucket 2>/dev/null)
DISTRIBUTION_ID=$(terraform output -raw cloudfront_distribution_id 2>/dev/null)
ALB_DNS=$(terraform output -raw alb_dns_name 2>/dev/null)

if [ -z "$S3_BUCKET" ]; then
    echo "Error: Could not get S3 bucket name. Is infrastructure deployed?"
    exit 1
fi

echo "S3 Bucket: $S3_BUCKET"
echo "CloudFront Distribution: $DISTRIBUTION_ID"
echo "Backend ALB: $ALB_DNS"

# Build frontend
echo ""
echo "Building frontend application..."
cd ../../frontend

# Set environment variables for build
export VITE_API_URL="${API_URL:-https://$ALB_DNS}"
echo "API URL: $VITE_API_URL"

# Install dependencies and build
npm ci
npm run build

# Sync to S3
echo ""
echo "Uploading to S3..."
aws s3 sync dist/ s3://$S3_BUCKET/ \
    --delete \
    --region $AWS_REGION \
    --cache-control "public,max-age=31536000,immutable" \
    --exclude "index.html" \
    --exclude "*.json"

# Upload index.html and manifest with no-cache
aws s3 cp dist/index.html s3://$S3_BUCKET/index.html \
    --region $AWS_REGION \
    --cache-control "no-cache,no-store,must-revalidate"

# Upload JSON files with short cache
aws s3 sync dist/ s3://$S3_BUCKET/ \
    --region $AWS_REGION \
    --cache-control "public,max-age=300" \
    --exclude "*" \
    --include "*.json"

# Invalidate CloudFront cache
echo ""
echo "Invalidating CloudFront cache..."
aws cloudfront create-invalidation \
    --distribution-id $DISTRIBUTION_ID \
    --paths "/*" \
    --region $AWS_REGION

echo ""
echo "================================================"
echo "Frontend deployed successfully!"
echo "================================================"
echo ""
echo "CloudFront URL: https://$(terraform output -raw cloudfront_domain_name)"
echo ""
echo "Note: CloudFront invalidation may take 5-10 minutes to complete."
echo ""
