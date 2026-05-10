# ShowFlow Project - Complete Learning Guide
**A Step-by-Step Guide to Understanding HTML, CSS, PHP, and SQL in the ShowFlow Application**

---

# SECTION 1: LANGUAGE BASICS

## Chapter 1: Basic HTML Used in the Project

### What is HTML?
HTML (HyperText Markup Language) is the structure/skeleton of web pages. It defines what content appears and how it's organized.

### HTML Structure in ShowFlow

#### 1.1 Basic Page Template
Every page in ShowFlow follows this structure:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Title</title>
    <link rel="stylesheet" href="netflix-theme.css">
</head>
<body>
    <!-- Content goes here -->
</body>
</html>
```

**What each part does:**
- `<!DOCTYPE html>` - Tells browser this is HTML5
- `<head>` - Contains metadata, title, and CSS links
- `<title>` - Shows in browser tab
- `<link>` - Links external CSS file
- `<body>` - Contains all visible content

#### 1.2 Common HTML Elements Used in ShowFlow

**Containers & Structure:**
```html
<!-- Container for organizing content -->
<div class="container">Content</div>

<!-- Header section -->
<header class="header-container">
    <a href="index.php" class="logo">ShowFlow</a>
</header>

<!-- Navigation -->
<nav>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="all_movies.php">Movies</a></li>
    </ul>
</nav>
```

**Form Elements (used in login, booking, etc.):**
```html
<form method="POST" action="login_handler.php">
    <!-- Text input for email -->
    <input type="email" name="email" required>
    
    <!-- Password input -->
    <input type="password" name="password" required>
    
    <!-- Dropdown/Select -->
    <select name="movie_id">
        <option value="">Select a movie</option>
        <option value="1">Movie A</option>
    </select>
    
    <!-- Submit button -->
    <button type="submit">Login</button>
</form>
```

**Tables (used for displaying bookings, complaints, etc.):**
```html
<table>
    <thead>
        <tr>
            <th>Movie Name</th>
            <th>Theatre</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Avatar</td>
            <td>PVR Cinema</td>
            <td>May 15, 2024</td>
        </tr>
    </tbody>
</table>
```

**Modal/Popup Structure (very common in ShowFlow):**
```html
<!-- Modal is a popup overlay -->
<div id="movieModal" class="modal">
    <div class="modal-content">
        <!-- Close button -->
        <button class="modal-close" onclick="closeModal()">✕</button>
        
        <!-- Modal header -->
        <div class="modal-header">
            <h2>Movie Details</h2>
        </div>
        
        <!-- Modal body (actual content) -->
        <div class="modal-body">
            <p>Movie information here</p>
        </div>
    </div>
</div>
```

#### 1.3 How HTML is Used in ShowFlow Files

**Example from index.php:**
```html
<!-- This creates the movie grid on homepage -->
<div class="movies-grid">
    <?php foreach ($movies as $movie): ?>
        <div class="movie-card" onclick="showMovieDetails(<?php echo $movie['mov_id']; ?>)">
            <div class="movie-poster">
                <img src="<?php echo $movie['mov_poster']; ?>" alt="<?php echo $movie['mov_name']; ?>">
            </div>
            <div class="movie-info">
                <div class="movie-title"><?php echo $movie['mov_name']; ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
```

**Key concept:** Notice `<?php ... ?>` tags mixed with HTML. This is PHP embedded in HTML. We'll explain this more in the PHP chapter.

---

## Chapter 2: CSS Used Here (Especially Modals)

### What is CSS?
CSS (Cascading Style Sheets) controls how HTML looks - colors, sizes, positions, animations, etc.

### CSS Selectors Used in ShowFlow

```css
/* Class selector - targets elements with class="container" */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* ID selector - targets element with id="movieModal" */
#movieModal {
    display: none;  /* Hidden by default */
}

/* Element selector - targets all <button> elements */
button {
    background: #e50914;
    color: white;
    border: none;
    cursor: pointer;
}

/* Attribute selector - targets input with type="text" */
input[type="text"] {
    padding: 10px;
    border: 1px solid #ccc;
}
```

### 2.1 The Modal System (Very Important!)

**HTML Structure of a Modal:**
```html
<div id="movieModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeMovieModal()">✕</button>
        <div class="modal-header">
            <h2>Movie Details</h2>
        </div>
        <div class="modal-body">
            <!-- Content loads here via JavaScript -->
        </div>
    </div>
</div>
```

**CSS that Makes Modals Work:**
```css
/* Modal is hidden by default */
.modal {
    display: none;
    position: fixed;          /* Fixed position on screen */
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);  /* Dark overlay */
    z-index: 1000;            /* Appears on top */
    align-items: center;
    justify-content: center;
}

