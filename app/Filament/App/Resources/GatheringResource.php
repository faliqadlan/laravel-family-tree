<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AppResource;
use App\Filament\App\Resources\GatheringResource\Pages\ListGatherings;
use App\Filament\App\Resources\GatheringResource\Pages\CreateGathering;
use App\Filament\App\Resources\GatheringResource\Pages\EditGathering;
use App\Filament\App\Resources\GatheringResource\Pages\ViewGathering;
use App\Filament\App\Resources\GatheringResource\RelationManagers\InvitationsRelationManager;
use App\Filament\App\Resources\GatheringResource\RelationManagers\ContributionsRelationManager;
use App\Filament\App\Resources\GatheringResource\RelationManagers\CommentsRelationManager;
use App\Models\Gathering;
use App\Models\Tree;
use App\Models\Person;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class GatheringResource extends AppResource
{
    protected static ?string $model = Gathering::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Family Gatherings';

    protected static string|\UnitEnum|null $navigationGroup = '👥 Family Reunions';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Event Details')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('type')
                            ->options([
                                Gathering::TYPE_REUNION => 'Reunion',
                                Gathering::TYPE_MEMORIAL => 'Memorial',
                                Gathering::TYPE_WEDDING => 'Wedding',
                                Gathering::TYPE_BIRTHDAY => 'Birthday',
                                Gathering::TYPE_HOLIDAY => 'Holiday',
                                Gathering::TYPE_OTHER => 'Other',
                            ])
                            ->required()
                            ->default(Gathering::TYPE_REUNION),
                        Select::make('status')
                            ->options([
                                Gathering::STATUS_DRAFT => 'Draft',
                                Gathering::STATUS_PUBLISHED => 'Published',
                                Gathering::STATUS_CANCELLED => 'Cancelled',
                                Gathering::STATUS_COMPLETED => 'Completed',
                            ])
                            ->required()
                            ->default(Gathering::STATUS_DRAFT),
                        Select::make('privacy')
                            ->options([
                                Gathering::PRIVACY_PUBLIC => 'Public',
                                Gathering::PRIVACY_CONNECTIONS => 'Connections Only',
                                Gathering::PRIVACY_PRIVATE => 'Private',
                            ])
                            ->required()
                            ->default(Gathering::PRIVACY_CONNECTIONS),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                ]),

            Section::make('Location & Schedule')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('location')
                            ->maxLength(255),
                        TextInput::make('location_url')
                            ->label('Location URL')
                            ->url()
                            ->maxLength(255),
                        DateTimePicker::make('start_date')
                            ->required()
                            ->native(false),
                        DateTimePicker::make('end_date')
                            ->native(false)
                            ->after('start_date'),
                        Toggle::make('is_virtual')
                            ->label('Virtual Event')
                            ->live(),
                        TextInput::make('meeting_url')
                            ->label('Meeting URL')
                            ->url()
                            ->maxLength(255)
                            ->visible(fn ($get) => $get('is_virtual')),
                    ]),
                ]),

            Section::make('Tree Context')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('tree_id')
                            ->label('Family Tree')
                            ->options(fn () => Tree::where('user_id', auth()->id())->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Select::make('person_id')
                            ->label('Associated Person')
                            ->options(fn () => Person::pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                    ]),
                ]),

            Section::make('Funding')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('funding_goal')
                            ->label('Funding Goal')
                            ->numeric()
                            ->minValue(0),
                        Select::make('funding_currency')
                            ->label('Currency')
                            ->options([
                                'USD' => 'USD - US Dollar',
                                'EUR' => 'EUR - Euro',
                                'GBP' => 'GBP - British Pound',
                            ])
                            ->default('USD'),
                        Textarea::make('funding_description')
                            ->label('Funding Description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'reunion' => 'primary',
                        'memorial' => 'gray',
                        'wedding' => 'warning',
                        'birthday' => 'success',
                        'holiday' => 'info',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
                TextColumn::make('start_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('location')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('funding_goal')
                    ->money(fn (Gathering $record) => $record->funding_currency)
                    ->toggleable(),
                TextColumn::make('organizer.name')
                    ->label('Organizer')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Gathering::STATUS_DRAFT => 'Draft',
                        Gathering::STATUS_PUBLISHED => 'Published',
                        Gathering::STATUS_CANCELLED => 'Cancelled',
                        Gathering::STATUS_COMPLETED => 'Completed',
                    ]),
                SelectFilter::make('type')
                    ->options([
                        Gathering::TYPE_REUNION => 'Reunion',
                        Gathering::TYPE_MEMORIAL => 'Memorial',
                        Gathering::TYPE_WEDDING => 'Wedding',
                        Gathering::TYPE_BIRTHDAY => 'Birthday',
                        Gathering::TYPE_HOLIDAY => 'Holiday',
                        Gathering::TYPE_OTHER => 'Other',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            InvitationsRelationManager::class,
            ContributionsRelationManager::class,
            CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGatherings::route('/'),
            'create' => CreateGathering::route('/create'),
            'view' => ViewGathering::route('/{record}'),
            'edit' => EditGathering::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['organizer', 'tree']);
    }
}
