# Customer ID Verification System - Implementation Guide

## Overview
A complete system for collecting and verifying customer government IDs during registration, with an admin panel for validation and customer-facing status tracking.

## Files Created/Modified

### 1. Database Schema (maata (11).sql)
Added 3 columns to `customers` table:
```sql
ALTER TABLE customers ADD COLUMN government_id_image VARCHAR(500);
ALTER TABLE customers ADD COLUMN government_id_verified TINYINT(1) DEFAULT 0;
ALTER TABLE customers ADD COLUMN id_verification_date TIMESTAMP NULL;
```

**Column Meanings:**
- `government_id_image`: Filename of uploaded government ID image
- `government_id_verified`: 0 = Pending, 1 = Verified, 2 = Rejected
- `id_verification_date`: When the ID was verified by admin

### 2. Client Registration (client/register.php)
**Changes Made:**
- Added `enctype="multipart/form-data"` to form
- Added government ID file input with validation hints
- Shows message about 24-hour verification timeframe
- Accepts JPG, PNG, GIF (max 5MB)

**File Input:**
```html
<input type="file" name="government_id_image" accept="image/*" required>
```

### 3. Registration Handler (handlers/client_register.php)
**Changes Made:**
- Added file upload validation (MIME type, size, error checking)
- Creates `assets/img/customer_ids/` directory if missing
- Generates unique filename: `govid_firstname_lastname_uniqid.ext`
- Updates INSERT to save filename and verification status (0 = unverified)
- Deletes file if database insert fails

**Validation Rules:**
- Allowed MIME types: image/jpeg, image/png, image/gif
- Max file size: 5MB
- Required field (must upload to register)

### 4. Admin Verification Panel (customer_id_verification.php) - NEW
**Features:**
- Lists all customers with pending ID verification
- Shows customer name, email, phone, registration date
- "View" button to open government ID image in modal
- "Verify" button to approve ID (marks as verified)
- "Reject" button to deny ID (sends email notification)
- Separate table for already-verified customers
- DataTables integration for sorting/filtering

**Verification Flow:**
1. Admin clicks "View" to see the government ID image
2. Admin compares image with customer details
3. Admin clicks "Verify" to approve → email sent to customer
4. OR Admin clicks "Reject" → customer must re-upload

### 5. Verification Handler (handlers/customer_id_verification.php) - NEW
**Actions:**
- `approve`: Sets `government_id_verified = 1`, records `id_verification_date`
- `reject`: Sets `government_id_verified = 2`, allows customer to re-upload

**Email Notifications:**
- Sends "Verified" email when approved
- Sends "Rejected" email with re-upload instructions when denied

### 6. Customer ID Verification Status (client/id_verification.php) - NEW
**Features:**
- Shows current verification status (Not submitted, Pending, Verified, Rejected)
- Color-coded status badge
- Upload/re-upload interface for rejected IDs
- Drag-and-drop file upload support
- Shows verification date when approved

**Status States:**
- **Not submitted**: No ID uploaded yet
- **Pending Verification**: Waiting for admin review
- **Verified**: ID has been verified by admin
- **Rejected**: ID was rejected, must re-upload

**For Rejected Cases:**
- Shows specific reasons for rejection
- Provides guidance on proper ID submission
- Allows immediate re-upload

## Integration Steps

### 1. Update Admin Menu
Add link to admin menu in your admin navigation (e.g., `partials/sidenav.php`):

```html
<li>
    <a href="customer_id_verification.php" class="nav-link">
        <i class="feather icon-check-circle"></i>
        <span>ID Verification</span>
    </a>
</li>
```

### 2. Add Link to Customer Profile
In `client/profile.php`, add a link to view ID verification status:

```html
<a href="id_verification.php" class="btn btn-sm btn-info">
    <i class="feather icon-shield"></i> ID Verification Status
</a>
```

### 3. Notify Admins of New Registrations
Consider adding a notification badge in admin dashboard showing pending IDs:

```php
<?php
$pending_count = $conn->query("SELECT COUNT(*) as count FROM customers WHERE government_id_verified = 0 AND government_id_image IS NOT NULL")->fetch_assoc()['count'];
if ($pending_count > 0) {
    echo "<span class='badge badge-warning'>$pending_count pending verifications</span>";
}
?>
```

## File Storage Structure
```
assets/
  img/
    customer_ids/
      govid_john_doe_5e8f3a9b.jpg
      govid_jane_smith_5e8f3a9c.png
      ...
```

## Security Considerations

1. **File Storage**: 
   - Images stored outside webroot in `assets/img/customer_ids/`
   - Admin panel only accessible to authenticated admins
   - Only admins can view customer ID images

2. **File Upload**:
   - MIME type validation on server
   - File size limited to 5MB
   - Unique filenames prevent collisions
   - Original filename not stored to prevent path traversal

3. **Database**:
   - Verification status flags prevent unauthorized approval
   - Timestamp tracks when verification occurred
   - File cleanup on failed registrations

4. **Manual Verification**:
   - Admin must manually compare ID with customer details
   - No automated OCR (to avoid complexity)
   - Admin can add notes or reject with reason

## Workflow Summary

### New Customer Registration
1. Customer fills registration form including government ID upload
2. File validated (type, size) on client-side (hints) and server-side
3. ID image saved with unique filename
4. Customer account created with `government_id_verified = 0`
5. Customer can use account but some features may be restricted

### Admin Verification
1. Admin visits "ID Verification" panel
2. Admin reviews list of pending verifications
3. Admin clicks "View" to see government ID image
4. Admin clicks "Verify" to approve (ID marked as verified, email sent)
   - OR Admin clicks "Reject" (email sent, customer can re-upload)

### Customer Re-upload (if Rejected)
1. Customer logs in and visits ID Verification page
2. Sees status "Rejected - Please Re-submit"
3. Uploads new/clearer ID image
4. Status reverts to "Pending Verification"
5. Admin reviews again

## Feature Flags (Optional Future Additions)

- Restrict certain features (menu orders, reservations) until ID verified
- Admin dashboard widget showing verification statistics
- Automated email reminders for pending verifications
- Bulk reject/approve with notes
- ID image retention policy (auto-delete after X days)
- OCR integration for automated name matching

## Testing Checklist

- [ ] Customer can upload government ID during registration
- [ ] File validation works (reject invalid types/sizes)
- [ ] Admin can see pending verifications list
- [ ] Admin can view government ID image in modal
- [ ] Admin can verify ID (email sent to customer)
- [ ] Admin can reject ID (email sent with re-upload instructions)
- [ ] Customer sees correct status on ID verification page
- [ ] Customer can re-upload if rejected
- [ ] Verified customers appear in "Verified" table
- [ ] Filenames include customer name for traceability
- [ ] Directory permissions allow file storage (0755)
