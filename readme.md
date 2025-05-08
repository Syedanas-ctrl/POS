# UltimatePOS - Customized Fork

This repository is a fork/built on top of other POS system, customized to meet specific client requirements. The primary modifications include:

- **ZATCA Integration**: Zatca is government based invoice reporting system
- Additional client-specific ui customizations and enhancements
- Extended functionality to address unique business workflows

## Technology Stack

This project is built using the following frameworks and technologies:

- **Laravel**: PHP framework for the backend API and server-side logic
- **MySQL**: Database management system
- **Bootstrap**: Frontend CSS framework for responsive design

## Installation and Setup

### Prerequisites
- PHP 8.0 or higher
- Composer
- MySQL
- Node.js and NPM

### Installation Steps

1. Install PHP dependencies
   ```
   composer install
   ```

2. Create environment file
   ```
   cp .env.example .env
   ```

3. Configure database settings in the `.env` file
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ultimatepos
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

4. Generate application key
   ```
   php artisan key:generate
   ```

5. Run database migrations and seed data
   ```
   php artisan migrate --seed
   ```

6. Start the development server
   ```
   php artisan serve
   ```