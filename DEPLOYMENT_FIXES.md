# Deployment Fixes

## Issue: Undefined array key warnings

### Problem
The moments table is missing the 'type' column, causing PHP warnings when trying to display moments.

### Solution
Run this SQL on your database:

```sql
-- Add type column to moments table if it doesn't exist
ALTER TABLE moments 
ADD COLUMN IF NOT EXISTS type ENUM('update', 'milestone', 'blocker', 'note') DEFAULT 'update' AFTER journey_id;

-- Update existing moments to have default type
UPDATE moments SET type = 'update' WHERE type IS NULL;
```

## Issue: Journey Pulse showing 0% for all

### Problem
The journey stats might not be calculating correctly or the pulse_status field needs updating.

### Solution
Run this SQL to update journey pulse status:

```sql
-- Update journey pulse status based on last activity
UPDATE journeys 
SET pulse_status = CASE 
    WHEN DATEDIFF(NOW(), COALESCE(last_moment_at, created_at)) > 14 THEN 'critical'
    WHEN DATEDIFF(NOW(), COALESCE(last_moment_at, created_at)) > 7 THEN 'warning'
    ELSE 'healthy'
END
WHERE status = 'active';
```

## Issue: Balance due not calculating

### Solution
Add the calculated column:

```sql
ALTER TABLE journeys 
ADD COLUMN IF NOT EXISTS balance_due DECIMAL(10,2) 
GENERATED ALWAYS AS (sale_amount - paid_amount) STORED AFTER paid_amount;
```

## Quick Fix Commands

Run all fixes at once:

```bash
mysql -u your_user -p your_database << EOF
ALTER TABLE moments ADD COLUMN IF NOT EXISTS type ENUM('update', 'milestone', 'blocker', 'note') DEFAULT 'update' AFTER journey_id;
UPDATE moments SET type = 'update' WHERE type IS NULL;
UPDATE journeys SET pulse_status = CASE 
    WHEN DATEDIFF(NOW(), COALESCE(last_moment_at, created_at)) > 14 THEN 'critical'
    WHEN DATEDIFF(NOW(), COALESCE(last_moment_at, created_at)) > 7 THEN 'warning'
    ELSE 'healthy'
END WHERE status = 'active';
EOF
```