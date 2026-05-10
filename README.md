# ShowFlow: Online Movie Ticket Booking System

## PROJECT REPORT

---

## 1. TITLE PAGE

### Project Name
**ShowFlow - Online Movie Ticket Booking System**

### Group Members
- Shamim
- Pushpita
- Nabil
- Riyad

### Course Information
**Course:** CSE311L (Database Management)  
**Section:** 7  
**Semester:** Spring 2024  
**Institution:** NSU

---

## 2. INTRODUCTION

### Topic: What is the Project About?

ShowFlow is a comprehensive web-based movie ticket booking platform designed to streamline the process of browsing movies, booking tickets, and managing theatre operations. It's a full-stack application built with PHP backend, MySQL database, and HTML/CSS/JavaScript frontend, following the Netflix-inspired design theme.

The system allows users to search for movies, view show timings, select seats, and book tickets with an integrated wallet-based payment system. The platform also provides theatre managers with tools to manage movies, create shows, handle complaints, and analyze earnings, while administrators can oversee the entire system.

### Purpose: Who Uses It and Why is It Useful?

**Primary Users:**

1. **End Users (Movie Enthusiasts)**
   - Browse available movies and shows
   - Book tickets online from home
   - Manage bookings and reviews
   - Track wallet balance and recharges
   - Submit complaints and track resolution

2. **Theatre Managers**
   - Add and manage movies in their theatre
   - Create show schedules with seat configurations
   - Monitor ticket sales and revenue
   - Handle customer complaints
   - Analyze business analytics and earnings
   - Manage food items and facilities

3. **System Administrators**
   - Monitor overall system performance
   - Manage theatre managers and staff
   - View system-wide analytics
   - Handle critical system issues

**Why It's Useful:**

- **Convenience:** Users can book tickets anytime, anywhere without visiting ticket counters
- **Efficiency:** Theatre managers can automate operations and reduce manual work
- **Real-time Data:** Instant updates on seat availability and show timings
- **Complaint Management:** Systematic approach to handling customer issues
- **Revenue Analytics:** Data-driven insights for business decisions
- **Secure Transactions:** Wallet-based payment system with transaction logging

---

## 3. DATABASE DESIGN

### Database Schema (Textual Representation)

The ShowFlow database consists of 12 interconnected tables designed following relational database principles with proper normalization and referential integrity.

### Table Structures with Primary and Foreign Keys

