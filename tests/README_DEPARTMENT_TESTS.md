# Department Tests Documentation

This document describes the comprehensive test suite added for the Department model and controller functionality in OpenDocMan.

## Overview

The Department test suite consists of three main test files that provide comprehensive coverage of the Department model and controller:

1. **Unit Tests** - `tests/Unit/DepartmentTest.php`
2. **Controller Functions Tests** - `tests/Unit/DepartmentControllerFunctionsTest.php`
3. **Integration Tests** - `tests/Integration/DepartmentControllerTest.php`

## Test Files Description

### 1. DepartmentTest.php (Unit Tests)

**Location**: `tests/Unit/DepartmentTest.php`

**Purpose**: Tests the Department model class in isolation, focusing on:
- Class instantiation and inheritance
- Constructor behavior
- Property configuration
- Static method functionality
- Database interaction mocking

**Key Test Cases**:
- `testDepartmentCanBeInstantiated()` - Verifies proper class instantiation
- `testDepartmentClassConstantIsDefined()` - Checks class constant definition
- `testConstructorSetsCorrectTableConfiguration()` - Validates constructor setup
- `testDepartmentInheritsTableConstants()` - Tests inheritance from parent class
- `testDepartmentInheritsPermissionConstants()` - Validates permission constants
- `testGetAllDepartmentsWithEmptyResult()` - Tests static method with no data
- `testGetAllDepartmentsWithSingleDepartment()` - Tests static method with one department
- `testGetAllDepartmentsWithMultipleDepartments()` - Tests static method with multiple departments
- `testGetAllDepartmentsUsesCorrectQuery()` - Validates SQL query structure
- `testGetAllDepartmentsReturnFormatConsistency()` - Tests return format consistency
- `testGetAllDepartmentsWithSpecialCharacters()` - Tests handling of special characters

**Coverage**: 17 tests, 98 assertions

### 2. DepartmentControllerFunctionsTest.php (Controller Functions Tests)

**Location**: `tests/Unit/DepartmentControllerFunctionsTest.php`

**Purpose**: Tests isolated business logic functions extracted from the department controller:
- Input validation
- Database operations
- Permission checking
- Complete workflow simulation

**Key Test Cases**:
- `testValidateDepartmentName()` - Tests department name validation logic
- `testCheckDepartmentDuplicate()` - Tests duplicate department checking
- `testInsertDepartment()` - Tests department insertion logic
- `testGetNewlyAddedDepartmentId()` - Tests ID retrieval after insertion
- `testGetDefaultRights()` - Tests default rights retrieval
- `testSetDefaultPermissions()` - Tests permission setting for new departments
- `testGetDepartmentInfo()` - Tests department information retrieval
- `testGetUsersInDepartment()` - Tests user listing for departments
- `testGetReassignOptions()` - Tests reassignment options for deletion
- `testCheckAdminPermission()` - Tests admin permission validation
- `testValidateSession()` - Tests session validation
- `testCompleteAddDepartmentWorkflow()` - Tests complete department addition workflow

**Coverage**: 12 tests, 65 assertions

### 3. DepartmentControllerTest.php (Integration Tests)

**Location**: `tests/Integration/DepartmentControllerTest.php`

**Purpose**: Tests the integration between controller logic and database operations:
- End-to-end workflow testing
- Complex database interaction scenarios
- Controller action simulation

**Key Test Cases**:
- `testAddDepartmentWithValidData()` - Tests successful department addition
- `testAddDepartmentWithEmptyName()` - Tests validation with empty input
- `testAddDepartmentWithDuplicateName()` - Tests duplicate prevention
- `testShowDepartmentInformation()` - Tests department information display
- `testShowDepartmentPickList()` - Tests department selection interface
- `testPrepareDepartmentDeletion()` - Tests deletion preparation
- `testDeleteDepartmentWithNoOtherDepartments()` - Tests deletion validation
- `testNonAdminUserAccessRestriction()` - Tests access control

**Coverage**: 8 tests, 36 assertions

## Running the Tests

### Run All Department Tests
```bash
# From the opendocman root directory
application/vendor/bin/phpunit tests/Unit/DepartmentTest.php tests/Unit/DepartmentControllerFunctionsTest.php tests/Integration/DepartmentControllerTest.php --verbose
```

