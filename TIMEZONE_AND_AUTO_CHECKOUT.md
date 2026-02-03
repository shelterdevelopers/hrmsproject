# Timezone and Auto Checkout Configuration

## Timezone Setup

The HRMS system is configured to use **Africa/Harare** timezone (Harare, Zimbabwe) for all date and time operations.

### Configuration Location
- **Primary Configuration**: `config.php` - Sets `APP_TIMEZONE` to `'Africa/Harare'`
- **Database Connection**: `DB_connection.php` - Ensures timezone is set before database operations
- **Attendance Model**: `app/Model/Attendance.php` - All check-in/check-out operations use Harare timezone

### How It Works
1. The timezone is set in `config.php` when the application loads
2. All PHP `date()` functions use the Harare timezone
3. All timestamps in the database are stored in Harare time
4. All displayed times are shown in Harare timezone

## Automatic Checkout at 5:00 PM

The system automatically checks out all employees at **5:00 PM Harare time** if they haven't manually checked out.

### How It Works

**Method 1: On Page Load (Automatic)**
- Every time a user loads any page (dashboard, attendance, etc.), the system checks:
  - If current time is 5:00 PM or later (Harare time)
  - If employee is checked in but not checked out
  - Automatically checks them out at 5:00 PM
- This ensures employees are always checked out even if they forget

**Method 2: Cron Job (Recommended for Production)**
- A cron job can be set up to run the auto-checkout script at 5:00 PM daily
- Script location: `cron/auto_checkout.php`
- This is more efficient for high-traffic systems

### Setup Instructions

#### Option 1: Automatic (Already Active)
The system automatically checks out employees on page load. No additional setup needed.

#### Option 2: Cron Job Setup (Recommended)
Add to your server's crontab to run at 5:00 PM Harare time daily:

```bash
# Edit crontab
crontab -e

# Add this line (adjust path to your installation)
0 17 * * * php /path/to/hrms/cron/auto_checkout.php >> /var/log/hrms_autocheckout.log 2>&1
```

**Note**: The cron job time (17:00) should match your server's timezone. If your server is in a different timezone, adjust accordingly:
- Server in UTC: `0 15 * * *` (3:00 PM UTC = 5:00 PM Harare, during standard time)
- Server in Harare: `0 17 * * *` (5:00 PM Harare)

### What Happens When Auto-Checkout Runs

1. **Finds Employees**: All active employees who:
   - Are checked in today
   - Have NOT manually checked out
   - Have a check-in time recorded

2. **Checks Them Out**: Sets checkout time to exactly **17:00:00** (5:00 PM)

3. **Marks as Auto-Checked**: Sets `auto_checked_out = TRUE` in the database

4. **Sends Notification**: Employee receives a notification:
   - "You were automatically checked out at 5:00 PM (Harare time) today."

5. **Logs Activity**: Activity is logged in the system (if ActivityLog is available)

### Database Field

The `attendance` table has an `auto_checked_out` boolean field:
- `TRUE`: Employee was automatically checked out at 5:00 PM
- `FALSE` or `NULL`: Employee manually checked out

### Manual Checkout Still Works

Employees can still manually check out before 5:00 PM:
- If they check out manually, they won't be auto-checked out
- Manual checkout time is recorded as the actual time they checked out
- Auto-checkout only affects employees who haven't checked out by 5:00 PM

## Testing

To test auto-checkout:
1. Check in as an employee
2. Wait until 5:00 PM Harare time (or temporarily modify the time check in code)
3. Load any page in the system
4. Check attendance - you should see checkout time as 17:00:00
5. Check notifications - you should see the auto-checkout notification

## Troubleshooting

### Auto-checkout not working?
1. **Check timezone**: Verify `config.php` has `APP_TIMEZONE` set to `'Africa/Harare'`
2. **Check server time**: Ensure server time matches Harare timezone
3. **Check database**: Verify `auto_checked_out` column exists in `attendance` table
4. **Check logs**: Look for errors in PHP error logs

### Timezone issues?
1. All times should display in Harare timezone
2. If times appear incorrect, check:
   - `config.php` timezone setting
   - Server timezone configuration
   - Database timezone settings (MySQL should use system timezone)

## Notes

- **Knock-off Time**: 5:00 PM (17:00) Harare time is the standard knock-off time
- **Timezone**: All operations use Africa/Harare timezone (CAT - Central Africa Time, UTC+2)
- **Daylight Saving**: Harare does not observe daylight saving time, so timezone remains constant year-round

**Last Updated**: January 2026
