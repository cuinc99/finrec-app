<?php

namespace App\Filament\Pages;

use Illuminate\Contracts\Support\Htmlable;
use ShuvroRoy\FilamentSpatieLaravelBackup\Pages\Backups as BaseBackups;

class Backups extends BaseBackups
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    public function getHeading(): string | Htmlable
    {
        return 'Backup Aplikasi';
    }

    public static function getNavigationLabel(): string
    {
        return 'Backup';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->role->isAdmin();
    }
}