### Run Individual Test Files
```bash
# Unit tests only
application/vendor/bin/phpunit tests/Unit/DepartmentTest.php --verbose

# Controller function tests only
application/vendor/bin/phpunit tests/Unit/DepartmentControllerFunctionsTest.php --verbose

# Integration tests only
application/vendor/bin/phpunit tests/Integration/DepartmentControllerTest.php --verbose
```

### Run All Unit Tests
```bash
application/vendor/bin/phpunit tests/Unit/ --verbose
```

## Test Coverage Summary

| Test File | Tests | Assertions | Focus Area |
|-----------|-------|------------|------------|
| DepartmentTest.php | 17 | 98 | Model class testing |
| DepartmentControllerFunctionsTest.php | 12 | 65 | Business logic testing |
| DepartmentControllerTest.php | 8 | 36 | Integration testing |
| **Total** | **37** | **199** | **Complete coverage** |

## Key Features Tested

### Department Model (`Department.class.php`)
- ✅ Class instantiation and inheritance
- ✅ Constructor parameter handling
- ✅ Table and field configuration
- ✅ Database connection management
- ✅ Static method `getAllDepartments()`
- ✅ SQL query generation
- ✅ Data format consistency
- ✅ Special character handling

### Department Controller (`department.php`)
- ✅ Department addition workflow
- ✅ Input validation
- ✅ Duplicate checking
- ✅ Permission assignment
- ✅ Department information display
- ✅ User listing per department
- ✅ Department deletion preparation
- ✅ Admin access control
- ✅ Session validation

## Testing Approach

### Mocking Strategy
The tests use **Mockery** for creating test doubles:
- **PDO and PDOStatement mocking** for database operations
- **Isolated testing** without real database connections
- **Controlled test data** for predictable results

### Test Organization
- **Unit tests** focus on individual methods and classes
- **Integration tests** focus on component interaction
- **Functional tests** focus on business logic workflows

### Validation Coverage
- **Input validation** (empty, null, special characters)
- **Business rules** (duplicates, permissions, admin access)
- **Database operations** (CRUD operations, query structure)
- **Error handling** (missing data, insufficient permissions)

## Test Data Patterns

### Department Test Data
```php
$departmentData = [
    'id' => 1,
    'name' => 'Test Department'
];
```

### User Test Data
```php
$userData = [
    'id' => 3,
    'first_name' => 'John',
    'last_name' => 'Doe'
];
```

### Rights Test Data
```php
$rightsData = [
    'id' => 1,
    'default_rights' => 1
];
```

## Dependencies

### Required Classes
- `Department` - Main model class being tested
- `databaseData` - Parent class with shared functionality
- `User` - For admin permission checking
- `PDO` - Database connection interface

### Test Dependencies
- **PHPUnit 9.6+** - Testing framework
- **Mockery** - Mocking library
- **TestCase** - Base test class with utilities

## Maintenance

### Adding New Tests
1. Follow the existing naming conventions
2. Use appropriate test categories (Unit/Integration)
3. Mock database operations properly
4. Include both positive and negative test cases
5. Document complex test scenarios

### Test Data Management
- Use the helper methods in `TestCase.php`
- Create realistic but controlled test data
- Avoid hard-coded values where possible
- Use factories for complex object creation

### Common Patterns
```php
// Standard PDO mock setup
$mockStatement = \Mockery::mock(\PDOStatement::class);
$mockStatement->shouldReceive('execute')->andReturn(true);
$mockStatement->shouldReceive('fetchAll')->andReturn($testData);

$mockPdo = \Mockery::mock(PDO::class);
$mockPdo->shouldReceive('prepare')->andReturn($mockStatement);
```

## Best Practices Demonstrated

1. **Comprehensive Coverage** - Tests cover happy path, edge cases, and error conditions
2. **Isolation** - Each test is independent and doesn't rely on others
3. **Mocking** - External dependencies are properly mocked
4. **Readability** - Test names clearly describe what is being tested
5. **Maintainability** - Helper methods reduce code duplication
6. **Documentation** - Tests serve as documentation for expected behavior

## Future Enhancements

Potential areas for additional test coverage:
- Performance testing for large department lists
- Concurrency testing for simultaneous operations
- Database transaction testing
- Plugin integration testing
- Security testing (SQL injection, XSS prevention)
- Internationalization testing