<?php

namespace App\Filament\Admin\Resources\MilestoneResource\Pages;

use App\Filament\Admin\Resources\MilestoneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMilestones extends ListRecords
{
    protected static string $resource = MilestoneResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
