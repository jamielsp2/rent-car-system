<?php

namespace App\Filament\Concerns;

use App\Services\BookingStats;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard\Concerns\HasFilters;
use Filament\Support\Icons\Heroicon;

/**
 * Period picker shown as a header button on the dashboards; widgets read the
 * choice through BookingStats::range().
 */
trait FiltersByPeriod
{
    use HasFilters;

    protected function getHeaderActions(): array
    {
        return [$this->periodAction()];
    }

    protected function periodAction(): ActionGroup
    {
        $current = $this->filters['period'] ?? '30d';

        $presets = collect(BookingStats::PRESETS)
            ->except('custom')
            ->map(fn (string $label, string $key) => Action::make("period_{$key}")
                ->label($label)
                ->icon($current === $key ? Heroicon::Check : null)
                ->action(fn () => $this->setPeriod(['period' => $key])))
            ->values()
            ->all();

        return ActionGroup::make([
            ActionGroup::make($presets)->dropdown(false),
            Action::make('period_custom')
                ->label('Elegir fechas…')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->modalHeading('Período personalizado')
                ->modalWidth('md')
                ->modalSubmitActionLabel('Aplicar')
                ->fillForm(fn () => [
                    'from' => $this->filters['from'] ?? null,
                    'to' => $this->filters['to'] ?? null,
                ])
                ->schema([
                    DatePicker::make('from')->label('Desde')->required()->native(false)->displayFormat('d/m/Y'),
                    DatePicker::make('to')->label('Hasta')->required()->native(false)->displayFormat('d/m/Y')->afterOrEqual('from'),
                ])
                ->action(fn (array $data) => $this->setPeriod(['period' => 'custom', ...$data])),
        ])
            ->label($this->periodLabel())
            ->icon(Heroicon::OutlinedCalendar)
            ->button()
            ->color('gray');
    }

    protected function periodLabel(): string
    {
        if (($this->filters['period'] ?? null) !== 'custom') {
            return BookingStats::PRESETS[$this->filters['period'] ?? '30d'] ?? BookingStats::PRESETS['30d'];
        }

        [$from, $to] = BookingStats::range($this->filters);

        return $from->translatedFormat('j M') . ' – ' . $to->translatedFormat('j M Y');
    }

    protected function setPeriod(array $filters): void
    {
        $this->filters = $filters;
        $this->updatedFilters();
    }
}