```
TABLE: user
├── u_id (PK, VARCHAR)
├── name (VARCHAR)
├── email (VARCHAR, UNIQUE)
├── password (VARCHAR, HASHED)
├── contact (VARCHAR)
├── created_at (TIMESTAMP)
└── Indexes: email (for login queries)

TABLE: manager
├── m_id (PK, INT)
├── manager_name (VARCHAR)
├── contact (VARCHAR)
├── password (VARCHAR, HASHED)
├── t_id (FK → theatre.t_id)
└── created_at (TIMESTAMP)

TABLE: developer
├── dev_id (PK, VARCHAR)
├── dev_name (VARCHAR)
├── password (VARCHAR, HASHED)
└── dev_email (VARCHAR)

TABLE: theatre
├── t_id (PK, INT)
├── theatre_name (VARCHAR)
├── location (VARCHAR)
├── contact (VARCHAR)
└── created_at (TIMESTAMP)

TABLE: hall
├── h_id (PK, INT)
├── t_id (FK → theatre.t_id)
├── hall_name (VARCHAR)
├── total_rows (INT)
├── total_columns (INT)
└── created_at (TIMESTAMP)

TABLE: movie
├── mov_id (PK, INT)
├── mov_name (VARCHAR)
├── mov_poster (VARCHAR, URL)
├── mov_genre (VARCHAR)
├── mov_duration (INT)
├── mov_release_date (DATE)
├── mov_synopsis (TEXT)
├── mov_trailer (VARCHAR, URL)
└── created_at (TIMESTAMP)

TABLE: show_schedule
├── s_id (PK, INT)
├── mov_id (FK → movie.mov_id)
├── t_id (FK → theatre.t_id)
├── h_id (FK → hall.h_id)
├── show_date (DATE)
├── show_time (TIME)
├── ticket_price (DECIMAL)
└── created_at (TIMESTAMP)

TABLE: booking
├── book_id (PK, INT)
├── u_id (FK → user.u_id)
├── s_id (FK → show_schedule.s_id)
├── seat_numbers (VARCHAR, comma-separated)
├── total_amount (DECIMAL)
├── paid_from_wallet (BOOLEAN)
├── booking_date (TIMESTAMP)
└── status (VARCHAR)

TABLE: balance
├── bal_id (PK, INT)
├── u_id (FK → user.u_id, UNIQUE)
├── current_balance (DECIMAL)
├── last_updated (TIMESTAMP)
└── Indexes: u_id (for wallet lookups)

TABLE: recharge_history
├── r_id (PK, INT)
├── u_id (FK → user.u_id)
├── amount (DECIMAL)
├── method (VARCHAR) [bKash, Nagad]
├── date (TIMESTAMP)
└── status (VARCHAR)

TABLE: complaint
├── comp_id (PK, INT)
├── u_id (FK → user.u_id)
├── t_id (FK → theatre.t_id)
├── complaint_text (TEXT)
├── status (VARCHAR) [Not Seen, Seen, Working, Resolved]
├── manager_notes (TEXT)
├── created_at (TIMESTAMP)
└── last_updated (TIMESTAMP)

TABLE: review
├── rev_id (PK, INT)
├── mov_id (FK → movie.mov_id)
├── u_id (FK → user.u_id)
├── rating (INT, 1-5)
├── comment (TEXT)
├── created_at (TIMESTAMP)
└── UNIQUE(mov_id, u_id) [One review per user per movie]

TABLE: food_item
├── food_id (PK, INT)
├── t_id (FK → theatre.t_id)
├── food_name (VARCHAR)
├── price (DECIMAL)
└── created_at (TIMESTAMP)

TABLE: food_order
├── order_id (PK, INT)
├── u_id (FK → user.u_id)
├── t_id (FK → theatre.t_id)
├── food_id (FK → food_item.food_id)
├── total_price (DECIMAL)
├── status (VARCHAR) [Pending, Delivered]
├── order_date (TIMESTAMP)
└── paid_from_wallet (BOOLEAN)

TABLE: expense
├── ex_id (PK, INT)
├── t_id (FK → theatre.t_id)
├── ex_reason (VARCHAR)
├── cost (DECIMAL)
├── ex_date (DATE)
└── created_at (TIMESTAMP)

TABLE: user_notification
├── notif_id (PK, INT)
├── u_id (FK → user.u_id)
├── message (TEXT)
├── notif_type (VARCHAR)
├── is_read (BOOLEAN)
└── created_at (TIMESTAMP)
```

### Relational Integrity

- **Primary Keys (PK):** Each table has a unique identifier
- **Foreign Keys (FK):** Establish relationships between tables
- **Referential Integrity:** Maintains data consistency across related tables
- **Indexes:** Optimized for frequently queried columns (email, u_id)
- **Constraints:** UNIQUE constraints on email, one-review-per-user rules

---

## 4. IMPLEMENTATION OF REQUIREMENTS

### A. CRUD Operations

#### CREATE Operations
| Feature | Page | Function |
|---------|------|----------|
| Create Booking | booking.php | Users select seats and create new bookings (INSERT into booking table) |
| Create Review | index.php, all_movies.php | Users add movie reviews (INSERT into review table) |
| Create Movie | manager-dashboard.php | Managers add movies (INSERT into movie table) |
| Create Show | manager-dashboard.php | Managers create show schedules (INSERT into show_schedule table) |
| Create Complaint | complaint-handler.php | Users submit complaints (INSERT into complaint table) |
| Create Food Order | food-order-handler.php | Users order food items (INSERT into food_order table) |

#### READ Operations
| Feature | Page | Function |
|---------|------|----------|
| Browse Movies | index.php, all_movies.php | Display all available movies with details |
| View Bookings | user-profile.php | Display user's booking history (SELECT with JOINs) |
| View Reviews | index.php | Display movie reviews and ratings |
| View Shows | booking.php | Display available shows for selected movie |
| View Complaints | manager-dashboard.php | Managers view customer complaints |
| View Analytics | manager-dashboard.php | Display revenue, ticket sales, earnings |

#### UPDATE Operations
| Feature | Page | Function |
|---------|------|----------|
| Update Profile | user-profile.php | Users update name, contact, password (UPDATE user table) |
| Update Complaint Status | manager-dashboard.php | Managers change complaint status (UPDATE complaint table) |
| Update Manager Profile | manager-dashboard.php | Managers update their info (UPDATE manager table) |
| Update Wallet Balance | booking-handler.php | System updates balance after transactions (UPDATE balance table) |

