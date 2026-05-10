## **1\. Project Overview: ShowFlow**

**ShowFlow** is a comprehensive cinema management system designed to bridge the gap between multiplex/single-screen hall owners and moviegoers. It handles everything from ticket booking and seat management to cafeteria tokens and financial profit calculation.

##  **Project Objectives**

The core goals of **ShowFlow** are to modernize and automate theater operations through the following objectives:

* **Unified Ticketing:** Provide a synchronized system for both online and offline (counter) booking using a single real-time database.  
* **Show & Seat Management:** Offer flexible tools to schedule showtimes and dynamically manage seating layouts in the hall.  
* **Concession Digitalization:** Implement a digital food menu and token-based system for efficient online and over-the-counter snacks ordering.  
* **Audience Engagement:** Capture user reviews and ratings to help owners understand audience sentiment and film performance.  
* **Workforce Oversight:** Streamline employee management by distributing tasks and tracking work performance across departments.

**Financial Intelligence:** Automate film-specific earning calculations and operational cost tracking to provide clear profitability insights.

Structure:  
Hierarchy: Developer-\>Manger-\>User

**Section 1: Developer Control Module (Super Admin)**

**Role:** System Architect / Super Admin

**Access Credentials:** \* **User ID:** `admin`

* **Password:** `1234`

---

#### **1.1 Authentication & Landing**

* **Login Logic:** Secure entry via a dedicated login page. Upon successful authentication with hardcoded credentials, redirect to a dashboard featuring high-level administrative options organized in a grid of rectangular action boxes.

#### **1.2 Database Schema: Core Infrastructure**

* **Table: `developer`**  
  * `d_id` (PK, Auto-increment)  
  * `password` (String)  
* **Table: `Theatre`**  
  * `t_id` (PK, Auto-increment: 1, 2, 3...)  
  * `theatre_name` (String, Unique)  
  * `location` (String)  
* **Table: `Manager`**  
  * `m_id` (PK, String/User\_ID)  
  * `name` (String)  
  * `contact` (String, Unique)  
  * `password` (String)  
  * `t_id` (FK, References `Theatre.t_id`)

---

#### **1.3 Functional Operations**

**Option A: Add Theatre** \* **Interface:** Modal pop-up form.

* **Inputs:** `Theatre Name`, `Location`.  
* **Automation:** Upon submission, the system automatically generates a unique t\_id and stores the record in the `Theatre` table.

**Option B: Create Manager** \* **Interface:** Modal pop-up form.

* **Inputs:** \* `Manager User ID` (Unique ID for login)  
  * `Manager Name`  
  * `Theatre Name` (Dynamic dropdown fetched from `Theatre` table)  
  * `Contact` (Unique phone/email)  
  * `Password`  
* **Logic:** Maps the manager to the selected theater using `t_id` as the Foreign Key.

**Option C: Replace Manager** \* **Interface:** Data update form.

* **Inputs:** \* `Select Theatre` (Dropdown menu of existing locations)  
  * `New Manager Name`  
  * `New Manager User_id`  
  * `New Password`  
* **Logic:** Executes an `UPDATE` query on the `Manager` table for the specific `t_id` to swap administrative control while maintaining theater history.

### **Section 2: Manager Operations Module**

**Role:** Theater Manager

**Access Credentials:** Validated against the `Manager` table (`m_id` and `password`).

---

#### **2.1 Authentication & Profile Management**

* **Login Logic:** Matches `m_id` and `password`. On success, the session stores the manager’s `t_id`.  
* **Header UI:** A **Profile Button** (top-right).  
  * **Action:** Opens a pop-up showing Name, Contact, and `t_id`.  
  * **Features:** Change Password and **Logout**.

  ---

  #### **2.2 Infrastructure & Content Management**

**Option: Add Hall**

* **Inputs:** `Hall Name`, `Total Row`, `Total Column`.  
* **Database:** `Hall (t_id, h_id (PK), hall_name, row, col)`.  
* **Logic:** `t_id` is pulled from the session.

**Option: Add Movie**

* **Inputs:** `Movie Name`, `Poster Link`, `Trailer Link`.  
* **Database:** `Movie (mov_id (PK), mov_name, mov_poster, mov_trailer)`.

**Option: Contract**

* **Inputs:** `Select Movie` (Searchable dropdown), `One-Time Right Cost`, `Percentage per Ticket`.  
* **Database:** `Contract (t_id, mov_id, price, prcnt)`.  
  ---

  #### **2.3 Financial & Inventory Tools**

**Option: Expenses**

* **Inputs:** `Date`, `Expense Reason`, `Cost`.  
* **Database:** `Expenses (t_id, ex_id (PK), date, reason, cost)`.

**Option: Movie Earning (The Analyzer)**

