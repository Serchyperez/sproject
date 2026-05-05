<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ImputationResource\Pages;
use App\Models\TaskImputation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ImputationResource extends Resource
{
    protected static ?string $model = TaskImputation::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Imputaciones';
    protected static ?int $navigationSort = 6;

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
            Forms\Components\Select::make('task_id')
                ->relationship('task', 'title')
                ->required()->searchable()->preload(),
            Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->required()->searchable()->preload(),
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\TextInput::make('hours')
                    ->numeric()->required()->minValue(0.25)->step(0.25),
                Forms\Components\DatePicker::make('date')->required()->default(now()),
                Forms\Components\TextInput::make('description')->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('task.title')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('task.project.name')->label('Proyecto'),
                Tables\Columns\TextColumn::make('user.name')->sortable(),
                Tables\Columns\TextColumn::make('hours')->suffix(' h')->sortable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('description')->limit(40),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')->relationship('user', 'name'),
            ])
            ->defaultSort('date', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImputations::route('/'),
            'create' => Pages\CreateImputation::route('/create'),
            'edit' => Pages\EditImputation::route('/{record}/edit'),
        ];
    }
}
