# User Tests Documentation

This document describes the comprehensive test suite added for the User model and controller functionality in OpenDocMan.

## Overview

The User test suite consists of three main test files that provide comprehensive coverage of the User model and controller:

1. **Unit Tests** - `tests/Unit/UserModelTest.php`
2. **Controller Functions Tests** - `tests/Unit/UserControllerFunctionsTest.php`
3. **Integration Tests** - `tests/Integration/UserControllerTest.php`

## Test Files Description

### 1. UserModelTest.php (Unit Tests)

**Location**: `tests/Unit/UserModelTest.php`

**Purpose**: Tests the User model class in isolation, focusing on:
- Class instantiation and inheritance
- Constructor behavior and error handling
- Property configuration and validation
- Database interaction methods
- Permission and access control methods
- Static method functionality

**Key Test Cases**:
- `testUserCanBeInstantiated()` - Verifies proper class instantiation
- `testUserClassConstantIsDefined()` - Checks class constant definition
- `testConstructorSetsCorrectConfiguration()` - Validates constructor setup
- `testUserInheritsTableConstants()` - Tests inheritance from parent class
- `testUserInheritsPermissionConstants()` - Validates permission constants
- `testGetDeptName()` - Tests department name retrieval
- `testGetDeptId()` - Tests department ID retrieval
- `testGetPublishedData()` - Tests published document retrieval
- `testIsAdminReturnsTrueForAdminUser()` - Tests admin privilege checking
- `testIsAdminReturnsFalseForNonAdminUser()` - Tests non-admin user checking
- `testIsAdminReturnsTrueForRootUser()` - Tests root user admin status
- `testIsRoot()` - Tests root user identification
- `testCanAddForAdminUser()` - Tests add permission for admin
- `testCanAddForUserWithPermission()` - Tests add permission for regular user
- `testCanAddForUserWithoutPermission()` - Tests add permission denial
- `testCanCheckInForAdminUser()` - Tests check-in permission for admin
- `testCanCheckInForUserWithPermission()` - Tests check-in permission for regular user
- `testGetPassword()` - Tests password retrieval
- `testChangePassword()` - Tests password modification
- `testValidatePasswordWithCorrectPassword()` - Tests password validation (correct)
- `testValidatePasswordWithIncorrectPassword()` - Tests password validation (incorrect)
- `testChangeName()` - Tests username modification
- `testIsReviewerForAdminUser()` - Tests reviewer status for admin
- `testIsReviewerForNonAdminReviewer()` - Tests reviewer status for non-admin
- `testGetAllUsers()` - Tests static user list retrieval
- `testGetEmailAddress()` - Tests email address retrieval
- `testGetPhoneNumber()` - Tests phone number retrieval
- `testGetFullName()` - Tests full name array retrieval
- `testGetUserName()` - Tests username retrieval
- `testGetCheckedOutFilesForRootUser()` - Tests checked-out files for root
- `testConstructorErrorHandling()` - Tests constructor error scenarios

**Coverage**: 29 tests, 140+ assertions

### 2. UserControllerFunctionsTest.php (Controller Functions Tests)

**Location**: `tests/Unit/UserControllerFunctionsTest.php`

**Purpose**: Tests isolated business logic functions extracted from the user controller:
- Input validation (username, email, phone)
- User lifecycle management (create, update, delete)
- Permission and access control logic
- Email notification preparation
- Demo mode restrictions
- Password generation and validation