/* When modal.active class is added, it becomes visible */
.modal.active {
    display: flex;
}

/* The white/dark box inside the modal */
.modal-content {
    background: #1a1a1a;      /* Dark background (Netflix theme) */
    border-radius: 12px;
    max-width: 700px;
    width: 90%;
    padding: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.9);
}

/* Close button styling */
.modal-close {
    position: absolute;
    top: 10px;
    right: 10px;
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
}
```

### 2.2 How CSS Classes Are Used

**Netflix Theme Used in ShowFlow:**
```css
/* Primary background (very dark) */
:root {
    --bg-primary: #0f0f0f;
    --bg-secondary: #1a1a1a;
    --text-primary: #ffffff;
    --accent-red: #e50914;
}

/* Applied to body */
body {
    background: var(--bg-primary);
    color: var(--text-primary);
    font-family: Arial, sans-serif;
}

/* Buttons */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary {
    background: var(--accent-red);
    color: white;
}

.btn-primary:hover {
    background: #cc0710;
    transform: scale(1.02);
}
```

### 2.3 Responsive CSS (Works on Mobile/Desktop)

ShowFlow uses CSS Grid and Flexbox to be responsive:

```css
/* Desktop view */
.profile-content {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
}

/* Mobile view - single column */
@media (max-width: 1024px) {
    .profile-content {
        grid-template-columns: 1fr;
    }
}
```

---

## Chapter 3: JavaScript (Script)

### What is JavaScript?
JavaScript makes web pages interactive - it handles clicks, shows/hides elements, validates forms, communicates with servers, etc.

### 3.1 Basic JavaScript Used in ShowFlow

**Opening and Closing Modals:**
```javascript
// Show modal
function openAddMovieModal() {
    document.getElementById('addMovieModal').classList.add('active');
}

// Close modal
function closeAddMovieModal() {
    document.getElementById('addMovieModal').classList.remove('active');
    document.getElementById('addMovieForm').reset();  // Clear form
}
```

**Switching Between Tabs:**
```javascript
function switchTab(event, tabName) {
    event.preventDefault();  // Don't refresh page
    
    // Hide all tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Remove active class from all buttons
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}
```

### 3.2 Fetching Data from Server (AJAX)

**Making a Request to PHP Handler:**
```javascript
function calculateMovieEarnings() {
    const movId = document.getElementById('earningsMovieSelect').value;
    
    if (!movId) return;

    // Fetch data from server
    fetch('api_movie_earnings.php?mov_id=' + movId + '&t_id=1')
        .then(response => response.json())  // Convert response to JSON
        .then(data => {
            // Handle successful response
            if (data.status === 'success') {
                document.getElementById('totalRevenue').textContent = 
                    '৳' + parseFloat(data.earnings.total_amount).toLocaleString();
            }
        })
        .catch(error => {
            // Handle error
            alert('Error: ' + error.message);
        });
}
```

### 3.3 Sending Form Data (POST Request)

**Example: Submitting a Review**
```javascript
function submitModalReview(event, movId) {
    event.preventDefault();  // Don't submit normally
    
    // Get form values
    const rating = document.getElementById('modalRating').value;
    const reviewText = document.getElementById('modalReviewText').value;
    
    // Create form data
    const params = new URLSearchParams();
    params.append('action', 'add_review');
    params.append('mov_id', movId);
    params.append('rating', rating);
    params.append('review_text', reviewText);

    // Send to server
    fetch('add_review_handler.php', {
        method: 'POST',
        body: params.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('✅ Review posted!');
            location.reload();  // Refresh page
        } else {
            alert('❌ Error: ' + data.message);
        }
    });
}
```

### 3.4 Event Listeners

```javascript
// When user clicks a button
document.getElementById('submitBtn').addEventListener('click', function() {
    console.log('Button clicked!');
});

// When modal is clicked
document.getElementById('movieModal').addEventListener('click', function(event) {
    // Close modal only if clicking outside the content
    if (event.target === this) {
        closeMovieModal();
    }
});

// When user presses Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeMovieModal();
    }
});
```

---

## Chapter 4: PHP Knowledge

### What is PHP?
PHP (Hypertext Preprocessor) runs on the server and generates HTML dynamically. It can access databases, process forms, create sessions, etc.

### 4.1 PHP Basics

**Variables and Data Types:**
```php
<?php
// String
$movie_name = "Avatar";

// Number
$ticket_price = 250;

// Array
$movies = ["Avatar", "Titanic", "Inception"];
echo $movies[0];  // Output: Avatar

