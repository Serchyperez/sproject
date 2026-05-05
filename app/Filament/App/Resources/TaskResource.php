<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TaskResource\Pages;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 3;

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
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
            Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull(),
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Select::make('priority')
                    ->options(['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'urgent' => 'Urgente'])
                    ->required()->default('medium'),
                Forms\Components\Select::make('type')
                    ->options(['task' => 'Tarea', 'bug' => 'Bug', 'story' => 'Historia', 'epic' => 'Epic'])
                    ->required()->default('task'),
                Forms\Components\Select::make('assigned_to')
                    ->relationship('assignee', 'name')
                    ->searchable()->preload()->nullable(),
            ]),
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\TextInput::make('story_points')->numeric()->nullable(),
                Forms\Components\TextInput::make('estimated_hours')->numeric()->nullable(),
                Forms\Components\DatePicker::make('due_date')->nullable(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(50),
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors(['gray' => 'low', 'primary' => 'medium', 'warning' => 'high', 'danger' => 'urgent']),
                Tables\Columns\BadgeColumn::make('type'),
                Tables\Columns\TextColumn::make('assignee.name')->label('Asignado a'),
                Tables\Columns\TextColumn::make('status.name')->label('Estado'),
                Tables\Columns\TextColumn::make('due_date')->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project')->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('priority')
                    ->options(['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'urgent' => 'Urgente']),
                Tables\Filters\SelectFilter::make('type')
                    ->options(['task' => 'Tarea', 'bug' => 'Bug', 'story' => 'Historia', 'epic' => 'Epic']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
