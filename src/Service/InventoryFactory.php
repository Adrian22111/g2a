<?php

namespace App\Service;

use App\Service\G2a\G2aClient;

class InventoryFactory
{
    public static function create($inventory)
    {
        return match (strtolower($inventory)) {
            'g2a' => G2aClient::create(
                $_ENV['G2A_EMAIL'],
                $_ENV['G2A_DOMAIN'],
                $_ENV['G2A_CLIENT_ID'],
                $_ENV['G2A_CLIENT_SECRET']
            ),
            default => throw new \InvalidArgumentException("Nieobsługiwane Inventory: $inventory"),
        };
    }
}