// Associative array (like dictionary)
$movie = [
    "name" => "Avatar",
    "price" => 250,
    "rating" => 4.5
];
echo $movie["name"];  // Output: Avatar

// Boolean
$is_logged_in = true;
?>
```

**Conditional Statements:**
```php
<?php
// If statement
if ($user_role === "admin") {
    echo "Welcome Admin";
} elseif ($user_role === "manager") {
    echo "Welcome Manager";
} else {
    echo "Welcome Guest";
}

// Switch statement
switch ($payment_method) {
    case "bkash":
        echo "BKash selected";
        break;
    case "nagad":
        echo "Nagad selected";
        break;
    default:
        echo "Unknown method";
}
?>
```

**Loops:**
```php
<?php
// For loop
for ($i = 0; $i < 5; $i++) {
    echo $i;  // Outputs: 01234
}

// Foreach loop (used heavily in ShowFlow)
$movies = ["Avatar", "Titanic", "Inception"];
foreach ($movies as $movie) {
    echo $movie . "<br>";
}

// Foreach with associative array
$booking = ["movie" => "Avatar", "theatre" => "PVR", "date" => "May 15"];
foreach ($booking as $key => $value) {
    echo $key . ": " . $value . "<br>";
}
?>
```

### 4.2 Working with Forms

**Getting Form Data:**
```php
<?php
// GET method (visible in URL)
$search = $_GET['search'] ?? 'default';

// POST method (hidden, for sensitive data)
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Checking if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form
    $name = trim($_POST['name']);
    if (empty($name)) {
        echo "Name is required";
    }
}
?>
```

### 4.3 Sessions (How ShowFlow Keeps Users Logged In)

**Session Basics:**
```php
<?php
// Start session (must be first line)
session_start();

// Set session variables after login
$_SESSION['u_id'] = 5;
$_SESSION['name'] = "John Doe";
$_SESSION['role'] = "user";

// Access session variables
echo $_SESSION['name'];  // Output: John Doe

// Check if user is logged in
if (isset($_SESSION['u_id'])) {
    echo "User is logged in";
} else {
    echo "User is not logged in";
}

// Clear session (logout)
session_destroy();
?>
```

### 4.4 Connecting to Database

**Database Connection (in db.php):**
```php
<?php
// Database credentials
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "showflow";

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character encoding
$conn->set_charset("utf8");
?>
```

### 4.5 Fetching Data from Database

**Simple Query:**
```php
<?php
// Simple query
$query = "SELECT * FROM movie LIMIT 10";
$result = $conn->query($query);

// Check if query was successful
if ($result && $result->num_rows > 0) {
    // Loop through results
    while ($row = $result->fetch_assoc()) {
        echo $row['mov_name'];
        echo $row['mov_poster'];
    }
} else {
    echo "No movies found";
}
?>
```

**Using Prepared Statements (Safer):**
```php
<?php
// Prepared statement (prevents SQL injection)
$u_id = 5;
$query = "SELECT * FROM user WHERE u_id = ?";
$stmt = $conn->prepare($query);

// Bind parameter (? is replaced with value)
$stmt->bind_param("i", $u_id);  // "i" means integer

// Execute
$stmt->execute();

// Get result
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo $user['name'];

// Close statement
$stmt->close();
?>
```

### 4.6 Inserting Data

**Example: Adding a New Movie**
```php
<?php
$movie_name = "Avatar 2";
$poster_url = "https://example.com/poster.jpg";

// Prepared statement
$query = "INSERT INTO movie (mov_name, mov_poster) VALUES (?, ?)";
$stmt = $conn->prepare($query);

// Bind parameters
$stmt->bind_param("ss", $movie_name, $poster_url);  // "ss" = string, string

