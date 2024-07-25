<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Tasks\Backup\BackupJobFactory;

class BackupController extends Controller
{
    public function download()
    {
        // Ensure the backup directory exists
        $backupPath = storage_path('app/laravel-backup');

        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        // Run the backup
        $backupJob = BackupJobFactory::createFromArray(config('backup'));
        $backupJob->run();

        // Find the latest backup file
        $disk = Storage::disk(config('backup.backup.destination.disks')[0]);
        $files = $disk->files(config('backup.backup.name'));
        $lastBackupFile = end($files);

        // Return the file as a response to download
        return response()->download(storage_path('app/' . $lastBackupFile));
    }
}
