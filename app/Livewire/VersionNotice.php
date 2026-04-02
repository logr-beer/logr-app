<?php

namespace App\Livewire;

use App\Services\VersionChecker;
use Livewire\Component;

class VersionNotice extends Component
{
    public ?string $latestVersion = null;

    public ?string $releaseUrl = null;

    public bool $updateAvailable = false;

    public function mount(VersionChecker $checker): void
    {
        $latest = $checker->getLatest();

        if ($latest && version_compare($latest['version'], config('logr.version'), '>')) {
            $this->updateAvailable = true;
            $this->latestVersion = $latest['version'];
            $this->releaseUrl = $latest['url'];
        }
    }

    public function render()
    {
        return view('livewire.version-notice');
    }
}