#### DELETE Operations
| Feature | Page | Function |
|---------|------|----------|
| Cancel Booking | user-profile.php | Users cancel tickets with automatic refund (DELETE from booking, UPDATE balance) |
| Delete Review | user-profile.php | Users delete their reviews (DELETE from review) |
| Delete Show | manager-dashboard.php | Managers cancel shows with refunds (DELETE from show_schedule) |
| Delete Food Order | user-profile.php | Users cancel food orders (DELETE from food_order) |

### B. Authentication System

**Registration Process:**
1. User accesses user-login.php and clicks "Sign Up" tab
2. Enters email, password, name, contact number
3. PHP validates input and checks for duplicate email
4. Password is hashed using `password_hash()` with BCRYPT algorithm
5. New record inserted into `user` table
6. Success redirects to login

**Login Process:**
1. User/Manager/Admin enters credentials on login page
2. System queries appropriate table (user/manager/developer)
3. `password_verify()` compares entered password with stored hash
4. If valid:
   - Session created with `session_start()`
   - Session variables set: `$_SESSION['u_id']`, `$_SESSION['role']`, `$_SESSION['name']`
   - User redirected to appropriate dashboard
5. If invalid: Error message displayed

**Session Management:**
- Sessions stored server-side with unique session IDs
- Session data persists across page navigation
- Logout destroys session: `session_destroy()`
- Each page checks `if (isset($_SESSION['u_id']))` before allowing access

**Security Features:**
- Passwords hashed with BCRYPT (PASSWORD_DEFAULT)
- Prepared statements prevent SQL injection
- Session regeneration after login
- Role-based access control

### C. Search Features

| Search Type | Location | Functionality |
|-------------|----------|---------------|
| Movie Search | all_movies.php | Users search by movie name (LIKE %search%) |
| Sort Movies | all_movies.php | Sort by highest earnings, rating, release date |
| Show Filter | booking.php | Filter shows by theatre, date, time |
| Complaint Search | manager-dashboard.php | View complaints for specific theatre |
| User Search | developers-login.php | Admin search users by ID/email |

**Search Implementation Example (all_movies.php):**
```sql
SELECT * FROM movie 
WHERE mov_name LIKE '%{$search}%'
ORDER BY {$sort_field} DESC
```

---

## 5. INTERFACE (SCREENSHOTS DESCRIPTION)

### Page 1: Home Page (index.php)
**Key Elements:**
- Navigation bar with ShowFlow logo, navigation links, and user authentication buttons
- Hero section with welcome message and call-to-action
- Movies grid displaying currently showing movies with posters, ratings, and "Book Now" buttons
- Each movie card clickable to open modal with full details
- Responsive design adapts to mobile/desktop

**User Actions:**
- Browse movies by scrolling
- Click movie to view details and user reviews
- Click "Book Tickets" to proceed to booking
- Access wallet balance and recharge option from header

### Page 2: User Profile Dashboard (user-profile.php)
**Key Elements:**
- Left sidebar with user avatar, profile information, wallet balance
- Tab navigation: Upcoming Bookings, Past Bookings, Food Orders, Messages, Reviews, Complaints, Edit Profile
- Upcoming Bookings table showing active tickets with options to:
  - Cancel booking (triggers automatic refund)
  - Print ticket
- Wallet balance display with recharge button
- Statistics: Total bookings, upcoming shows, reviews submitted

**User Actions:**
- Switch between different tabs to view different information
- Cancel upcoming bookings with confirmation
- Edit profile information
- Recharge wallet
- View past complaints and their status

### Page 3: Movie Booking Page (booking.php)
**Key Elements:**
- Movie selection dropdown
- Theatre selection
- Show schedule table with available times and prices
- Seat selection grid showing available (green) and booked (red) seats
- Selected seats list with total price calculation
- "Confirm Booking" button
- Payment method selection (Wallet or Cash)

**User Actions:**
- Select movie from dropdown
- Choose theatre and show timing
- Click seats to select (togglable)
- View real-time price calculation
- Confirm booking with payment method selection

### Page 4: Manager Dashboard (manager-dashboard.php)
**Key Elements:**
- Dashboard stats showing: Tickets sold this week, halls, upcoming shows, food items
- Action cards for: Add Movie, Edit Movie, Add Hall, Create Show, Earnings, Food Items, Complaints
- Halls table with edit/delete options
- Upcoming shows table with edit/delete options
- Food menu table
- Complaints modal with status update and messaging features

