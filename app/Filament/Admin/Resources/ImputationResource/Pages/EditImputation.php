<?php

namespace App\Filament\Admin\Resources\ImputationResource\Pages;

use App\Filament\Admin\Resources\ImputationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditImputation extends EditRecord
{
    protected static string $resource = ImputationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
