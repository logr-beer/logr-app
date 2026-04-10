<?php

namespace App\Livewire\Admin;

use App\Models\Beer;
use App\Models\Brewery;
use App\Models\Checkin;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SystemInfo extends Component
{
    public bool $showPurgeModal = false;

    public bool $purgeWithDemo = false;

    public bool $showPurgeSettingsModal = false;

    public bool $showResetModal = false;

    public string $purgeConfirmation = '';

    public string $purgeSettingsConfirmation = '';

    public function getSystemInfoProperty(): array
    {
        return [
            'Logr Version' => config('logr.version'),
            'PHP Version' => PHP_VERSION,
            'Laravel Version' => app()->version(),
            'Livewire Version' => \Composer\InstalledVersions::getPrettyVersion('livewire/livewire'),
            'Database' => config('database.default'),
            'Cache Driver' => config('cache.default'),
            'Queue Driver' => config('queue.default'),
            'Environment' => app()->environment(),
            'Debug Mode' => config('app.debug') ? 'Enabled' : 'Disabled',
            'Demo Mode' => config('app.demo_mode') ? 'Enabled' : 'Disabled',
        ];
    }

    public function getStatsProperty(): array
    {
        return [
            'Users' => User::count(),
            'Beers' => Beer::count(),
            'Breweries' => Brewery::count(),
            'Check-ins' => Checkin::count(),
        ];
    }

    public function getQueueStatsProperty(): array
    {
        $pending = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->count();

        $batches = DB::table('job_batches')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'total' => $b->total_jobs,
                'pending' => $b->pending_jobs,
                'failed' => $b->failed_jobs,
                'finished' => ! is_null($b->finished_at),
                'created_at' => $b->created_at,
            ]);

        return [
            'pending' => $pending,
            'failed' => $failed,
            'batches' => $batches,
        ];
    }

    public function retryFailedJobs(): void
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        session()->flash('message', 'All failed jobs have been pushed back to the queue.');
    }

    public function flushFailedJobs(): void
    {
        Artisan::call('queue:flush');

        session()->flash('message', 'All failed jobs have been cleared.');
    }

    public function confirmPurge(): void
    {
        $this->showPurgeModal = true;
        $this->purgeConfirmation = '';
    }

    public function purgeData(): void
    {
        if ($this->purgeConfirmation !== 'PURGE') {
            $this->addError('purgeConfirmation', 'Please type PURGE to confirm.');

            return;
        }

        Artisan::call('logr:purge', [
            '--force' => true,
            '--demo' => $this->purgeWithDemo,
        ]);

        $this->showPurgeModal = false;
        $this->purgeConfirmation = '';
        $this->purgeWithDemo = false;

        session()->flash('message', 'All data has been purged.'.($this->purgeWithDemo ? ' Demo data loaded.' : ''));

        $this->redirect(route('admin.system'), navigate: true);
    }

    public function confirmPurgeSettings(): void
    {
        $this->showPurgeSettingsModal = true;
        $this->purgeSettingsConfirmation = '';
    }

    public function purgeSettings(): void
    {
        if ($this->purgeSettingsConfirmation !== 'PURGE') {
            $this->addError('purgeSettingsConfirmation', 'Please type PURGE to confirm.');

            return;
        }

        Artisan::call('logr:purge-settings', ['--force' => true]);

        $this->showPurgeSettingsModal = false;
        $this->purgeSettingsConfirmation = '';

        session()->flash('message', 'All user settings have been purged.');

        $this->redirect(route('admin.system'), navigate: true);
    }

    public function confirmReset(): void
    {
        $this->showResetModal = true;
    }

    public function resetApp(): void
    {
        Artisan::call('app:reset', ['--force' => true]);

        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('setup'), navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.system-info')
            ->layout('layouts.app')
            ->title('System Info');
    }
}
