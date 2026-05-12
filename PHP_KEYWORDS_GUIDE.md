# PHP Keywords Used in ShowFlow Codebase

This document lists all PHP keywords found in the ShowFlow cinema management system, along with explanations and real examples from the actual codebase.

---

## Table of Contents

1. [File Inclusion Keywords](#file-inclusion-keywords)
2. [Control Structure Keywords](#control-structure-keywords)
3. [Loop Keywords](#loop-keywords)
4. [Function Keywords](#function-keywords)
5. [Variable & Data Keywords](#variable--data-keywords)
6. [Exception Handling Keywords](#exception-handling-keywords)
7. [Operator Keywords](#operator-keywords)
8. [Other Keywords](#other-keywords)

---

## File Inclusion Keywords

### 1. **require**

**What it does:** Includes and evaluates a specified file. If the file is not found, it produces a fatal error and stops the script execution.

**Example:**
```php
<?php
require 'db.php';           // Include database connection
require 'session_handler.php'; // Include session management functions
require 'constants.php';    // Include application constants

// File not found = Fatal Error and script stops
```

**Used in ShowFlow:** All pages include `db.php`, `session_handler.php`, and `constants.php` at the top.

---

### 2. **require_once**

**What it does:** Similar to `require`, but ensures the file is included only once, even if you call it multiple times.

**Example:**
```php
<?php
require_once 'db.php';  // If already included, won't include again
require_once 'db.php';  // This call is ignored
```

**Used in ShowFlow:** Used in files where multiple includes might happen to prevent redeclaration errors.

---

### 3. **include**

**What it does:** Includes and evaluates a file. If the file is not found, it produces a warning but continues executing the script.

**Example:**
```php
<?php
include 'optional_config.php';  // If not found, just warn and continue
// Script keeps running even if file is missing
```

**Used in ShowFlow:** Less common than `require`, used for optional files.

---

### 4. **include_once**

**What it does:** Like `include` but includes the file only once.

**Example:**
```php
<?php
include_once 'config.php';
// Won't include again even if called multiple times
```

**Used in ShowFlow:** Used for optional configuration files that might be included multiple times.

---

## Control Structure Keywords

### 5. **if**

**What it does:** Executes a block of code if a condition is true.

**Example:**
```php
<?php
require 'session_handler.php';

if (!hasRole(ROLE_USER)) {
    header('Location: user-login.php');
    exit();
}

// Only runs if user is logged in
```

**Used in ShowFlow:** Checks user roles, validates input data, verifies database results.

---

### 6. **else**

**What it does:** Executes a block of code if the `if` condition is false.

**Example:**
```php
<?php
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "User not found";
}
```

**Used in ShowFlow:** Used after `if` statements to handle alternative scenarios.

---

### 7. **elseif**

**What it does:** Tests another condition if the previous `if` condition was false.

**Example:**
```php
<?php
$action = $_POST['action'] ?? '';

if ($action === 'add_movie') {
    // Add movie logic
} elseif ($action === 'edit_movie') {
    // Edit movie logic
} elseif ($action === 'delete_movie') {
    // Delete movie logic
}
```

**Used in ShowFlow:** Handler files use multiple `elseif` statements to process different actions.

---

### 8. **switch**

**What it does:** Selects one of many code blocks to execute based on a single expression.

**Example:**
```php
<?php
$sort = $_GET['sort'] ?? 'earnings';

switch ($sort) {
    case 'rating':
        $orderBy = "ORDER BY avg_rating DESC";
        break;
    case 'release_date':
        $orderBy = "ORDER BY mov_release_date DESC";
        break;
    default:
        $orderBy = "ORDER BY total_earnings DESC";
}
```

**Used in ShowFlow:** Sorting options and action routing use switch statements.

---

### 9. **case**

**What it does:** Specifies a value to match in a `switch` statement.

**Example:** (See switch example above)

---

### 10. **default**

**What it does:** Specifies code to run if no cases match in a `switch` statement.

**Example:** (See switch example above)

---

### 11. **break**

**What it does:** Exits from a loop or `switch` statement.

**Example:**
```php
<?php
switch ($action) {
    case 'login':
        // Login code
        break;  // Exit switch and don't check other cases
    case 'logout':
        // Logout code
        break;
}
```

**Used in ShowFlow:** Used in switch statements to prevent falling through to the next case.

---

## Loop Keywords

### 12. **foreach**

**What it does:** Loops through each item in an array or object.

**Example:**
```php
<?php
$movies = [];
while ($row = $result->fetch_assoc()) {
    $movies[] = $row;
}

// Loop through all movies
foreach ($movies as $movie) {
    echo htmlspecialchars($movie['mov_name']);
}

// Loop with key and value
foreach ($movies as $key => $movie) {
    echo "Movie {$key}: " . $movie['mov_name'];
}
```

**Used in ShowFlow:** Displays movies, bookings, complaints - almost every list in the codebase.

---

### 13. **while**

**What it does:** Executes a block of code repeatedly as long as a condition is true.

**Example:**
```php
<?php
$result = $conn->query($query);
$movies = [];

while ($row = $result->fetch_assoc()) {
    $movies[] = $row;  // Keep fetching rows until no more exist
}
```

**Used in ShowFlow:** Fetches multiple database rows into an array.

---

### 14. **for**

**What it does:** Executes code a specific number of times.

**Example:**
```php
<?php
// Loop 100 times
for ($i = 0; $i < 100; $i++) {
    echo "Iteration " . $i;
}

// Loop through seat rows
for ($row = 0; $row < $total_rows; $row++) {
    for ($col = 0; $col < $total_columns; $col++) {
        echo "Seat: {$row}-{$col}";
    }
}
```

**Used in ShowFlow:** Less common than foreach, used for generating seat grids.

---

### 15. **continue**

**What it does:** Skips the current iteration and continues to the next iteration in a loop.

**Example:**
```php
<?php
foreach ($movies as $movie) {
    if ($movie['mov_status'] === 'inactive') {
        continue;  // Skip inactive movies
    }
    echo $movie['mov_name'];  // Only shows active movies
}
```

**Used in ShowFlow:** Skips invalid or unwanted records during processing.

---

## Function Keywords

### 16. **function**

**What it does:** Declares a reusable block of code that can be called multiple times.

**Example:**
```php
<?php
// Define a function
function getUserWalletBalance($u_id) {
    global $conn;
    
    $balance_query = "SELECT current_balance FROM balance WHERE u_id = ?";
    $stmt = $conn->prepare($balance_query);
    $stmt->bind_param("s", $u_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (float)$row['current_balance'];
    }
    return 0;
}

// Call the function
$balance = getUserWalletBalance($user_id);
```

**Used in ShowFlow:** Session functions, wallet functions, utility functions.

---

### 17. **return**

**What it does:** Exits a function and optionally returns a value to the caller.

**Example:**
```php
<?php
function isLoggedIn() {
    return isset($_SESSION['role']) && isset($_SESSION['user_id']);
}

function calculateTotal($quantity, $price) {
    return $quantity * $price;
}

// Using return values
if (isLoggedIn()) {
    echo "User is logged in";
}

$total = calculateTotal(5, 100);  // Returns 500
```

**Used in ShowFlow:** Almost every function returns a value.

---

### 18. **global**

**What it does:** Declares that a variable is global (accessible everywhere), not just in the current function scope.

**Example:**
```php
<?php
$conn = new mysqli(...);  // Global database connection

function getUserWalletBalance($u_id) {
    global $conn;  // Access the global $conn variable
    
    $balance_query = "SELECT current_balance FROM balance WHERE u_id = ?";
    $stmt = $conn->prepare($balance_query);
    // ... rest of function
}
```

**Used in ShowFlow:** Functions access the global `$conn` database connection.

---

## Variable & Data Keywords

### 19. **isset()**

**What it does:** Checks if a variable is set and not null.

**Example:**
```php
<?php
// Check if session variable exists
if (!isset($_SESSION['u_id'])) {
    throw new Exception('User not logged in');
}

// Check if POST data exists
$action = $_POST['action'] ?? null;
if (isset($action)) {
    // Process action
}

// Check multiple variables
if (isset($_POST['email']) && isset($_POST['password'])) {
    // Both fields exist
}
```

**Used in ShowFlow:** Validates POST/GET data, checks session variables before using them.

---

### 20. **empty()**

**What it does:** Checks if a variable is empty (empty string, 0, false, null, empty array, etc.).

**Example:**
```php
<?php
$search = $_GET['search'] ?? '';

if (empty($search)) {
    // No search term provided
} else {
    // Search for movies
}

// Check multiple fields
if (!empty($mov_name) && !empty($mov_genre)) {
    // Both have values
}
```

**Used in ShowFlow:** Validates form inputs, checks if fields have values.

---

### 21. **array()**

**What it does:** Creates an array (list of values). Can also be used with short syntax `[]`.

**Example:**
```php
<?php
// Traditional syntax
$movies = array('Avatar', 'Titanic', 'Inception');

// Short syntax (modern)
$movies = ['Avatar', 'Titanic', 'Inception'];

// Associative array (key => value)
$movie = array(
    'mov_id' => 1,
    'mov_name' => 'Avatar',
    'mov_genre' => 'Sci-Fi'
);

// Short syntax
$movie = [
    'mov_id' => 1,
    'mov_name' => 'Avatar'
];

// Adding to array
$movies[] = 'Dune';
```

**Used in ShowFlow:** Stores database results, form data, API responses.

---

### 22. **unset()**

**What it does:** Destroys (deletes) a variable.

**Example:**
```php
<?php
$_SESSION['redirect_to'] = 'index.php';

// Later, after using it
unset($_SESSION['redirect_to']);

// Clean up after login
unset($_SESSION['success']);
unset($_SESSION['error']);
```

**Used in ShowFlow:** Clears temporary session variables after use.

---

### 23. **define()**

**What it does:** Creates a constant (a value that cannot be changed).

**Example:**
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'showflow');
define('ROLE_USER', 'user');
define('ROLE_MANAGER', 'manager');

// Constants are used without $
$connection = mysqli(..., DB_HOST, ..., DB_NAME);

if ($_SESSION['role'] === ROLE_USER) {
    // User is a regular user
}
```

**Used in ShowFlow:** Defined in `constants.php` for database credentials and roles.

---

### 24. **list()**

**What it does:** Assigns variables as if they were an array (unpacking).

**Example:**
```php
<?php
$person = ['John', 'Doe', 25];

list($firstname, $lastname, $age) = $person;
echo $firstname;  // John
echo $lastname;   // Doe
echo $age;        // 25

// Modern syntax (PHP 7.1+)
[$firstname, $lastname, $age] = $person;
```

**Used in ShowFlow:** Less common, but used for unpacking database rows.

---

### 25. **extract()**

**What it does:** Converts an associative array into variables.

**Example:**
```php
<?php
$movie = [
    'mov_id' => 1,
    'mov_name' => 'Avatar',
    'mov_genre' => 'Sci-Fi'
];

extract($movie);

echo $mov_id;     // 1
echo $mov_name;   // Avatar
echo $mov_genre;  // Sci-Fi
```

**Used in ShowFlow:** Rarely used, but helpful for converting query results to variables.

---

### 26. **compact()**

**What it does:** Creates an array from variables.

**Example:**
```php
<?php
$mov_name = 'Avatar';
$mov_genre = 'Sci-Fi';
$mov_rating = 4.5;

$movie = compact('mov_name', 'mov_genre', 'mov_rating');
// Result: ['mov_name' => 'Avatar', 'mov_genre' => 'Sci-Fi', 'mov_rating' => 4.5]
```

**Used in ShowFlow:** Passing multiple variables to functions cleanly.

---

## Exception Handling Keywords

### 27. **try**

**What it does:** Marks a block of code to monitor for errors (exceptions).

**Example:**
```php
<?php
try {
    // Code that might cause an error
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid Request Method');
    }
    
    if (!isset($_SESSION['u_id'])) {
        throw new Exception('User not logged in');
    }
    
    // ... process request
    
} catch (Exception $e) {
    // Handle the error
    jsonResponse('error', $e->getMessage());
}
```

**Used in ShowFlow:** API handlers and critical operations wrap code in try-catch.

---

### 28. **catch**

**What it does:** Catches and handles an exception thrown in a try block.

**Example:** (See try example above)

---

### 29. **finally**

**What it does:** Executes code after try-catch, regardless of whether an exception was thrown.

**Example:**
```php
<?php
try {
    $file = fopen('data.txt', 'r');
    $content = fread($file, 100);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    // Always close the file
    fclose($file);
}
```

**Used in ShowFlow:** Less common, but useful for cleanup operations.

---

### 30. **throw**

**What it does:** Manually throws an exception.

**Example:**
```php
<?php
try {
    $mov_id = intval($_POST['mov_id'] ?? 0);
    
    if ($mov_id <= 0) {
        throw new Exception('Invalid movie ID');
    }
    
    // Process movie
    
} catch (Exception $e) {
    jsonResponse('error', $e->getMessage());
}
```

**Used in ShowFlow:** Handler files throw exceptions for validation errors.

---

### 31. **new**

**What it does:** Creates a new instance (object) of a class.

**Example:**
```php
<?php
// Create a new database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Create a new Exception
throw new Exception('Error message');
```

**Used in ShowFlow:** Creates database connections and exception objects.

---

## Operator Keywords

### 32. **and / AND**

**What it does:** Logical AND operator (both conditions must be true).

**Example:**
```php
<?php
if (isset($_SESSION['u_id']) AND isset($_SESSION['role'])) {
    // Both session variables exist
}

// Modern style (preferred)
if (isset($_SESSION['u_id']) && isset($_SESSION['role'])) {
    // Both session variables exist
}
```

**Used in ShowFlow:** Multiple conditions must be true.

---

### 33. **or / OR**

**What it does:** Logical OR operator (at least one condition must be true).

**Example:**
```php
<?php
if ($status === 'Confirmed' OR $status === 'Pending') {
    // Status is either Confirmed or Pending
}

// Modern style (preferred)
if ($status === 'Confirmed' || $status === 'Pending') {
    // Status is either Confirmed or Pending
}
```

**Used in ShowFlow:** Multiple alternative conditions.

---

### 34. **not / NOT / !**

**What it does:** Logical NOT operator (inverts the condition).

**Example:**
```php
<?php
if (!hasRole(ROLE_USER)) {
    header('Location: user-login.php');
    exit();
}

// Longer version
if (NOT hasRole(ROLE_USER)) {
    // ...
}
```

**Used in ShowFlow:** Checks if user is NOT logged in, if result is NOT found, etc.

---

### 35. **? :** (Ternary Operator)

**What it does:** Shorthand for if-else statement.

**Example:**
```php
<?php
// Long form
if ($result->num_rows > 0) {
    $status = 'success';
} else {
    $status = 'error';
}

// Short form (ternary)
$status = $result->num_rows > 0 ? 'success' : 'error';

// Display value
$sort_label = $sort === 'earnings' ? 'Highest Earnings' : 'Latest Release';

// HTML with ternary
echo '<option value="earnings" ' . ($sort === 'earnings' ? 'selected' : '') . '>Highest Earnings</option>';
```

**Used in ShowFlow:** HTML forms with dynamic attributes, setting default values.

---

### 36. **?? (Null Coalescing Operator)**

**What it does:** Returns the first value if it's not null, otherwise returns the second value.

**Example:**
```php
<?php
// Long form
$action = isset($_POST['action']) ? $_POST['action'] : 'default';

// Short form (null coalescing)
$action = $_POST['action'] ?? 'default';

// Used in ShowFlow
$search = $_GET['search'] ?? '';
$s_id = intval($_GET['s_id'] ?? 0);
$amount = floatval($_GET['amount'] ?? 0);
$tab = 'login';
```

**Used in ShowFlow:** Provides default values for missing GET/POST parameters.

---

### 37. **=>**

**What it does:** Maps a key to a value in an associative array.

**Example:**
```php
<?php
$movie = [
    'mov_id' => 1,
    'mov_name' => 'Avatar',
    'mov_genre' => 'Sci-Fi',
    'rating' => 4.5
];

foreach ($movies as $key => $value) {
    echo "$key: $value";
}
```

**Used in ShowFlow:** Array declarations and foreach loops with key-value pairs.

---

## Other Keywords

### 38. **echo**

**What it does:** Outputs (prints) text or variables to the screen.

**Example:**
```php
<?php
echo "Hello World";
echo $user_name;
echo htmlspecialchars($movie['mov_name']);
?>

<!-- In HTML context -->
<p><?php echo "Hello"; ?></p>

<!-- Shorthand (if short tags are enabled) -->
<p><?= "Hello" ?></p>
```

**Used in ShowFlow:** Outputs JSON responses, HTML content, variable values.

---

### 39. **die() / exit()**

**What it does:** Terminates script execution immediately.

**Example:**
```php
<?php
if (!$conn) {
    die('Database connection failed');  // Stop here
}

// Check login
if (!isLoggedIn()) {
    header('Location: user-login.php');
    exit();  // Stop script, don't execute further
}
```

**Used in ShowFlow:** Stops execution after database errors or redirects.

---

### 40. **header()**

**What it does:** Sends HTTP headers to the browser.

**Example:**
```php
<?php
// Redirect to another page
header('Location: user-login.php');

// Set content type
header('Content-Type: application/json');

// Set HTTP status
header('HTTP/1.0 403 Forbidden');

// Always exit after header redirect
header('Location: index.php');
exit();
```

**Used in ShowFlow:** Redirects users, sets JSON response type, sends HTTP error codes.

---

### 41. **print**

**What it does:** Similar to echo, but print is technically a language construct and returns 1.

**Example:**
```php
<?php
print "Hello World";
print $variable;

// Less common than echo in ShowFlow
```

**Used in ShowFlow:** Rarely used, echo is preferred.

---

### 42. **var_dump()**

**What it does:** Displays structured information about a variable (for debugging).

**Example:**
```php
<?php
$movie = ['mov_id' => 1, 'mov_name' => 'Avatar'];
var_dump($movie);
// Output:
// array(2) {
//   ['mov_id']=> int(1)
//   ['mov_name']=> string(6) "Avatar"
// }
```

**Used in ShowFlow:** Development and debugging only.

---

### 43. **print_r()**

**What it does:** Prints human-readable information about a variable.

**Example:**
```php
<?php
$movie = ['mov_id' => 1, 'mov_name' => 'Avatar'];
print_r($movie);
// Output:
// Array ( [mov_id] => 1 [mov_name] => Avatar )
```

**Used in ShowFlow:** Debugging and logging.

---

### 44. **class**

**What it does:** Declares a class (blueprint for creating objects).

**Example:**
```php
<?php
class Movie {
    public $mov_id;
    public $mov_name;
    
    public function __construct($id, $name) {
        $this->mov_id = $id;
        $this->mov_name = $name;
    }
    
    public function getDetails() {
        return "Movie: " . $this->mov_name;
    }
}

// Using the class
$movie = new Movie(1, 'Avatar');
echo $movie->getDetails();
```

**Used in ShowFlow:** Rarely used, most functionality uses procedural PHP.

---

### 45. **public**

**What it does:** Access modifier - allows properties/methods to be accessed from anywhere.

**Example:**
```php
<?php
class Movie {
    public $mov_name;      // Can be accessed from outside
    private $mov_price;    // Cannot be accessed from outside
    
    public function getPrice() {
        return $this->mov_price;
    }
}
```

**Used in ShowFlow:** If using OOP (classes), marks public methods.

---

### 46. **private**

**What it does:** Access modifier - restricts property/method access to only within the class.

**Example:** (See public example above)

---

### 47. **protected**

**What it does:** Access modifier - allows access within the class and inherited classes.

**Example:**
```php
<?php
class BaseClass {
    protected $value;  // Can be accessed in child classes
}

class ChildClass extends BaseClass {
    public function showValue() {
        echo $this->value;  // Allowed
    }
}
```

**Used in ShowFlow:** Less common in this codebase.

---

### 48. **static**

**What it does:** Marks a property or method that belongs to the class itself, not to instances.

**Example:**
```php
<?php
class Counter {
    public static $count = 0;
    
    public static function increment() {
        self::$count++;
    }
}

// Access static without creating instance
Counter::increment();
echo Counter::$count;  // 1
```

**Used in ShowFlow:** Rarely used, mostly procedural functions.

---

### 49. **extends**

**What it does:** Indicates that a class inherits from another class.

**Example:**
```php
<?php
class BaseClass {
    public function greet() {
        return "Hello";
    }
}

class ChildClass extends BaseClass {
    public function greet() {
        return parent::greet() . " World";
    }
}
```

**Used in ShowFlow:** Not commonly used, mostly procedural PHP.

---

### 50. **implements**

**What it does:** Indicates that a class implements an interface.

**Example:**
```php
<?php
interface Payable {
    public function pay();
}

class Booking implements Payable {
    public function pay() {
        // Payment logic
    }
}
```

**Used in ShowFlow:** Not used in this codebase.

---

### 51. **namespace**

**What it does:** Organizes code into namespaces to avoid naming conflicts.

**Example:**
```php
<?php
namespace ShowFlow\API;

class Handler {
    // ...
}

// Using namespaced class
$handler = new ShowFlow\API\Handler();
```

**Used in ShowFlow:** Not used in this codebase (no namespaces).

---

### 52. **use**

**What it does:** Imports a namespace or trait into the current scope.

**Example:**
```php
<?php
use ShowFlow\Database\Connection;

$conn = new Connection();
```

**Used in ShowFlow:** Not used (no namespaces/traits).

---

### 53. **const**

**What it does:** Declares a constant within a class.

**Example:**
```php
<?php
class Config {
    const DB_HOST = 'localhost';
    const DB_USER = 'root';
}

echo Config::DB_HOST;
```

**Used in ShowFlow:** Not commonly used, `define()` is used instead.

---

### 54. **final**

**What it does:** Prevents a class or method from being overridden or extended.

**Example:**
```php
<?php
final class CoreClass {
    // Cannot be extended
}

class Parent {
    final public function criticalMethod() {
        // Cannot be overridden
    }
}
```

**Used in ShowFlow:** Not used in this codebase.

---

### 55. **abstract**

**What it does:** Declares a class or method as abstract (must be implemented by child classes).

**Example:**
```php
<?php
abstract class PaymentProvider {
    abstract public function processPayment();
}

class PayPal extends PaymentProvider {
    public function processPayment() {
        // Implementation
    }
}
```

**Used in ShowFlow:** Not used in this codebase.

---

### 56. **interface**

**What it does:** Defines a contract for what methods a class must implement.

**Example:**
```php
<?php
interface Loggable {
    public function log($message);
}

class UserLogger implements Loggable {
    public function log($message) {
        // Logging implementation
    }
}
```

**Used in ShowFlow:** Not used in this codebase.

---

### 57. **trait**

**What it does:** Allows you to reuse methods across multiple classes (prevents code duplication).

**Example:**
```php
<?php
trait Timestampable {
    public function getCreatedAt() {
        return $this->created_at;
    }
}

class Booking {
    use Timestampable;
}
```

**Used in ShowFlow:** Not used in this codebase.

---

### 58. **clone**

**What it does:** Creates a copy of an object.

**Example:**
```php
<?php
$movie1 = new Movie('Avatar', 100);
$movie2 = clone $movie1;  // Creates an independent copy
$movie2->setPrice(150);   // Doesn't affect $movie1
```

**Used in ShowFlow:** Not used in this codebase.

---

### 59. **instanceof**

**What it does:** Checks if an object is an instance of a class.

**Example:**
```php
<?php
if ($booking instanceof Booking) {
    echo "This is a booking";
}

if ($payment instanceof PayPal) {
    echo "PayPal payment";
}
```

**Used in ShowFlow:** Not used in this codebase (no OOP).

---

### 60. **yield**

**What it does:** Used in generator functions to yield values one at a time.

**Example:**
```php
<?php
function numberGenerator() {
    yield 1;
    yield 2;
    yield 3;
}

foreach (numberGenerator() as $num) {
    echo $num;
}
```

**Used in ShowFlow:** Not used in this codebase.

---

### 61. **eval()**

**What it does:** Evaluates and executes PHP code from a string (DANGEROUS - avoid!).

**Example:**
```php
<?php
$code = 'echo "Hello";';
eval($code);  // Outputs: Hello

// DANGEROUS - Never use user input!
$userInput = $_GET['code'];
eval($userInput);  // Security risk!
```

**Used in ShowFlow:** NOT used (security risk).

---

### 62. **callable**

**What it does:** Type hint for variables that contain a callable function or method.

**Example:**
```php
<?php
function executeCallback(callable $callback) {
    $callback();
}

executeCallback(function() {
    echo "Callback executed";
});
```

**Used in ShowFlow:** Not commonly used.

---

### 63. **declare**

**What it does:** Defines execution directives for a code block.

**Example:**
```php
<?php
declare(strict_types=1);  // Enforce strict type checking

function add(int $a, int $b): int {
    return $a + $b;
}
```

**Used in ShowFlow:** Not used in this codebase.

---

### 64. **goto**

**What it does:** Jumps to a labeled location in the code (NOT recommended).

**Example:**
```php
<?php
if ($error) {
    goto error_handler;
}

// ... code ...

error_handler:
echo "Error occurred";
```

**Used in ShowFlow:** NOT used (bad practice).

---

### 65. **insteadof**

**What it does:** Resolves trait conflicts by specifying which trait method to use.

**Example:**
```php
<?php
trait A {
    public function say() { echo "A"; }
}

trait B {
    public function say() { echo "B"; }
}

class C {
    use A, B {
        B::say insteadof A;  // Use B's say() method
    }
}
```

**Used in ShowFlow:** Not used (no traits).

---

## Summary Table

| Category | Keywords | Usage |
|----------|----------|-------|
| **File Inclusion** | require, require_once, include, include_once | Load external files |
| **Control Flow** | if, else, elseif, switch, case, default, break | Decision making |
| **Loops** | foreach, while, for, continue | Repeated execution |
| **Functions** | function, return, global | Code reusability |
| **Variables** | isset, empty, array, unset, list, extract, compact | Data management |
| **Exceptions** | try, catch, finally, throw, new | Error handling |
| **Operators** | and/OR, not/!, ? :, ??, => | Logical operations |
| **Output** | echo, print, var_dump, print_r | Display information |
| **HTTP** | header, exit, die | Server communication |
| **OOP** | class, public, private, protected, static, extends, implements | Object-oriented programming |
| **Advanced** | namespace, use, trait, clone, instanceof, yield | Advanced features |

---

## Most Commonly Used Keywords in ShowFlow

1. **require** - Every page loads external files
2. **echo** - Outputs HTML and JSON
3. **if/else** - Control flow throughout
4. **foreach** - Loops through database results
5. **isset()** - Validates POST/GET/SESSION data
6. **array()** / `[]` - Stores data
7. **header()** - Redirects users
8. **$_POST/$_GET/$_SESSION** - Accesses request data
9. **function** - Defines reusable code
10. **return** - Returns from functions
11. **try/catch** - Error handling in handlers
12. **while** - Fetches database rows
13. **?? (null coalescing)** - Provides defaults
14. **->** - Object property/method access
15. **new** - Creates database connections

---

## Notes

- ShowFlow is built with **procedural PHP** rather than object-oriented programming
- No namespaces or traits are used
- Database operations use prepared statements for security
- Most logic is in handler files that process POST requests and return JSON
- Session management and user authentication are critical throughout the application

