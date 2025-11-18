output "redis_cluster_id" {
  description = "ID of the Redis cluster"
  value       = aws_elasticache_cluster.main.id
}

output "redis_endpoint" {
  description = "Redis connection endpoint"
  value       = "${aws_elasticache_cluster.main.cache_nodes[0].address}:${aws_elasticache_cluster.main.cache_nodes[0].port}"
}

output "redis_address" {
  description = "Redis hostname"
  value       = aws_elasticache_cluster.main.cache_nodes[0].address
}

output "redis_port" {
  description = "Redis port"
  value       = aws_elasticache_cluster.main.cache_nodes[0].port
}
