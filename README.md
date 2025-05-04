<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Kantin App

## About Kantin App

Kantin App is a comprehensive online school canteen management system that enables students to order food from various food stalls within their school. "Kantin" is the Indonesian word for "canteen," which is a place where students go to eat food on the school premises.

## Key Features

- **Multi-role Authentication**: Separate accounts for students and food stall admins
- **Food Stall Management**: Each "stan" (food stall) can manage their menu items
- **Discount Management**: Create and manage time-based discounts for menu items
- **Online Food Ordering**: Students can browse menus and place orders
- **Order Tracking**: Real-time status updates for orders (confirmed, cooking, being delivered, arrived)
- **Transaction History**: View transaction history by month
- **Income Reports**: Food stall admins can view income reports
- **Receipt Generation**: PDF receipts for completed orders

## Technical Details

This project is built with:
- Laravel 12 framework
- JWT Authentication (using php-open-source-saver/jwt-auth)
- RESTful API design
- PDF generation using barryvdh/laravel-dompdf
- Role-based access control

## Project Structure

The project follows a standard Laravel structure with:
- Models for Stan (food stall), Siswa (student), Menu, Diskon (discount), Transaksi (transaction), etc.
- API controllers for handling all business logic
- Middleware for role-based access control
- Comprehensive API routes for all functionality
- Migration files for database structure

## API Documentation

The API documentation is available in the Postman collection at `/postman/kantin-app-api.json`. You can import this collection directly into Postman to test all available endpoints.

> **Note**: A ready-to-use Postman collection file is included in the repo at `/postman/kantin-app-api.json` with examples for all API endpoints.

## Getting Started

1. Clone the repository
2. Install dependencies with `composer install`
3. Configure your `.env` file
4. Run database migrations with `php artisan migrate`
5. Generate JWT secret key with `php artisan jwt:secret`
6. Start the development server with `php artisan serve`
7. Import the Postman collection to test the API endpoints

## Created By

This project was created by [RadjaShiqnals](https://github.com/RadjaShiqnals)
