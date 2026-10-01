<?php

namespace App\Filament\Pages;

use App\Http\Middleware\RequireSuperAdminTwoFactor;
use App\Models\SuperAdmin;
use App\Models\SuperAdminAuditLog;
use App\Models\SystemSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * @property-read Schema $form
 */
class PlatformSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Ajustes';

    protected static ?string $title = 'Ajustes de la plataforma';

    protected static ?int $navigationSort = 3;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'trial_days' => SystemSetting::get('trial_days', config('tenancy.default_trial_days')),
            'mfa_required' => RequireSuperAdminTwoFactor::isRequired(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $withMfa = SuperAdmin::query()->whereNotNull('app_authentication_secret')->count();
        $total = SuperAdmin::query()->count();

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Periodo de prueba')
                    ->description('Se aplica a los planes que no tienen días de prueba propios.')
                    ->schema([
                        TextInput::make('trial_days')->label('Días de prueba globales')->integer()->minValue(0)->maxValue(365)->required(),
                    ]),
                Section::make('Verificación en dos pasos')
                    ->description("Cada Super Admin la activa en su Perfil con una app de autenticación. {$withMfa} de {$total} ya la tienen.")
                    ->schema([
                        Toggle::make('mfa_required')
                            ->label('Exigirla a todos los Super Admins')
                            ->helperText('Quien no la tenga solo podrá entrar a su Perfil hasta configurarla. Para encenderla, primero actívala en tu Perfil.'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([Action::make('save')->label('Guardar')->submit('save')]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $mfaBefore = RequireSuperAdminTwoFactor::isRequired();
        $mfaAfter = (bool) $data['mfa_required'];

        // Que quien la enciende no se quede fuera de su propio panel
        if ($mfaAfter && ! $mfaBefore && ! Auth::guard('super_admin')->user()->hasTwoFactor()) {
            Notification::make()
                ->title('Primero activa tu verificación en dos pasos')
                ->body('Ve a tu Perfil → Autenticación con app. Después podrás exigirla a todos.')
                ->danger()
                ->send();

            return;
        }

        $before = (int) SystemSetting::get('trial_days', config('tenancy.default_trial_days'));
        SystemSetting::put('trial_days', (int) $data['trial_days']);
        SystemSetting::put(RequireSuperAdminTwoFactor::SETTING, $mfaAfter);

        if ($before !== (int) $data['trial_days']) {
            SuperAdminAuditLog::record('settings.updated', "Días de prueba globales: {$before} → {$data['trial_days']}", properties: [
                'trial_days' => ['from' => $before, 'to' => (int) $data['trial_days']],
            ]);
        }

        if ($mfaBefore !== $mfaAfter) {
            SuperAdminAuditLog::record('settings.updated', $mfaAfter
                ? 'Hizo obligatoria la verificación en dos pasos'
                : 'Hizo opcional la verificación en dos pasos');
        }

        Notification::make()->title('Ajustes guardados')->success()->send();
    }
}
