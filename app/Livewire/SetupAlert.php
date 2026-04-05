<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SetupAlert extends Component
{
    public bool $dismissed = false;

    public array $missing = [];

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $rssFeeds = $user->getData('untappd_rss_feeds') ?? [];
        $webhooks = $user->getData('discord_webhooks') ?? [];
        $hasBot = ! empty(\App\Models\Setting::get('discord_bots', []));

        if (empty($rssFeeds)) {
            $this->missing[] = 'untappd';
        }

        if (empty($webhooks) && ! $hasBot) {
            $this->missing[] = 'discord';
        }

        $this->dismissed = (bool) $user->getData('setup_alert_dismissed');
    }

    public function dismiss(): void
    {
        $this->dismissed = true;

        $user = Auth::user();
        $user->setData('setup_alert_dismissed', true);
        $user->save();
    }

    public function render()
    {
        return view('livewire.setup-alert');
    }
}
