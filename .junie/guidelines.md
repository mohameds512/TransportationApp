# TransportationApp Development Guidelines

This document provides guidelines and instructions for developers working on the TransportationApp project.

## Build/Configuration Instructions

### Requirements
- PHP 8.2 or higher
- Composer
- Node.js and NPM (for frontend assets)

### Setup
1. Clone the repository
2. Install PHP dependencies:
   ```
   composer install
   ```
3. Install JavaScript dependencies:
   ```
   npm install
   ```
4. Create environment file:
   ```
   copy .env.example .env
   ```
5. Generate application key:
   ```
   php artisan key:generate
   ```
6. Configure your database in the `.env` file
7. Run migrations:
   ```
   php artisan migrate
   ```
8. Start the development server:
   ```
   php artisan serve
   ```
   
### Development Environment
The project includes a convenient development script that starts multiple services:
```
composer dev
```
This command runs:
- Laravel development server
- Queue worker
- Log viewer
- Vite for frontend assets

## Testing Information

### Test Configuration
- Tests use SQLite in-memory database
- The application uses PHPUnit for testing
- Tests are organized into Feature and Unit directories
- Configuration is in `phpunit.xml`

### Running Tests
Run all tests:
```
php artisan test
```

Run specific test:
```
php artisan test --filter=TestName
```

Run tests with coverage report:
```
php artisan test --coverage
```

### Adding New Tests
1. **Unit Tests**: Place in `tests/Unit` directory
   - Extend `PHPUnit\Framework\TestCase`
   - Focus on testing isolated components
   - Example:
     ```php
     <?php
     
     namespace Tests\Unit;
     
     use PHPUnit\Framework\TestCase;
     
     class SimpleTest extends TestCase
     {
         public function test_addition_works(): void
         {
             $this->assertEquals(4, 2 + 2);
         }
     }
     ```

2. **Feature Tests**: Place in `tests/Feature` directory
   - Extend `Tests\TestCase`
   - Focus on testing application behavior
   - Example:
     ```php
     <?php
     
     namespace Tests\Feature;
     
     use Tests\TestCase;
     
     class ExampleTest extends TestCase
     {
         public function test_the_application_returns_a_successful_response(): void
         {
             $response = $this->get('/');
             
             $response->assertStatus(200);
         }
     }
     ```

## Code Style and Development Guidelines

### Code Style
- The project uses Laravel Pint for code style enforcement
- Run Pint to fix code style issues:
  ```
  ./vendor/bin/pint
  ```

### EditorConfig
The project includes an `.editorconfig` file with the following settings:
- UTF-8 encoding
- LF line endings
- 4-space indentation (2 spaces for YAML files)
- No trailing whitespace
- Final newline in all files

### Development Best Practices
1. Follow Laravel's conventions for:
   - Controller methods
   - Route naming
   - Model relationships
   - Service providers

2. Use Laravel's built-in features:
   - Validation
   - Middleware
   - Eloquent ORM
   - Blade templating

3. Write tests for new features and bug fixes

4. Use Laravel's queue system for long-running tasks

5. Follow PSR-4 autoloading standards