// Execute
if ($stmt->execute()) {
    echo "Movie added successfully";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
?>
```

### 4.7 Updating Data

**Example: Update User Profile**
```php
<?php
$u_id = 5;
$new_name = "John Smith";
$new_contact = "01712345678";

$query = "UPDATE user SET name = ?, contact = ? WHERE u_id = ?";
$stmt = $conn->prepare($query);

// "s" = string, "s" = string, "i" = integer
$stmt->bind_param("ssi", $new_name, $new_contact, $u_id);

if ($stmt->execute()) {
    echo "Profile updated";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
?>
```

### 4.8 Returning JSON (Important for JavaScript Communication)

**Handler Example (add_review_handler.php):**
```php
<?php
require 'db.php';
require 'session_handler.php';

header('Content-Type: application/json');  // Tell browser it's JSON

try {
    $action = $_POST['action'] ?? null;
    if ($action !== 'add_review') {
        throw new Exception('Invalid action');
    }

    $mov_id = $_POST['mov_id'];
    $rating = $_POST['rating'];
    $u_id = $_SESSION['u_id'];

    // Insert review
    $query = "INSERT INTO review (mov_id, u_id, rating, comment) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isis", $mov_id, $u_id, $rating, $_POST['review_text']);
    $stmt->execute();

    // Send success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Review added'
    ]);

} catch (Exception $e) {
    // Send error response
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
```

---

## Chapter 5: SQL and JOINs

### What is SQL?
SQL (Structured Query Language) is used to interact with databases. You already know this, but here's how it's used in ShowFlow.

### 5.1 Basic Queries

**SELECT (Read Data):**
```sql
-- Get all movies
SELECT * FROM movie;

-- Get specific columns
SELECT mov_name, mov_price FROM movie;

-- Get with WHERE condition
SELECT * FROM user WHERE u_id = 5;

-- Get with sorting
SELECT * FROM movie ORDER BY created_at DESC LIMIT 10;
```

### 5.2 JOINs (Combining Data from Multiple Tables)

**INNER JOIN - Only rows that match in both tables:**
```sql
-- Get bookings with movie and theatre names
SELECT 
    b.book_id,
    b.booking_date,
    m.mov_name,          -- From movie table
    t.theatre_name,      -- From theatre table
    b.total_amount
FROM booking b
INNER JOIN movie m ON b.mov_id = m.mov_id
INNER JOIN theatre t ON b.t_id = t.t_id
WHERE b.u_id = 5;
```

**LEFT JOIN - All rows from left table + matching from right:**
```sql
-- Get all users and their complaints (even if no complaints)
SELECT 
    u.u_id,
    u.name,
    c.complaint_text,
    c.created_at
FROM user u
LEFT JOIN complaint c ON u.u_id = c.u_id
ORDER BY u.u_id;
```

**Multiple JOINs (Used heavily in ShowFlow):**
```sql
-- Complex query from user-profile.php
SELECT 
    b.book_id,
    b.booking_date,
    b.total_amount,
    m.mov_name,
    t.theatre_name,
    h.hall_name,
    s.show_date,
    s.show_time
FROM booking b
JOIN show_schedule s ON b.s_id = s.s_id
JOIN movie m ON s.mov_id = m.mov_id
JOIN theatre t ON s.t_id = t.t_id
JOIN hall h ON s.h_id = h.h_id
WHERE b.u_id = 5
ORDER BY s.show_date DESC;
```

### 5.3 Aggregate Functions

**COUNT, SUM, AVG, etc.:**
```sql
-- Count total bookings
SELECT COUNT(*) as total_bookings FROM booking WHERE u_id = 5;

-- Sum total amount spent
SELECT SUM(total_amount) as total_spent FROM booking WHERE u_id = 5;

-- Average rating
SELECT AVG(rating) as avg_rating FROM review WHERE mov_id = 1;

-- Count bookings per movie
SELECT m.mov_name, COUNT(b.book_id) as bookings
FROM booking b
JOIN show_schedule s ON b.s_id = s.s_id
JOIN movie m ON s.mov_id = m.mov_id
GROUP BY m.mov_id
ORDER BY bookings DESC;
```

### 5.4 Date Functions

```sql
-- Get data from last 7 days
SELECT * FROM booking 
WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 7 DAY);

-- Format date
SELECT DATE_FORMAT(created_at, '%d %b %Y') as formatted_date FROM complaint;

-- Compare dates
SELECT * FROM show_schedule WHERE show_date >= CURDATE();
```

---

# SECTION 2: PROJECT DEEP DIVE

## Chapter 1: Login System

### Overview
The login system authenticates users and managers. It creates a session so they stay logged in across pages.

### Flow Diagram:
```
User enters email/password
        ↓
user-login.php page loads
        ↓
User clicks "Login" button
        ↓
JavaScript sends POST to login_handler.php
        ↓
PHP checks credentials in database
        ↓
If correct: Create session → Redirect to index.php
If wrong: Show error message
```

### Key Files:
- **user-login.php** - Login form (HTML + CSS)
- **login_handler.php** - Processes login (PHP + Database)
- **session_handler.php** - Manages sessions

### Code Walkthrough:

**1. User-Login.php (What User Sees)**
```html
<form method="POST" action="login_handler.php">
    <input type="email" name="email" placeholder="Enter email" required>
    <input type="password" name="password" placeholder="Enter password" required>
    <button type="submit">Login</button>