* **Interface:** Searchable dropdown for movies.  
* **Calculated Display:**  
  * **Total Tickets Sold:** `COUNT` from the `Booking` table where `mov_id` and `t_id` match.  
  * **Total Earnings:** `SUM(amount)` from `Booking` for that movie/theater.  
  * **Distributor Fee:** `(Total Earnings * percent / 100)`.  
  * **One-Time Cost:** `Contract.price`.  
  * **Net:** `Total Earning - Distributor Fee - One-Time Cost`.

**Option: Food Management**

* **Insert:** Form for `Food Name` and `Price` → `food_item(food_id, t_id, food_name, price)`.  
* **Delete:** Dropdown showing only food where `t_id` matches the session.  
  ---

  #### **2.4 Scheduling & Pricing (Critical Missing Piece)**

**Option: Create Show**

* **Inputs:** \* `Select Movie` (Searchable dropdown)  
  * `Select Hall` (Dropdown filtered by `t_id`)  
  * `Select Show Date` & `Time`  
  * **Ticket Price** (Required for the `Booking` table to calculate "Total Earning")  
* **Database:** `Show (show_id (PK), mov_id, t_id, hall_id, date, time, price)`.

**Option: Seat Status (View Only)**

* **Interface:** Select a **Show** to see the 2D grid (`row` x `col`).  
* **Logic:** Cross-reference the `Booking` table to show which seats are currently occupied for that specific showtime.


### **Section 3: Public User & Booking Module**

**Role:** Public Visitor / Registered Customer

**Access:** Navigation is open to all; Authentication (Login/Register) is triggered only for Review and Booking actions.

---

#### **3.1 Landing Page (Index)**

* **Header:** \* Left: Logo and "ShowFlow" title.  
  * Right: Dynamic Toggle — If not logged in, show **Login** button. If logged in, show **Profile** button.  
* **Navigation Menu (Fixed):** Options: Now in Theatre, All Shows, Theatre Facilities.  
* **Current Showings (Body Content):**  
  * **Logic:** Query the Show table where date $\\ge$ current\_date.  
  * **Display:** A table list showing: Movie Poster | Movie Name | Average Rating.  
  * **Rating Logic:** Calculate the average from the Review table matching the mov\_id.  
  * **Action:** Clicking the **Movie Name** redirects to the Template Movie Details Page.

---

#### **3.2 Movie Details Page (Template)**

* **UI Layout:** IMDB-style layout featuring the mov\_poster, mov\_name, avg\_rating, and synopsis.  
* **Action Bar:** \* **Add Review Button:** Triggers login check.  
  * **Buy Tickets Button:** Redirects to the scheduling section.  
* **Theater Availability Table:**  
  * **Logic:** 1\. Filter Show table by mov\_id and date $\\ge$ now.  
    2\. GROUP BY t\_id.  
    3\. JOIN with the Theatre table to get Name and Location.  
    4\. Find MIN(price) for that specific movie at that theater.  
  * **Display:** Theatre Name | Location | Minimum Price.

---

#### **3.3 User Authentication (Pop-up/Modal)**

* **Login Interface:**  
  * Inputs: user\_id, password.  
  * Buttons: Submit and Create an Account.  
* **Logic:** Verification against the User table. If the user was trying to act (like Reviewing) before logging in, the system should redirect them back to that action upon success.

---

#### **3.4 Review System**

* **Logic:** Check if the user is logged in. If yes, open the Review Pop-up.  
* **Interface:** \* Movie Name (Read-only)  
  * Review Text Area / Star Rating  
* **Database:** Review (movie\_id, user\_id, review\_text, rating\_value).

---

#### **3.5 Database Additions for Section 3**

* **Table: User**  
  * user\_id (PK)  
  * name  
  * password  
  * contact  
* **Table: Review**  
  * rev\_id (PK, Auto-increment)  
  * movie\_id (FK)  
  * user\_id (FK)  
  * comment  
  * rating (Int)  
    

    ### **Section 4: Ticket Transaction & Seat Management**

This module handles the core business logic, ensuring users can only book available seats for specific showtimes through a dynamic interface.

---

#### **4.1 Access Control & Initialization**

* **Authentication Check:** Before rendering the page, the system verifies the user session. If the user is not logged in, they are redirected to the Login/Register pop-up.  
* **Smart Initialization:** If the user arrives via a specific "Buy Tickets" button from the **Movie Details Page**, the `Movie Selection` dropdown is pre-filled with that movie ID.

  ---

  #### **4.2 Booking Workflow (Cascading Selection)**

The page uses a sequential selection process to narrow down the specific showtime:

