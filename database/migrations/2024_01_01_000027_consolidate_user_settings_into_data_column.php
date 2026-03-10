<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $encryptedFields = [
        'catalog_beer_api_key',
        'untappd_client_id',
        'untappd_client_secret',
        'logr_db_token',
        'untappd_rss_feeds',
    ];

    private array $plainFields = [
        'untappd_username',
        'untappd_password',
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('data')->nullable()->after('remember_token');
        });

        $allFields = array_merge($this->encryptedFields, $this->plainFields);

        foreach (DB::table('users')->get() as $user) {
            $data = [];

            foreach ($allFields as $field) {
                $raw = $user->$field;
                if (empty($raw)) {
                    continue;
                }

                if (in_array($field, $this->encryptedFields)) {
                    try {
                        $value = Crypt::decryptString($raw);
                        // untappd_rss_feeds was encrypted:array — the decrypted value is JSON
                        if ($field === 'untappd_rss_feeds') {
                            $decoded = json_decode($value, true);
                            if ($decoded !== null) {
                                $value = $decoded;
                            }
                        }
                        $data[$field] = $value;
                    } catch (\Exception $e) {
                        // If decryption fails, store as-is (might be plain text)
                        $data[$field] = $raw;
                    }
                } else {
                    $data[$field] = $raw;
                }
            }

            if (! empty($data)) {
                DB::table('users')->where('id', $user->id)->update([
                    'data' => Crypt::encryptString(json_encode($data)),
                ]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn($this->encryptedFields);
            $table->dropColumn($this->plainFields);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('catalog_beer_api_key')->nullable();
            $table->text('untappd_client_id')->nullable();
            $table->text('untappd_client_secret')->nullable();
            $table->string('untappd_username')->nullable();
            $table->text('untappd_password')->nullable();
            $table->text('logr_db_token')->nullable();
            $table->text('untappd_rss_feeds')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('data');
        });
    }
};