**Key Test Cases**:
- `testValidateUserInput()` - Tests comprehensive input validation
- `testCheckUserDuplicate()` - Tests duplicate user checking
- `testInsertUser()` - Tests user creation logic
- `testInsertAdmin()` - Tests admin record creation
- `testInsertDepartmentReviewer()` - Tests reviewer assignment
- `testDeleteUser()` - Tests user deletion workflow
- `testUpdateUser()` - Tests user modification logic
- `testPasswordValidation()` - Tests password strength validation
- `testPermissionChecking()` - Tests access control logic
- `testGetDepartmentList()` - Tests department list retrieval
- `testGetUserList()` - Tests user list retrieval
- `testGetUserDetails()` - Tests user detail retrieval
- `testGetDepartmentReviewers()` - Tests reviewer relationship retrieval
- `testEmailNotificationLogic()` - Tests email notification preparation
- `testRandomPasswordGeneration()` - Tests password generation
- `testDemoModeRestrictions()` - Tests demo mode behavior

**Coverage**: 16 tests, 95+ assertions

### 3. UserControllerTest.php (Integration Tests)

**Location**: `tests/Integration/UserControllerTest.php`

**Purpose**: Tests the integration between controller logic and database operations:
- End-to-end user management workflows
- Complex database interaction scenarios
- Controller action simulation
- Access control integration

**Key Test Cases**:
- `testAddUserWithValidData()` - Tests successful user creation
- `testAddUserWithDuplicateUsername()` - Tests duplicate prevention
- `testDeleteUser()` - Tests user deletion workflow
- `testShowUserInformation()` - Tests user information display
- `testShowUserPickList()` - Tests user selection interface
- `testPrepareUserModification()` - Tests modification preparation
- `testUpdateUser()` - Tests user update workflow
- `testGetDepartmentList()` - Tests department list integration
- `testNonAdminUserAccessRestriction()` - Tests access control
- `testAdminUserAccessPermission()` - Tests admin privileges
- `testUserAccessingOwnProfile()` - Tests self-access permissions

**Coverage**: 11 tests, 50+ assertions

## Running the Tests

### Run All User Tests
```bash
# From the opendocman root directory
application/vendor/bin/phpunit tests/Unit/UserModelTest.php tests/Unit/UserControllerFunctionsTest.php tests/Integration/UserControllerTest.php --verbose
```

### Run Individual Test Files
```bash
# Model unit tests only
application/vendor/bin/phpunit tests/Unit/UserModelTest.php --verbose

# Controller function tests only
application/vendor/bin/phpunit tests/Unit/UserControllerFunctionsTest.php --verbose

# Integration tests only
application/vendor/bin/phpunit tests/Integration/UserControllerTest.php --verbose
```

### Run All Unit Tests
```bash
application/vendor/bin/phpunit tests/Unit/ --verbose
```

## Test Coverage Summary

| Test File | Tests | Assertions | Focus Area |
|-----------|-------|------------|------------|
| UserModelTest.php | 29 | 140+ | Model class testing |
| UserControllerFunctionsTest.php | 16 | 95+ | Business logic testing |
| UserControllerTest.php | 11 | 50+ | Integration testing |
| **Total** | **56** | **285+** | **Complete coverage** |

## Key Features Tested

### User Model (`User.class.php`)
- ✅ Class instantiation and inheritance
- ✅ Constructor parameter handling and error scenarios
- ✅ Table and field configuration
- ✅ Database connection management
- ✅ Department relationship methods
- ✅ Permission checking (admin, reviewer, add, check-in)
- ✅ Password management (get, change, validate)
- ✅ User identification (root, admin status)
- ✅ Published data retrieval
- ✅ Static method `getAllUsers()`
- ✅ Personal information getters
- ✅ File management (checked-out files)
- ✅ Error handling and edge cases

### User Controller (`user.php`)
- ✅ User creation workflow with validation
- ✅ Input validation (username, email, phone)
- ✅ Duplicate checking and prevention
- ✅ Admin and reviewer assignment
- ✅ User modification and updates
- ✅ User deletion with data reassignment
- ✅ User information display
- ✅ Permission and access control
- ✅ Department integration
- ✅ Email notification system
- ✅ Demo mode restrictions
- ✅ Session and security validation

## Testing Approach

### Mocking Strategy
The tests use **Mockery** for creating test doubles:
- **PDO and PDOStatement mocking** for database operations
- **Isolated testing** without real database connections
- **Controlled test data** for predictable results
- **User object mocking** for permission testing

