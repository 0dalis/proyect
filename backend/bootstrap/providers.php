<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\SuperadminPanelProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AppServiceProvider::class,
    SuperadminPanelProvider::class,
    TelescopeServiceProvider::class,
];
