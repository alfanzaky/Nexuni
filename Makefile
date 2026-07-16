.PHONY: up down dev

# Menjalankan Docker dependensi (RabbitMQ, Redis) di background
up:
	@if [ ! -f .env ]; then \
		echo "Membuat file .env root dari .env.docker..."; \
		cp .env.docker .env; \
	fi
	@echo "Menjalankan Docker container..."
	docker compose --env-file .env up -d

# Menghentikan Docker dependensi
down:
	@echo "Menghentikan Docker container..."
	docker compose down

# Menjalankan seluruh aplikasi (Docker + Laravel + Vite) dengan 1 command
dev: up
	@echo "Menjalankan Laravel Backend & Frontend secara bersamaan..."
	@echo "Tekan CTRL+C untuk menghentikan semuanya."
	@trap 'make down; kill %1 %2 2>/dev/null' SIGINT; \
	cd core && php artisan serve & \
	cd core && npm run dev & \
	wait
