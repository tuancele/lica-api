<?php

declare(strict_types=1);

namespace App\Services\Gmc;

use Google\Client as GoogleClient;
use Google\Service\ShoppingContent;

class GmcClientFactory
{
    public function makeContentService(): ShoppingContent
    {
        $jsonPath = (string) config('gmc.service_account_json', '');
        if ($jsonPath === '' || ! is_file($jsonPath)) {
            throw new \RuntimeException('GMC service account JSON is missing.');
        }

        $client = new GoogleClient;
        $client->setAuthConfig($jsonPath);
        $client->setScopes([ShoppingContent::CONTENT]);
        $client->setApplicationName('lica-gmc');

        return new ShoppingContent($client);
    }
}
