<?php

namespace App\Filament\Resources\SuperAdmins;

use App\Filament\Resources\SuperAdmins\Pages\CreateSuperAdmin;
use App\Filament\Resources\SuperAdmins\Pages\EditSuperAdmin;
use App\Filament\Resources\SuperAdmins\Pages\ListSuperAdmins;
use App\Filament\Resources\SuperAdmins\Schemas\SuperAdminForm;
use App\Filament\Resources\SuperAdmins\Tables\SuperAdminsTable;
use App\Models\SuperAdmin;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SuperAdminResource extends Resource
{
    protected static ?string $model = SuperAdmin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $modelLabel = 'super admin';

    protected static ?string $pluralModelLabel = 'super admins';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return SuperAdminForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuperAdminsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuperAdmins::route('/'),
            'create' => CreateSuperAdmin::route('/create'),
            'edit' => EditSuperAdmin::route('/{record}/edit'),
        ];
    }
}
