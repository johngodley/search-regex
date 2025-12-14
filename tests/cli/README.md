# WP CLI Unit Tests

This directory contains unit tests for the Search Regex WP CLI commands.

## Test Coverage

The test suite covers the following functionality:

### `extract_matched_texts()` Method Tests

1. **Basic Functionality**
   - Extracts matched text from search results
   - Handles multiple matches within a single context
   - Handles multiple columns with different matches
   - Handles multiple contexts within columns

2. **Text Truncation**
   - Truncates text longer than 50 characters
   - Adds `...` suffix to truncated text
   - Tests exact boundary conditions (50 vs 51 characters)
   - Preserves text that's exactly 50 characters or less

3. **Edge Cases**
   - Empty columns array
   - Empty contexts array
   - Empty matches array
   - Missing 'match' key in match data
   - Special characters preservation (HTML, email, ampersands)

4. **Data Structure Handling**
   - Navigates through nested result structure (columns → contexts → matches)
   - Gracefully handles missing or malformed data
   - Returns empty array when no matches are found

## Setup

To run these tests, you need a WordPress test environment:

### 1. Install WordPress Tests

```bash
# Install the WordPress test library
./bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

### 2. Configure Environment

Set the `WP_TESTS_DIR` environment variable:

```bash
export WP_TESTS_DIR=/path/to/wordpress-tests-lib
```

Or modify `tests/bootstrap.php` to point to your test library location.

## Running Tests

### Run All CLI Tests

```bash
./vendor/bin/phpunit tests/cli/
```

### Run Specific Test File

```bash
./vendor/bin/phpunit tests/cli/test-cli.php
```

### Run Specific Test Method

```bash
./vendor/bin/phpunit --filter test_extract_matched_texts_with_matches tests/cli/test-cli.php
```

## Test Structure

Each test follows this pattern:

1. **Arrange**: Create test data with specific structure
2. **Act**: Call the method being tested using reflection (since it's private)
3. **Assert**: Verify the output matches expected behavior

Example:
```php
public function test_extract_matched_texts_with_matches() {
    // Arrange - Create test data
    $result = [
        'columns' => [
            [
                'contexts' => [
                    [
                        'matches' => [
                            [ 'match' => 'Hello' ],
                            [ 'match' => 'World' ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    // Act - Call private method via reflection
    $reflection = new ReflectionClass( $this->cli );
    $method = $reflection->getMethod( 'extract_matched_texts' );
    $method->setAccessible( true );
    $matched_texts = $method->invoke( $this->cli, $result );

    // Assert - Verify results
    $this->assertCount( 2, $matched_texts );
    $this->assertEquals( 'Hello', $matched_texts[0] );
    $this->assertEquals( 'World', $matched_texts[1] );
}
```

## Adding New Tests

When adding new WP CLI functionality:

1. Create test methods following the naming convention: `test_<functionality>_<scenario>()`
2. Test both success and failure cases
3. Test edge cases and boundary conditions
4. Document complex test scenarios with inline comments

## CI/CD Integration

These tests can be integrated into CI/CD pipelines using GitHub Actions, GitLab CI, or other platforms. Example GitHub Actions workflow:

```yaml
name: PHP Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.0'

      - name: Install dependencies
        run: composer install

      - name: Setup WordPress test environment
        run: |
          bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

      - name: Run tests
        run: ./vendor/bin/phpunit tests/cli/
```