</form>
```

**2. Login_handler.php (Backend Processing)**
```php
<?php
require 'db.php';
require 'session_handler.php';

try {
    // Get form data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        throw new Exception('Email and password required');
    }

    // Query database
    $query = "SELECT u_id, name, password FROM user WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('User not found');
    }

    $user = $result->fetch_assoc();
    
    // Check password
    if (!password_verify($password, $user['password'])) {
        throw new Exception('Invalid password');
    }

    // Create session
    $_SESSION['u_id'] = $user['u_id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = 'user';
    
    // Redirect to home
    header('Location: index.php');
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: user-login.php');
    exit();
}
?>
```

**3. How Session Keeps You Logged In**
```php
<?php
// On every page, this checks if user is logged in
session_start();

if (isset($_SESSION['u_id'])) {
    echo "Welcome " . $_SESSION['name'];
} else {
    // Redirect to login
    header('Location: user-login.php');
}
?>
```

---

## Chapter 2: Logout System

### Overview
Logout destroys the session and redirects user to home page.

### Flow:
```
User clicks "Logout"
        ↓
logout.php is loaded
        ↓
Session is destroyed (user data deleted)
        ↓
Redirect to index.php (base URL)
```

### Code:

**logout.php:**
```php
<?php
require 'session_handler.php';

// This function is in session_handler.php
logout();  // Destroys session and redirects
?>
```

**What logout() does (in session_handler.php):**
```php
function logout() {
    session_destroy();  // Delete all session data
    header('Location: ' . BASE_URL);  // Go to home page
    exit();
}
```

---

## Chapter 3: User Dashboard (User Profile Page)

### Overview
Dashboard shows user's bookings, reviews, complaints, wallet balance, etc.

### File: **user-profile.php**

### Data Flow:

**1. Fetch User Data (PHP)**
```php
<?php
// Get all user's bookings from database
$booking_query = "
    SELECT b.*, m.mov_name, t.theatre_name, h.hall_name
    FROM booking b
    JOIN show_schedule s ON b.s_id = s.s_id
    JOIN movie m ON s.mov_id = m.mov_id
    JOIN theatre t ON s.t_id = t.t_id
    JOIN hall h ON s.h_id = h.h_id
    WHERE b.u_id = ?
";