1. **Select Movie:** Dropdown with a search bar (Defaults to the previously selected movie).  
2. **Select Theatre:** Dropdown with a search bar (Only shows theaters currently screening the selected movie).  
3. **Select Hall:** Dropdown filtered by the chosen theater.  
4. **Select Date:** Date picker filtered by dates available in the `Show` table for that movie/theatre.  
5. **Select Show:** Dropdown listing specific times (e.g., 02:00 PM, 06:30 PM) for the selected date/hall.

   ---

   #### **4.3 Interactive Seat Map (The 2D Grid)**

Once a specific `show_id` is identified, the system renders the seating layout:

* **Logic:** Fetches `row` and `col` counts from the `Hall` table for the selected `h_id`.  
* **Interface:** A 2D array of buttons (Grid Layout).  
  * **State \- Available (White/Green):** User can click to select.  
  * **State \- Selected (Yellow):** Current selection before confirmation.  
  * **State \- Booked (Red/Disabled):** These IDs are fetched from the `Booking` table for that specific `show_id`.  
* **Seat ID Logic:** Seats are identified by coordinates (e.g., Row A, Column 5 \-\> `A5`).

  ---

  #### **4.4 Finalization & Database Entry**

* **Action:** User clicks the **Confirm Booking** button.  
* **Database Operation:**  
  * **Table: `Booking`**  
    * `book_id` (PK, Auto-increment)  
    * `user_id` (FK)  
    * `show_id` (FK)  
    * `seat_numbers` (String or separate Mapping Table)  
    * `total_price` (Calculated: `Show.price` \* `Number of Seats`)  
    * `booking_date` (Current Timestamp)  
* **Post-Action:** Display a "Success" message with a digital ticket/summary and redirect the user to their Profile/History page.

  ---

  ### **Database Schema: Booking Record**

**Table: `Booking`**

* `book_id` (PK)  
* `u_id` (FK from User)  
* `s_id` (FK from Show)  
* `seat_names` (Text \- e.g., "A1, A2")  
* `total_amount` (Decimal)  
* `status` (String \- e.g., 'Confirmed')


  ### **Section 5: Theatre Facilities Module**

This page allows logged-in users to interact with specific cinema locations for ancillary services like concessions and feedback.

---

#### **5.1 Initialization & Access Control**

* **Security:** This page is restricted to registered users. If not logged in, the user is redirected to the Login pop-up.  
* **Selection Logic:** A global dropdown with a search bar allows the user to select a specific **Theatre**. All subsequent actions (Food & Complaints) are mapped to the selected `t_id`.  
  ---

  #### **5.2 Digital Cafeteria (Order Food)**

* **Interface:** Clicking "Order Food" opens a modal pop-up.  
* **Display:** A list of available items fetched from the `food_item` table where `t_id` matches the selection.  
* **Inputs:** \* Checkboxes/Select for the food item.  
  * Numeric input for `Quantity`.  
* **Action:** A "Confirm Order" button that calculates the total cost and generates a digital token.  
* **Database: `food_order` (New Tracking Table)**  
  * `order_id` (PK, Auto-increment)  
  * `t_id` (FK)  
  * `user_id` (FK)  
  * `food_id` (FK)  
  * `quantity` (Int)  
  * `total_price` (Quantity × `food_item.price`)  
  * `order_date` (Current Timestamp)  
  * `status` (String: 'Pending', 'Served')

  ---

  #### **5.3 Feedback System (Complain Box)**

* **Interface:** Clicking "Give Complain" opens a text-based pop-up.  
* **Inputs:**  
  * Subject (Dropdown or Text).  
  * Complaint Message (Text area).  
* **Logic:** The system automatically captures the `user_id`, the selected `t_id`, and the current system `date`.  
* **Database: `complain`**  
  * `comp_id` (PK, Auto-increment)  
  * `t_id` (FK)  
  * `user_id` (FK)  
  * `date` (Date)  
  * `complain` (Text)

  ---

  ### **Section 6: User Profile (Summary Page)**

As mentioned in the Header section, once a user is logged in, they can access their **Profile Page** to view their activity history.

* **UI Components:**  
  * **User Details:** Name, Contact Info, and "Change Password" option.  
  * **Booking History:** A table showing `Movie Name`, `Theatre`, `Show Time`, and `Seat Numbers`.  
  * **Food Orders:** A list of ordered snacks and their current `status` (so the user knows when to pick up their food).  
  * **Logout Button:** Clears session data and redirects to the Landing Page

  ### **One Last Detail: The "All Shows" Page**

In your Landing Page menu, you listed **"All Shows"**.

* **The Logic:** This should be a master list/table of every movie playing across **all** theaters in the system.  
* **Table Columns:** `Movie Name` | `Theater Name` | `Location` | `Time`.  
* **Feature:** A filter by "City" or "Movie Title" would make this the most used page for a general customer.


  

