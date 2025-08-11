# OpenDocMan Unit Testing Guide

This directory contains the unit and integration tests for OpenDocMan. The testing framework is built using PHPUnit with Mockery for mocking dependencies.

## Directory Structure

```
tests/
├── bootstrap.php           # Test bootstrap and configuration
├── TestCase.php            # Base test case with common utilities
├── Unit/                   # Unit tests for individual classes
│   ├── UserTest.php        # Example unit test for User class
│   ├── DepartmentTest.php  # Unit tests for Department model
│   └── DepartmentControllerFunctionsTest.php # Unit tests for Department controller functions
├── Integration/            # Integration tests for component interaction
│   ├── DatabaseDataTest.php # Example integration test
│   └── DepartmentControllerTest.php # Integration tests for Department controller
├── README.md              # This file
└── README_DEPARTMENT_TESTS.md # Detailed documentation for Department tests
```

## Getting Started

### 1. Install Dependencies

First, install the testing dependencies using Composer:

```bash
composer install
```

This will install:
- PHPUnit 9.5+ for test framework
- Mockery for mocking dependencies

### 2. Run Tests

Run all tests:
```bash
composer test
# or
./application/vendor/bin/phpunit
```

Run specific test suites:
```bash
# Run only unit tests
./application/vendor/bin/phpunit --testsuite Unit

# Run only integration tests
./application/vendor/bin/phpunit --testsuite Integration
```

Run specific test files:
```bash
./application/vendor/bin/phpunit tests/Unit/UserTest.php
./application/vendor/bin/phpunit tests/Unit/DepartmentTest.php
```

Run Department tests specifically:
```bash
# Run the convenient script
./run-department-tests.sh

# Or run manually
./application/vendor/bin/phpunit tests/Unit/DepartmentTest.php tests/Unit/DepartmentControllerFunctionsTest.php tests/Integration/DepartmentControllerTest.php
```

### 3. Generate Code Coverage

Generate HTML coverage report:
```bash
composer test-coverage
# or
./application/vendor/bin/phpunit --coverage-html coverage
```

The coverage report will be generated in the `coverage/` directory.

## Writing Tests

### Unit Tests

Unit tests should focus on testing individual classes in isolation. They should:

- Test a single class/method at a time
- Mock all external dependencies
- Be fast and reliable
- Follow the naming convention: `ClassNameTest.php`

Example unit test structure:
```php
<?php

class UserTest extends TestCase
{
    private $user;
    private $mockConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockConnection = $this->createMockPDO();
        $this->user = new User(1, $this->mockConnection);
    }

    public function testUserCanBeInstantiated(): void
    {
        $this->assertInstanceOf(User::class, $this->user);
    }

    public function testUserPropertiesCanBeSet(): void
    {
        $this->user->username = 'testuser';
        $this->assertEquals('testuser', $this->user->username);
    }
}
```

### Integration Tests

Integration tests verify that multiple components work together correctly. They should:

- Test interactions between classes
- May use real or mock database connections
- Test more complex workflows
- Follow the naming convention: `ComponentNameTest.php`

### Test Naming Conventions

- Test files: `ClassNameTest.php`
- Test methods: `testMethodDescription()`
- Use descriptive names that explain what is being tested

Examples:
- `testUserCanBeCreatedWithValidData()`
- `testFileUploadValidatesFileType()`
- `testPermissionCheckReturnsCorrectLevel()`

## Testing Utilities

The base `TestCase` class provides several utilities:

### Mock Database Objects

```php
// Create mock PDO connection
$mockConnection = $this->createMockPDO();

// Create mock PDOStatement with return data
$mockStatement = $this->createMockPDOStatement([
    'id' => 1,
    'username' => 'testuser'
]);
```

### Test Data Creation

```php
// Create test user data
$userData = $this->createTestData('user');

// Create test file data
$fileData = $this->createTestData('file');
```

### Custom Assertions

```php
// Assert array has specific keys
$this->assertArrayHasKeys(['id', 'username'], $userData);

// Assert object has specific properties
$this->assertObjectHasProperties(['username', 'email'], $user);
```

### Configuration Setup

```php
// Set up test configuration
$this->setUpGlobalConfig([
    'custom_setting' => 'test_value'
]);
```

## Best Practices

