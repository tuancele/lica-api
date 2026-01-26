<?php

declare(strict_types=1);

namespace App\Modules\R2\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class R2ServiceProvider extends ServiceProvider
{
    public function boot()
    {
        try {
            if (Schema::hasTable('configs')) {
                $r2Settings = \App\Modules\Config\Models\Config::whereIn('name', [
                    'r2_account_id', 'r2_access_key_id', 'r2_secret_access_key', 'r2_bucket_name', 'r2_public_domain',
                ])->pluck('value', 'name');

                if (isset($r2Settings['r2_access_key_id']) && ! empty($r2Settings['r2_access_key_id'])) {
                    config([
                        'filesystems.disks.r2.key' => $r2Settings['r2_access_key_id'],
                        'filesystems.disks.r2.secret' => $r2Settings['r2_secret_access_key'] ?? '',
                        'filesystems.disks.r2.bucket' => $r2Settings['r2_bucket_name'] ?? '',
                        'filesystems.disks.r2.url' => (isset($r2Settings['r2_public_domain']) && strpos($r2Settings['r2_public_domain'], 'http') !== 0) ? 'https://'.$r2Settings['r2_public_domain'] : ($r2Settings['r2_public_domain'] ?? ''),
                        'filesystems.disks.r2.endpoint' => isset($r2Settings['r2_account_id']) ? "https://{$r2Settings['r2_account_id']}.r2.cloudflarestorage.com" : '',
                    ]);
                }
            }
        } catch (\Exception $e) {
            // quiet fail
        }
    }

    public function register()
    {
        $this->commands([
            \App\Modules\R2\Commands\SyncMediaR2::class,
        ]);
    }
}
