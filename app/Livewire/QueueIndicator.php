<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class QueueIndicator extends Component
{
    public function render()
    {
        $pending = DB::table('jobs')->count();

        return view('livewire.queue-indicator', [
            'pending' => $pending,
        ]);
    }
}
