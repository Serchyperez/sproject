<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SprintResource\Pages;
use App\Models\Sprint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SprintResource extends Resource
{
    protected static ?string $model = Sprint::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('project_id')
                ->relationship('project', 'name')
                ->required()->searchable()->preload(),
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\Textarea::make('goal')->rows(2)->label('Objetivo del sprint'),
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Select::make('status')
                    ->options(['planning' => 'Planificación', 'active' => 'Activo', 'completed' => 'Completado'])
                    ->default('planning')->required(),
                Forms\Components\DatePicker::make('start_date')->label('Fecha inicio'),
                Forms\Components\DatePicker::make('end_date')->label('Fecha fin'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors(['gray' => 'planning', 'primary' => 'active', 'success' => 'completed']),
                Tables\Columns\TextColumn::make('start_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('end_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('tasks_count')->counts('tasks')->label('Tareas'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project')->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['planning' => 'Planificación', 'active' => 'Activo', 'completed' => 'Completado']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSprints::route('/'),
            'create' => Pages\CreateSprint::route('/create'),
            'edit' => Pages\EditSprint::route('/{record}/edit'),
        ];
    }
}