### Test Organization
- **Unit tests** focus on individual methods and classes
- **Integration tests** focus on component interaction
- **Functional tests** focus on business logic workflows

### Validation Coverage
- **Input validation** (usernames, emails, phone numbers)
- **Business rules** (duplicates, permissions, admin access)
- **Database operations** (CRUD operations, complex queries)
- **Error handling** (missing data, insufficient permissions)
- **Security checks** (access control, session validation)

## Test Data Patterns

### User Test Data
```php
$userData = [
    'id' => 1,
    'username' => 'testuser',
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test@example.com',
    'phone' => '555-1234',
    'department' => 2,
    'can_add' => 1,
    'can_checkin' => 1,
    'pw_reset_code' => null
];
```

### Department Test Data
```php
$departmentData = [
    'id' => 1,
    'name' => 'Engineering'
];
```

### Admin Test Data
```php
$adminData = [
    'id' => 1,
    'admin' => 1
];
```

### Reviewer Test Data
```php
$reviewerData = [
    'dept_id' => 1,
    'user_id' => 5
];
```

## Dependencies

### Required Classes
- `User` - Main model class being tested
- `databaseData` - Parent class with shared functionality
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
6. Test error conditions and edge cases

### Test Data Management
- Use the helper methods in `TestCase.php`
- Create realistic but controlled test data
- Avoid hard-coded values where possible
- Use factories for complex object creation

### Common Patterns
```php
// Standard PDO mock setup for User constructor
$mockStatement = \Mockery::mock(\PDOStatement::class);
$mockStatement->shouldReceive('execute')->andReturn(true);
$mockStatement->shouldReceive('fetch')->andReturn($userData);

$mockPdo = \Mockery::mock(PDO::class);
$mockPdo->shouldReceive('prepare')->andReturn($mockStatement);

$user = new User(1, $mockPdo);
```

## Best Practices Demonstrated

1. **Comprehensive Coverage** - Tests cover happy path, edge cases, and error conditions
2. **Isolation** - Each test is independent and doesn't rely on others
3. **Mocking** - External dependencies are properly mocked
4. **Readability** - Test names clearly describe what is being tested
5. **Maintainability** - Helper methods reduce code duplication
6. **Documentation** - Tests serve as documentation for expected behavior
7. **Security Testing** - Access control and permission validation
8. **Error Handling** - Constructor error scenarios and invalid inputs

## Security Testing Coverage

- **Access Control**: Admin vs. regular user permissions
- **Self-Access**: Users modifying their own profiles
- **Session Validation**: Proper session checking
- **SQL Injection Prevention**: Parameterized queries
- **Password Security**: Hashing and validation
- **Demo Mode**: Restrictions in demo environment

## Business Logic Testing

- **User Lifecycle**: Create, read, update, delete operations
- **Permission Management**: Add, check-in, admin, reviewer permissions
- **Department Integration**: User-department relationships
- **Email Notifications**: Account creation notifications
- **Data Validation**: Input sanitization and validation
- **Duplicate Prevention**: Username uniqueness enforcement

## Future Enhancements

Potential areas for additional test coverage:
- Performance testing for large user lists
- Concurrency testing for simultaneous operations
- LDAP authentication integration testing
- Password complexity policy testing
- User activity logging testing
- File permission inheritance testing
- Bulk user operations testing
- API endpoint testing (if applicable)
- Multi-language support testing
- Audit trail testing

## Error Scenarios Tested

- Invalid user IDs in constructor
- Database connection failures
- Missing required fields
- Invalid email formats
- Duplicate usernames
- Insufficient permissions
- Session timeouts
- Demo mode restrictions
- Password validation failures
- Department assignment errors

This comprehensive test suite ensures that the User model and controller are robust, secure, and maintainable while providing excellent documentation of expected behavior through executable tests.