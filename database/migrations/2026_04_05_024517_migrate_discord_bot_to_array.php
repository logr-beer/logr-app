<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Convert Setting: discord_bot (single) -> discord_bots (array)
        $single = Setting::where('key', 'discord_bot')->first();

        if ($single) {
            $bot = $single->value;

            if ($bot && ! empty($bot['guild_id'])) {
                Setting::set('discord_bots', [$bot]);
            }

            $single->delete();
        }

        // Convert User: discord_publish_checkins/purchases -> discord_bot_prefs keyed by guild_id
        foreach (User::all() as $user) {
            $publishCheckins = $user->getData('discord_publish_checkins');
            $publishPurchases = $user->getData('discord_publish_purchases');

            if ($publishCheckins === null && $publishPurchases === null) {
                continue;
            }

            $bots = Setting::get('discord_bots', []);
            $prefs = $user->getData('discord_bot_prefs') ?? [];

            foreach ($bots as $bot) {
                $guildId = $bot['guild_id'];
                if (! isset($prefs[$guildId])) {
                    $prefs[$guildId] = [
                        'publish_checkins' => (bool) $publishCheckins,
                        'publish_purchases' => (bool) $publishPurchases,
                    ];
                }
            }

            $user->setData('discord_bot_prefs', $prefs);
            $user->setData('discord_publish_checkins', null);
            $user->setData('discord_publish_purchases', null);
            $user->save();
        }
    }

    public function down(): void
    {
        // Convert back: discord_bots (array) -> discord_bot (first entry)
        $bots = Setting::get('discord_bots', []);

        if (! empty($bots)) {
            Setting::set('discord_bot', $bots[0]);
        }

        Setting::where('key', 'discord_bots')->delete();
    }
};
