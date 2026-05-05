<?php

namespace App\Filament\App\Resources\ImputationResource\Pages;

use App\Filament\App\Resources\ImputationResource;
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
