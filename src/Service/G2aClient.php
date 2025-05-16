<?php

namespace App\Service;

use \G2A\IntegrationApi\Model\Config;
use \G2A\IntegrationApi\Client;

class G2aClient
{
    public static function create($email, $domain, $clientId, $clientSecret)
    {
        $config = new Config(
            $email,
            $domain,
            $clientId,
            $clientSecret
        );
        return new Client($config);
    }
}
