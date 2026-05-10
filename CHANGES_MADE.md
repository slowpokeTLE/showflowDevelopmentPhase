# ShowFlow Bug Fixes and Feature Additions - Summary Report

**Date:** May 10, 2026  
**Project:** ShowFlow - Online Ticket Booking Site

---

## Files Modified

### 1. **index.php**
**Changes:**
- Changed ShowFlow title logo from cinema card emoji (🎬) to image file `showflowicon.png`
- Logo now displays as an `<img>` tag with proper styling

---

### 2. **logout.php**
**Changes:**
- Fixed redirect destination from undefined (showflow) to `index.php`
- Added explicit redirect header after logout

---

### 3. **all_movies.php**
**Changes:**
- Fixed Facilities button link from `user-facilities.php` to `facilities.php`
- Changed all rupee signs (₹) to BDT signs (৳) in the movies table earnings column

---

### 4. **user-profile.php**
**Changes:**
- Fixed logout button link to properly redirect to `logout.php`
- Changed user profile avatar from emoji (🎭) to image file `userIcon.png`
- Added new "Past Complains" tab that displays:
  - Theatre name
  - Complaint text
  - Status (Not Seen, Seen, Working, Resolved)
  - Date submitted
- Changed all rupee signs (₹) to BDT signs (৳) throughout the page
- Updated total spent calculation to display in BDT
- Wallet balance now displays in BDT

**Note:** The "Past Complains" tab requires a `status` column in the complaints table with a default value of 'Not Seen'. You may need to add this column to your database:
```sql
ALTER TABLE complaint ADD COLUMN status VARCHAR(20) DEFAULT 'Not Seen';
```

---

### 5. **recharge.php**
**Changes:**
- Changed bKash payment method icon from emoji (📱) to image file `bkash.png`
- Changed Nagad payment method icon from emoji (💰) to image file `nagad.png`
- Updated provider logos to display as images with proper styling

---

### 6. **manager-dashboard.php**
**Changes:**

#### Logo & Header:
- Changed dashboard header logo from emoji (🎬) to image file `showflowicon.png`
- Added Settings button for manager profile management

#### Dashboard Stats:
- Removed "Movies Added" stat from hero dashboard
- Replaced with "Tickets Sold (Last Week)" stat that:
  - Fetches data from booking table
  - Calculates tickets sold in the last 7 days for the theatre
  - Dynamically queries the database

#### Currency Changes:
- Changed all rupee signs (₹) to BDT signs (৳) throughout the page
- Updated in: show schedules table, food items table, expense forms, contract forms

#### Complaints Management:
- Enhanced "See Complaints" modal with:
  - New "Status" column showing current complaint status
  - Status dropdown with options: "Not Seen", "Seen", "Working", "Resolved"
  - "Update Status" button to save status changes
  - "Send Message" button to send direct messages to users about their complaints

#### New Features:
- Added "Settings" button (⚙️) in header navigation
- New "Edit Manager Profile" modal that allows managers to change:
  - Manager name
  - Contact number
  - Password (optional)
- New "Send Message" modal for sending direct messages to users

#### JavaScript Functions Added:
- `openEditManagerModal()` - Opens manager profile edit modal
- `closeEditManagerModal()` - Closes manager profile edit modal
- `submitEditManager(event)` - Submits manager profile changes
- `sendMessageToUser(userId, complaintId)` - Opens message modal
- `closeSendMessageModal()` - Closes message modal
- `submitSendMessage(event)` - Submits message to user
- `updateComplaintStatus(complaintId)` - Updates complaint status

---

## Bug Fixes Applied

### Fixed Issues:
1. **User Profile Tab Selection Not Working** ✅
   - Added missing complaints data fetching query
   - Query now uses COALESCE for safe status column handling
   - Fallback mechanism if status column doesn't exist
   - All tabs should now be selectable and functional

To fully support the new complaint management features, add the following columns if they don't exist:

```sql
-- Add status column to complaints table
ALTER TABLE complaint ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'Not Seen';

-- Add message-related columns if supporting direct messaging
ALTER TABLE complaint ADD COLUMN IF NOT EXISTS manager_notes TEXT;
ALTER TABLE complaint ADD COLUMN IF NOT EXISTS last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
```

---

## Image Files Required

Ensure the following image files exist in the project root directory:
- `showflowicon.png` - ShowFlow logo icon
- `userIcon.png` - User profile avatar
- `bkash.png` - bKash payment method logo
- `nagad.png` - Nagad payment method logo

---

## API/Handler Files Referenced (Need to be Created/Updated)

The following PHP handler files are referenced but may need to be created or updated:

1. **manager_profile_handler.php** - Handles manager profile updates
2. **send_complaint_message_handler.php** - Handles sending messages to users about complaints
3. **update_complaint_status_handler.php** - Handles updating complaint status

---

## Currency Conversion

- All prices have been changed from Indian Rupee (₹) to Bangladeshi Taka (৳)
- This affects:
  - Movie ticket pricing
  - Food item pricing
  - Wallet recharge amounts
  - User booking costs
  - Manager dashboard earnings and expenses

---

## Testing Checklist

- [ ] Test ShowFlow logo displays correctly on index.php
- [ ] Test logout redirects to index.php
- [ ] Test Facilities button navigates to facilities.php
- [ ] Test user profile avatar displays correctly
- [ ] Test Past Complains tab shows complaint data with status
- [ ] Test bKash and Nagad logos display in recharge page
- [ ] Test manager dashboard shows correct "Tickets Sold (Last Week)"
- [ ] Test complaint status updates work
- [ ] Test sending messages to users works
- [ ] Test manager profile edit functionality
- [ ] Verify all currency signs display as BDT (৳)
- [ ] Test on mobile and desktop viewports

---

## Notes

- All changes maintain backward compatibility with existing functionality
- No existing user data should be affected
- The complaint status feature is new and defaults to "Not Seen"
- The past complains tab is only accessible to logged-in users
- Manager profile changes require proper validation and authentication