**Manager Actions:**
- Add new movies with poster and details
- Create show schedules
- Update show timing/price
- View and respond to customer complaints
- Send direct messages to customers
- Analyze earnings and revenue
- Manage manager profile (name, contact, password)

---

## 6. CONCLUSION

### Best Features of ShowFlow

1. **Comprehensive Wallet System**
   - Integrated payment system without external gateway dependency
   - Automatic refunds on booking cancellation with transaction logging
   - Recharge history tracking for users

2. **Advanced Complaint Management**
   - Status tracking (Not Seen → Seen → Working → Resolved)
   - Direct messaging between managers and users
   - Manager notes for complaint resolution documentation

3. **Real-time Analytics**
   - Dashboard showing tickets sold in last week
   - Monthly income reports with breakdown
   - Revenue analytics per movie per theatre
   - Expense tracking and profit calculation

4. **Flexible Seat Selection**
   - Dynamic seat grid based on hall configuration
   - Real-time seat availability updates
   - Multi-seat booking with automatic price calculation

5. **User Review System**
   - Rating and review on movies
   - One review per user per movie (prevents duplicate reviews)
   - Average rating calculation and display

6. **Netflix-Inspired Design**
   - Modern dark theme with red accents
   - Responsive design for all devices
   - Smooth modal animations and transitions
   - Professional UI/UX

### Challenges Faced

1. **Session Management Across Pages**
   - Challenge: Maintaining user authentication across multiple pages
   - Solution: Implemented `session_start()` on all pages with proper role-based checks

2. **Complex Database Queries with Multiple JOINs**
   - Challenge: Fetching booking data required joining 5+ tables
   - Solution: Optimized queries with proper indexing on foreign keys

3. **Preventing SQL Injection**
   - Challenge: User inputs could contain malicious SQL
   - Solution: Used prepared statements with `bind_param()` throughout application

4. **Real-time Seat Availability**
   - Challenge: Race condition when multiple users book same seats simultaneously
   - Solution: Implemented database transactions (BEGIN, COMMIT, ROLLBACK)

5. **Automatic Refund System**
   - Challenge: Ensuring refunds process correctly on show cancellation
   - Solution: Implemented transaction-based refund with notification system

6. **Cross-page Communication Without Page Reload**
   - Challenge: Update data dynamically without refreshing
   - Solution: Implemented AJAX with JavaScript `fetch()` API to communicate with PHP handlers

7. **Modal System for Complex Forms**
   - Challenge: Multiple overlapping forms and validations
   - Solution: Created reusable modal system with JavaScript show/hide functionality

8. **Dynamic Favicon and Logo Integration**
   - Challenge: Coordinating image references across multiple pages and folders
   - Solution: Added favicon links to all pages with relative path references

### Project Impact

ShowFlow successfully demonstrates a full-stack web application implementing:
- ✅ Relational database design with proper normalization
- ✅ Secure authentication and session management
- ✅ CRUD operations across all entities
- ✅ Complex business logic (refunds, transactions, complaints)
- ✅ Responsive user interface
- ✅ Role-based access control
- ✅ Real-time data processing

This project can be extended with:
- Payment gateway integration (Stripe, PayPal)
- SMS notifications
- Email confirmations
- Mobile app development
- Advanced analytics dashboard
- Machine learning for recommendations

---

## TECHNICAL STACK

| Component | Technology |
|-----------|-----------|
| Backend | PHP 7.4+ |
| Database | MySQL 5.7+ |
| Frontend | HTML5, CSS3, JavaScript (Vanilla) |
| Design Pattern | MVC (Model-View-Controller) |
| Architecture | 3-Tier Architecture |
| Security | Password Hashing (BCRYPT), Prepared Statements, Sessions |
| Responsive | CSS Flexbox, Grid, Media Queries |

---

## DEPLOYMENT NOTES

**Local Environment:**
- Apache Web Server (XAMPP)
- PHP with MySQL support
- Directory: `d:\WP\xampp\htdocs\showflow2\`

**Production Deployment:**
- Update `BASE_URL` in constants.php (dynamic detection implemented)
- Ensure image files (showflowicon.png, bkash.png, nagad.png, userIcon.png) are in root directory
- Create required database tables using provided schema
- Configure database credentials in `constants.php`
- Enable HTTPS for secure password transmission
- Set up automated backups for database

---

**Report Prepared:** [Date]  
**Project Duration:** [Duration]  
**Total Development Time:** [Hours]