### 1. Test Independence
- Each test should be independent and not rely on other tests
- Use `setUp()` and `tearDown()` methods to prepare and clean up test state

### 2. Use Descriptive Names
- Test method names should clearly describe what is being tested
- Use comments to explain complex test scenarios

### 3. Test Edge Cases
- Test both happy path and error conditions
- Test boundary values and edge cases
- Test null/empty inputs

### 4. Mock External Dependencies
- Mock database connections, file systems, external APIs
- Use dependency injection to make mocking easier
- Don't test external libraries, focus on your code

### 5. Keep Tests Simple
- One assertion per test when possible
- If testing multiple things, use multiple test methods
- Use data providers for testing multiple inputs

### 6. Test Data Management
- Use factories or builders to create test data
- Keep test data minimal and focused
- Use meaningful test data that reflects real usage

## Common Patterns

### Testing Database Models

```php
public function testUserCanBeLoadedFromDatabase(): void
{
    $userData = $this->createTestData('user');
    $mockStatement = $this->createMockPDOStatement($userData);
    
    $this->mockConnection
        ->shouldReceive('prepare')
        ->andReturn($mockStatement);
    
    $user = new User(1, $this->mockConnection);
    // Add assertions here
}
```

### Testing Validation

```php
public function testEmailValidationRejectsInvalidEmail(): void
{
    $this->expectException(InvalidArgumentException::class);
    $this->user->setEmail('invalid-email');
}
```

### Testing Permissions

```php
public function testUserHasCorrectPermissionLevel(): void
{
    $this->user->permission_level = User::ADMIN_RIGHT;
    $this->assertTrue($this->user->hasAdminPermission());
}
```

## Configuration

### Test Database
Tests use a separate test database configuration defined in `bootstrap.php`. Make sure to:

- Use a separate test database
- Never run tests against production data
- Consider using in-memory SQLite for faster tests

### Environment Variables
Set environment variables for testing:

```bash
export APP_ENV=testing
export DATABASE_NAME=opendocman_test
```

## Continuous Integration

For CI/CD pipelines, add these commands:

```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Run tests
composer test

# Generate coverage for CI
./application/vendor/bin/phpunit --coverage-clover coverage.xml
```

## Troubleshooting

### Common Issues

1. **Class not found errors**: Make sure `bootstrap.php` includes all necessary files
2. **Database connection errors**: Verify test database configuration
3. **Mock expectations not met**: Check that your mocks are set up correctly

### Debugging Tests

```bash
# Run tests with verbose output
./application/vendor/bin/phpunit --verbose

# Run specific test with debug output
./application/vendor/bin/phpunit --debug tests/Unit/UserTest.php
```

### Modern PHPUnit Practices

**Avoid Deprecated Features:**
- Don't use `expectWarning()` or `expectError()` as they're deprecated in PHPUnit 10
- Instead of testing for specific error types, test the actual behavior/result
- Use error suppression (`@`) when testing buggy code that generates warnings

**Example - Testing Undefined Variable Bugs:**
```php
// ❌ Deprecated approach (PHPUnit 9 and earlier)
public function testSomethingWithError(): void
{
    $this->expectWarning(); // Deprecated in PHPUnit 10
    $result = methodWithBug();
}

// ✅ Modern approach
public function testSomethingWithError(): void
{
    // Suppress the error and test the actual result
    $result = @methodWithBug();
    $this->assertNull($result, 'Method should return null due to bug');
}
```

## Department Tests

A comprehensive test suite has been added for the Department model and controller. This includes:

- **37 total tests** with **199 assertions**
- Unit tests for the Department model class
- Functional tests for Department controller business logic  
- Integration tests for Department workflows

See `README_DEPARTMENT_TESTS.md` for detailed documentation, or run:
```bash
./run-department-tests.sh
```

## Contributing

When adding new features:

1. Write tests first (TDD approach recommended)
2. Ensure all tests pass before submitting
3. Maintain or improve code coverage
4. Update this README if adding new testing patterns
5. Follow the Department test examples for comprehensive coverage

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Mockery Documentation](http://docs.mockery.io/)
- [Test-Driven Development in PHP](https://phpunit.de/getting-started/phpunit-9.html)
- [PHPUnit 10 Migration Guide](https://phpunit.de/announcements/phpunit-10.html)
- [Modern PHP Testing Practices](https://phpunit.de/getting-started.html)