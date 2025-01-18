# Laravel E-commerce API

A RESTful API built with Laravel for managing products and orders with authentication.

## Requirements

- PHP >= 8.2
- Composer
- MySQL or PostgreSQL
- Node.js & NPM (optional - for frontend)

## Installation

1. Clone the repository

```bash
git clone [https://github.com/Meklad/trend.test](https://github.com/Meklad/trend.test)
cd trend.test/
```

2. Install PHP dependencies
```bash
composer install
```
3. Create and configure environment file
```bash
cp .env.example .env
```

4. Configure your `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```
5. Generate application key
```bash
php artisan key:generate
```

6. Run database migrations
```bash
php artisan migrate
```

## Running the Application

1. Start the development server
```bash
npm install
composer run dev
```


The API will be available at `http://localhost:8000`

## API Endpoints

### Authentication

#### Register - `POST /api/register`

#### Login - `POST /api/login`

#### Logout - `POST /api/logout`
Requires Authentication Token

### Products

- `GET /api/products` - List all products
- `POST /api/products` - Create a product
- `GET /api/products/{id}` - Get single product
- `PUT /api/products/{id}` - Update product
- `DELETE /api/products/{id}` - Delete product

### Orders

- `GET /api/orders` - List user's orders
- `POST /api/orders` - Create an order
- `GET /api/orders/{id}` - Get single order
- `PUT /api/orders/{id}` - Update order
- `DELETE /api/orders/{id}` - Delete order

## Authentication

All API endpoints except `login` and `register` require authentication. Include the token in the Authorization header:


## Testing

Run the test suite:
```bash
php artisan test
```