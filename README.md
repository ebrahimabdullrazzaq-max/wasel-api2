<<<<<<< HEAD
# wasel-api2
Laravel API for Vegetable Delivery App
=======
# Wasel - Laravel Backend API

A robust Laravel backend powering the **Wasel Flutter mobile app** for vegetable delivery services.

## 🚀 Features

- RESTful API endpoints for products, categories, stores, and orders
- JWT-based authentication via Laravel Sanctum
- Image storage via Laravel Filesystem (public/storage)
- Real-time product notifications for Flutter app
- Full CRUD operations with validation and error handling
- CORS configured for cross-origin requests from Flutter apps
- Optimized for mobile consumption (JSON responses only)

## 📁 Structure



## 🌐 API Endpoints (Examples)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET`  | `/api/products/store/{id}` | Get all products for a store |
| `GET`  | `/api/products/store/{id}/categories` | Get categories for a store |
| `GET`  | `/api/stores/{id}` | Get store info (name, status, delivery time) |

> All routes are under `/api` prefix and require no session auth (stateless).

## ⚙️ Setup Instructions

1. Clone this repo.
2. Run `composer install`
3. Copy `.env.example` → `.env`
4. Generate key: `php artisan key:generate`
5. Configure database in `.env`
6. Run migrations: `php artisan migrate --seed`
7. Link storage: `php artisan storage:link`
8. Start server: `php artisan serve`

## 📱 Connected To

- Flutter Mobile App: [https://github.com/ebrahimabdullrazzaq-max/flutter-application-1](https://github.com/ebrahimabdullrazzaq-max/flutter-application-1)

## 🚀 Deployment

Deployed live on **Railway.app**:  
👉 https://wasel-api.up.railway.app

## 💡 Built With

- PHP 8.3
- Laravel 10.x
- MySQL / PostgreSQL
- Laravel Sanctum (API Auth)
- Laravel Filament (optional admin panel)
- Redis (for caching & queues)

## 📜 License

MIT © Ebrahim Abdullrazzaq
>>>>>>> e95c66b (Initial commit: Secure Laravel Vegetable Delivery API)
