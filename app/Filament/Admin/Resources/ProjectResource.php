<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProjectResource\Pages;
use App\Models\Project;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Proyectos';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()->tabs([
                Forms\Components\Tabs\Tab::make('Información')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                                    $set('slug', \Illuminate\Support\Str::slug($state))),
                            Forms\Components\TextInput::make('slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                        ]),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('methodology')
                                ->options([
                                    'kanban' => 'Kanban',
                                    'scrum' => 'Scrum',
                                    'waterfall' => 'Waterfall',
                                ])
                                ->required()
                                ->default('kanban'),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'active' => 'Activo',
                                    'archived' => 'Archivado',
                                    'completed' => 'Completado',
                                ])
                                ->required()
                                ->default('active'),
                            Forms\Components\ColorPicker::make('color')
                                ->default('#6366f1'),
                        ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('start_date')->label('Fecha inicio'),
                            Forms\Components\DatePicker::make('end_date')->label('Fecha fin'),
                        ]),
                        Forms\Components\Select::make('owner_id')
                            ->label('Propietario')
                            ->options(User::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->default(fn () => auth()->id()),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('methodology')
                    ->colors([
                        'primary' => 'kanban',
                        'success' => 'scrum',
                        'warning' => 'waterfall',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'gray' => 'archived',
                        'primary' => 'completed',
                    ]),
                Tables\Columns\TextColumn::make('owner.name')->label('Propietario'),
                Tables\Columns\TextColumn::make('tasks_count')->counts('tasks')->label('Tareas'),
                Tables\Columns\TextColumn::make('start_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('end_date')->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('methodology')
                    ->options([
                        'kanban' => 'Kanban',
                        'scrum' => 'Scrum',
                        'waterfall' => 'Waterfall',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Activo',
                        'archived' => 'Archivado',
                        'completed' => 'Completado',
                    ]),
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