$stmt = $conn->prepare($booking_query);
$stmt->bind_param("s", $u_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
```

**2. Display Data in Tables (HTML)**
```html
<table>
    <thead>
        <tr>
            <th>Movie</th>
            <th>Theatre</th>
            <th>Date</th>
            <th>Price</th>
        </tr>
    </thead>
    <tbody>
        <!-- Loop through bookings -->
        <?php foreach ($bookings as $booking): ?>
            <tr>
                <td><?php echo $booking['mov_name']; ?></td>
                <td><?php echo $booking['theatre_name']; ?></td>
                <td><?php echo date('M d, Y', strtotime($booking['show_date'])); ?></td>
                <td>৳<?php echo $booking['total_amount']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

**3. Tab System (JavaScript)**
```javascript
// Show different sections when tabs are clicked
function switchTab(event, tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
}
```

### Sections in Dashboard:
1. **Upcoming Bookings** - Shows future shows
2. **Past Bookings** - Shows completed shows
3. **Food Orders** - Pending and delivered
4. **Messages** - Notifications from manager
5. **Reviews** - Movies user reviewed
6. **Past Complaints** - Support tickets
7. **Edit Profile** - Change name, contact, password

---

## Chapter 4: User Booking

### Overview
Users select a movie, choose seats, and pay to book tickets.

### Files:
- **index.php** - Browse movies
- **booking.php** - Select seats and confirm
- **booking-handler.php** - Process payment

### Step-by-Step Flow:

**Step 1: User Clicks "Book Tickets" on Movie**
```javascript
function bookTickets(movieId) {
    // Redirect to booking page with movie ID
    window.location.href = 'booking.php?movie=' + movieId;
}
```

**Step 2: Booking.php Shows Available Shows**
```php
<?php
$mov_id = $_GET['movie'] ?? null;

// Get all shows for this movie
$query = "
    SELECT s.*, t.theatre_name, h.hall_name
    FROM show_schedule s
    JOIN theatre t ON s.t_id = t.t_id
    JOIN hall h ON s.h_id = h.h_id
    WHERE s.mov_id = ? AND s.show_date >= CURDATE()
    ORDER BY s.show_date ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $mov_id);
$stmt->execute();
$shows = $stmt->get_result()->fetch_all();
?>
```

**Step 3: Display Seat Grid**
```html
<!-- Show available and booked seats -->
<div class="seat-grid">
    <?php for ($i = 1; $i <= $total_seats; $i++): ?>
        <button class="seat <?php echo in_array($i, $booked_seats) ? 'booked' : 'available'; ?>"
                onclick="selectSeat(<?php echo $i; ?>)">
            <?php echo $i; ?>
        </button>
    <?php endfor; ?>
</div>
```

**Step 4: Calculate Price and Book**
```javascript
function confirmBooking() {
    // Calculate total
    const selectedSeats = getSelectedSeats();
    const totalPrice = selectedSeats.length * ticketPrice;
    
    // Send to handler
    fetch('booking-handler.php', {
        method: 'POST',
        body: new FormData({
            'action': 'book_seats',
            'show_id': showId,
            'seats': selectedSeats.join(','),
            'total_price': totalPrice
        })
    }).then(response => response.json())
      .then(data => {
          if (data.status === 'success') {
              alert('Booking confirmed!');
              window.location.href = 'user-profile.php';
          }
      });
}
```

**Step 5: Booking-Handler.php Saves to Database**
```php
<?php
$s_id = $_POST['show_id'];
$seats = $_POST['seats'];
$total_amount = $_POST['total_price'];
$u_id = $_SESSION['u_id'];

// Create booking record
$query = "INSERT INTO booking (u_id, s_id, seat_numbers, total_amount, booking_date)
          VALUES (?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($query);
$stmt->bind_param("isis", $u_id, $s_id, $seats, $total_amount);

if ($stmt->execute()) {
    // Deduct from wallet
    $wallet_query = "UPDATE balance SET current_balance = current_balance - ? WHERE u_id = ?";
    $wallet_stmt = $conn->prepare($wallet_query);
    $wallet_stmt->bind_param("ds", $total_amount, $u_id);
    $wallet_stmt->execute();
    
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}
?>
```

---

## Chapter 5: Facilities

### Overview
Shows theatres and their facilities (parking, WiFi, AC, etc.).

### Files:
- **facilities.php** - Display theatres

### Key Code:

**Get All Theatres with Facilities:**
```php
<?php
$query = "SELECT * FROM theatre ORDER BY theatre_name";
$result = $conn->query($query);

while ($theatre = $result->fetch_assoc()) {
    // Get facilities for this theatre
    $fac_query = "SELECT * FROM facility WHERE t_id = ?";
    $fac_stmt = $conn->prepare($fac_query);
    $fac_stmt->bind_param("i", $theatre['t_id']);
    $fac_stmt->execute();
    $facilities = $fac_stmt->get_result()->fetch_all();
    
    echo "<h3>" . $theatre['theatre_name'] . "</h3>";
    echo "<p>Location: " . $theatre['location'] . "</p>";
    echo "Facilities: " . implode(", ", array_column($facilities, 'facility_name'));
}
?>
```

---

## Chapter 6: Manager - Movie Adding System

### Overview
Managers add movies to the system so users can book them.

### File:
- **manager-dashboard.php** - Manager interface
- **manager_operations_handler.php** - Add/Edit movie

### Flow:

**1. Manager Clicks "Add Movie" Button**
```javascript
function openAddMovieModal() {
    document.getElementById('addMovieModal').classList.add('active');
}
```

**2. Modal Form Appears (HTML)**
```html
<div id="addMovieModal" class="modal">
    <div class="modal-content">
        <form id="addMovieForm" onsubmit="submitAddMovie(event)">
            <input type="text" name="mov_name" placeholder="Movie name" required>
            <input type="text" name="mov_poster" placeholder="Poster URL">
            <input type="text" name="mov_genre" placeholder="Genre">
            <textarea name="mov_synopsis" placeholder="Synopsis"></textarea>
            <button type="submit">Add Movie</button>
        </form>
    </div>
</div>
```

**3. JavaScript Submits Form**
```javascript
function submitAddMovie(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('addMovieForm'));
    formData.append('action', 'add_movie');
    
    fetch('manager_operations_handler.php', {
        method: 'POST',
        body: formData
    }).then(response => response.json())
      .then(data => {
          if (data.status === 'success') {
              alert('Movie added!');
              location.reload();
          }
      });
}
```

**4. Handler Saves to Database**
```php
<?php
$action = $_POST['action'] ?? null;

if ($action === 'add_movie') {
    $mov_name = $_POST['mov_name'];
    $mov_poster = $_POST['mov_poster'];
    $mov_genre = $_POST['mov_genre'];
    $mov_synopsis = $_POST['mov_synopsis'];
    
    $query = "INSERT INTO movie (mov_name, mov_poster, mov_genre, mov_synopsis)
              VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $mov_name, $mov_poster, $mov_genre, $mov_synopsis);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Movie added']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
}
?>
```

---

## Chapter 7: Show Cancellation & Refund System

### Overview
Manager can cancel a show, and users are automatically refunded.

### Flow:

**1. Manager Deletes Show**
```javascript
function deleteShow(s_id, movieName) {
    if (confirm('Delete this show? Users will be refunded.')) {
        const formData = new FormData();
        formData.append('action', 'delete_show');
        formData.append('s_id', s_id);
        
        fetch('removing_show_handler.php', {
            method: 'POST',
            body: formData
        }).then(response => response.json())
          .then(data => {
              alert(data.users_refunded + ' users refunded with ৳' + data.total_refunded);
              location.reload();
          });
    }
}
```

**2. Handler Cancels Show and Refunds**
```php
<?php
$s_id = $_POST['s_id'];

// Start transaction (atomic operation)
$conn->begin_transaction();

try {
    // Get all bookings for this show
    $bookings_query = "SELECT * FROM booking WHERE s_id = ?";
    $stmt = $conn->prepare($bookings_query);
    $stmt->bind_param("i", $s_id);
    $stmt->execute();
    $bookings = $stmt->get_result()->fetch_all();
    
    // Refund each user
    foreach ($bookings as $booking) {
        // Add refund to wallet
        $refund_query = "UPDATE balance SET current_balance = current_balance + ? 
                         WHERE u_id = ?";
        $refund_stmt = $conn->prepare($refund_query);
        $refund_stmt->bind_param("ds", $booking['total_amount'], $booking['u_id']);
        $refund_stmt->execute();
        
        // Notify user
        $notify_query = "INSERT INTO user_notification (u_id, message, notif_type)
                         VALUES (?, 'Show cancelled. Refund processed.', 'cancellation')";
        $notify_stmt = $conn->prepare($notify_query);
        $notify_stmt->bind_param("s", $booking['u_id']);
        $notify_stmt->execute();
    }
    
    // Delete the show
    $delete_query = "DELETE FROM show_schedule WHERE s_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $s_id);
    $delete_stmt->execute();
    
    // Commit transaction (save all changes)
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'users_refunded' => count($bookings),
        'total_refunded' => array_sum(array_column($bookings, 'total_amount'))
    ]);
    
} catch (Exception $e) {
    // Rollback on error (undo changes)
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
```

---

## Chapter 8: Manager Analytics

### Overview
Managers see revenue, ticket sales, and earnings analytics.

### Key Metrics:

**1. Tickets Sold This Week**
```sql
SELECT COUNT(*) as tickets_sold
FROM booking b
JOIN show_schedule s ON b.s_id = s.s_id
WHERE s.t_id = ? AND b.booking_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
```

**2. Total Revenue per Movie**
```sql
SELECT 
    m.mov_name,
    COUNT(b.book_id) as tickets_sold,
    SUM(b.total_amount) as total_revenue
FROM booking b
JOIN show_schedule s ON b.s_id = s.s_id
JOIN movie m ON s.mov_id = m.mov_id
WHERE s.t_id = ?
GROUP BY m.mov_id
ORDER BY total_revenue DESC
```

**3. Monthly Income Report**
```sql
SELECT
    SUM(b.total_amount) as booking_revenue,
    SUM(fo.total_price) as food_sales,
    SUM(e.cost) as total_expenses
FROM booking b
LEFT JOIN food_order fo ON MONTH(fo.order_date) = MONTH(NOW())
LEFT JOIN expense e ON MONTH(e.ex_date) = MONTH(NOW())
WHERE MONTH(b.booking_date) = MONTH(NOW()) AND b.t_id = ?
```

---

## Chapter 9: Manager - Complaint Management

### Overview
Users submit complaints, managers respond to them.

### Flow:

**1. User Submits Complaint**
```php
// complaint-handler.php
$query = "INSERT INTO complaint (u_id, t_id, complaint_text, created_at)
          VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $u_id, $t_id, $complaint_text);
$stmt->execute();
```

**2. Manager Views and Updates Status**
```javascript
function updateComplaintStatus(complaintId) {
    const status = document.getElementById('status_' + complaintId).value;
    
    const formData = new FormData();
    formData.append('action', 'update_complaint_status');
    formData.append('complaint_id', complaintId);
    formData.append('status', status);
    
    fetch('update_complaint_status_handler.php', {
        method: 'POST',
        body: formData
    }).then(response => response.json())
      .then(data => {
          if (data.status === 'success') {
              alert('Status updated to: ' + status);
          }
      });
}
```

**3. Manager Sends Message**
```javascript
function sendMessageToUser(userId, complaintId) {
    const message = prompt('Enter your message:');
    if (!message) return;
    
    const formData = new FormData();
    formData.append('action', 'send_complaint_message');
    formData.append('u_id', userId);
    formData.append('complaint_id', complaintId);
    formData.append('message', message);
    
    fetch('send_complaint_message_handler.php', {
        method: 'POST',
        body: formData
    }).then(response => response.json())
      .then(data => {
          if (data.status === 'success') {
              alert('Message sent!');
          }
      });
}
```

---

## Chapter 10: Manager Profile Settings

### Overview
Manager can update their name, contact, password.

### Code:

**JavaScript (manager-dashboard.php):**
```javascript
function openEditManagerModal() {
    document.getElementById('editManagerModal').classList.add('active');
}

function submitEditManager(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('editManagerForm'));
    formData.append('action', 'edit_manager_profile');
    
    fetch('manager_profile_handler.php', {
        method: 'POST',
        body: formData
    }).then(response => response.json())
      .then(data => {
          if (data.status === 'success') {
              alert('Profile updated!');
              location.reload();
          }
      });
}
```

**PHP Handler (manager_profile_handler.php):**
```php
<?php
$m_id = $_SESSION['m_id'];
$name = $_POST['name'];
$contact = $_POST['contact'];
$password = $_POST['password'] ?? '';

if (!empty($password)) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $query = "UPDATE manager SET manager_name = ?, contact = ?, password = ?
              WHERE m_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $name, $contact, $hashed, $m_id);
} else {
    $query = "UPDATE manager SET manager_name = ?, contact = ? WHERE m_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $name, $contact, $m_id);
}

$stmt->execute();
echo json_encode(['status' => 'success']);
?>
```

---

## Chapter 11: Admin System

### Overview
Admins manage managers, developers, and system settings. Access through developers-login.php.

### Admin Functions:

**1. Manage Managers**
```sql
-- View all managers
SELECT m.*, t.theatre_name
FROM manager m
JOIN theatre t ON m.t_id = t.t_id
ORDER BY m.manager_name

-- Add new manager
INSERT INTO manager (m_id, manager_name, contact, t_id, password)
VALUES (?, ?, ?, ?, ?)

-- Remove manager
DELETE FROM manager WHERE m_id = ?
```

**2. View System Statistics**
```sql
-- Total users
SELECT COUNT(*) as total_users FROM user

-- Total bookings
SELECT COUNT(*) as total_bookings FROM booking

-- Total revenue
SELECT SUM(total_amount) as total_revenue FROM booking

-- Active theatres
SELECT COUNT(*) as active_theatres FROM theatre
```

**3. Manage Complaints**
```sql
-- All complaints in system
SELECT c.*, u.name as user_name, t.theatre_name
FROM complaint c
LEFT JOIN user u ON c.u_id = u.u_id
LEFT JOIN theatre t ON c.t_id = t.t_id
ORDER BY c.created_at DESC
```

---

# SUMMARY

## Key Takeaways:

### HTML
- Structure of pages with tags like `<div>`, `<form>`, `<table>`, `<button>`
- Modal popups are just `<div>` hidden with CSS, shown with JavaScript

### CSS
- Makes things look good with colors, sizes, positioning
- `.modal.active` makes modals visible
- `@media` queries make responsive design

### JavaScript
- Makes things interactive: click → action
- `fetch()` sends data to PHP without page reload
- Handles form submission and modal opening/closing

### PHP
- Runs on server (users don't see the code)
- Connects to database with `$conn`
- Uses `$_POST`, `$_GET`, `$_SESSION` for data
- Returns JSON to JavaScript

### SQL
- `SELECT` gets data
- `JOIN` combines data from multiple tables
- `WHERE` filters data
- `INSERT`, `UPDATE`, `DELETE` modify data

### Flow in ShowFlow:
```
1. User interacts with HTML (click, type, submit)
2. JavaScript handles the event
3. JavaScript sends data to PHP file with fetch()
4. PHP processes data, talks to database
5. PHP returns JSON response
6. JavaScript receives response and updates HTML
```

---

# NEXT STEPS TO LEARN

1. **Try modifying code:** Change colors in CSS, add new input fields in HTML
2. **Debug with browser console:** Press F12 → Console to see JavaScript errors
3. **Use MySQL GUI:** See database structure and test queries
4. **Read actual code:** Compare this guide with actual files in ShowFlow
5. **Experiment:** Break things, fix them, learn from mistakes

Good luck! 🎉
