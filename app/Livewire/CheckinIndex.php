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

    public bool $selecting = false;
    public array $selected = [];

    public function toggleSelecting(): void
    {
        $this->selecting = !$this->selecting;
        $this->selected = [];
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
        $this->selecting = false;
    }

    public function render()
    {
        return view('livewire.checkin-index', [
            'checkins' => Checkin::where('user_id', auth()->id())
                ->with(['beer.brewery', 'photos', 'venue'])
                ->latest()
                ->paginate(20),
        ]);
    }
}
