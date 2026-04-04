<?php

namespace App\Livewire;

use App\Services\VersionChecker;
use Livewire\Component;

class VersionNotice extends Component
{
    public ?string $latestVersion = null;

    public ?string $releaseUrl = null;

    public bool $updateAvailable = false;

    public function mount(): void
    {
        $this->refreshVersion();
    }

    public function checkNow(): void
    {
        $latest = app(VersionChecker::class)->forceCheck();
        $this->applyLatest($latest);
    }

    public function render()
    {
        return view('livewire.version-notice');
    }

    protected function refreshVersion(): void
    {
        $latest = app(VersionChecker::class)->getLatest();
        $this->applyLatest($latest);
    }

    protected function applyLatest(?array $latest): void
    {
        if ($latest && version_compare($latest['version'], config('logr.version'), '>')) {
            $this->updateAvailable = true;
            $this->latestVersion = $latest['version'];
            $this->releaseUrl = $latest['url'];
        } else {
            $this->updateAvailable = false;
            $this->latestVersion = null;
            $this->releaseUrl = null;
        }
    }
}
