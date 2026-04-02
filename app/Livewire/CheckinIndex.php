<?php

namespace App\Livewire;

use App\Models\Checkin;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Check-ins')]
class CheckinIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'newest';
    public string $sortDirection = 'desc';
    public array $selected = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'newest'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function updatingSortDirection(): void
    {
        $this->resetPage();
    }

    public function toggleSelected(int $checkinId): void
    {
        if (in_array($checkinId, $this->selected)) {
            $this->selected = array_values(array_diff($this->selected, [$checkinId]));
        } else {
            $this->selected[] = $checkinId;
        }
    }

    public function selectAll(): void
    {
        $this->selected = Checkin::where('user_id', auth()->id())
            ->latest()
            ->pluck('id')
            ->all();
    }

    public function deselectAll(): void
    {
        $this->selected = [];
    }

    public function deleteSelected(): void
    {
        Checkin::where('user_id', auth()->id())
            ->whereIn('id', $this->selected)
            ->delete();

        $this->selected = [];
    }

    public function render()
    {
        $query = Checkin::where('user_id', auth()->id())
            ->with(['beer.brewery', 'photos', 'venue']);

        if ($this->search) {
            $query->whereHas('beer', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('brewery', fn ($b) => $b->where('name', 'like', '%' . $this->search . '%'));
            });
        }

        $dir = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        $query = match ($this->sortBy) {
            'rating' => $query->orderBy('rating', $dir),
            default => $dir === 'desc' ? $query->latest() : $query->oldest(),
        };

        return view('livewire.checkin-index', [
            'checkins' => $query->paginate(20),
        ]);
    }
}
