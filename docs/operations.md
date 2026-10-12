# ExTrack operating procedures

## Database backups

Run a backup manually with:

```powershell
php artisan database:backup
```

Backups are written to `storage/app/private/backups` by default. The scheduled command keeps seven days and removes older SQL dumps. Set `DB_DUMP_BINARY` in `.env` if `mysqldump` is not on the server's PATH. You can preview the destination with `php artisan database:backup --dry-run`.

Laravel's scheduler runs the backup at 11:50 PM in the application's configured timezone. The server must call `php artisan schedule:run` every minute. On Windows, create a Task Scheduler task that runs `php.exe` with arguments `artisan schedule:run` and the project folder as its working directory. On Linux, add Laravel's standard once-per-minute scheduler cron entry.

To restore, stop ExTrack writes, then import the SQL file with a MySQL/MariaDB client account that can create tables. The dump contains the database name and schema. Keep backups outside the public web root, restrict access to them, copy them to a separate device or cloud location, and periodically verify a restore on a separate database.

## Daily cash close

At the end of each business day, an owner opens **Daily Closing**, selects the date, counts the cash drawer and GCash balance, and records the opening float and any cash paid-outs. The system compares those counts with completed POS sales and locks the close record. Explain any difference in the notes before saving.

## Stock receiving and corrections

Record every delivery or stock correction in **Inventory** with a reason. Incoming stock requires a supplier, expiry date when applicable, and purchase cost per unit. For spoiled or missing goods, record stock-out with a reason. Inventory movements, batch changes, and sales cannot be edited or deleted after saving; make a correcting movement with a clear explanation instead.

## Transaction corrections

An owner can void a sale from **Transaction History** and must provide a reason. The system restores quantities to their original batches when that allocation is available and records the reversal in Inventory. Voided sales stay visible in history and are excluded from sales reports.
