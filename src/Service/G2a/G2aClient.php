<?php

namespace App\Service\G2a;

use Exception;
use G2A\IntegrationApi\Client;
use G2A\IntegrationApi\Model\Config;
use G2A\IntegrationApi\Request\OrderAddRequest;
use G2A\IntegrationApi\Request\OrderKeyRequest;
use G2A\IntegrationApi\Request\OrderDetailsRequest;
use G2A\IntegrationApi\Request\OrderPaymentRequest;
use G2A\IntegrationApi\Exception\IntegrationApiException;
use G2A\IntegrationApi\Exception\Request\ValidatorException;
use G2A\IntegrationApi\Exception\Response\BadResponseException;

class G2aClient
{
    private $client;

    private function __construct($client)
    {
        $this->client = $client;
    }

    public static function create($email, $domain, $clientId, $clientSecret)
    {
        $config = new Config(
            $email,
            $domain,
            $clientId,
            $clientSecret
        );

        $client = new Client($config);
        return new self($client);
    }

    public function buyKeys($product, $qty, $currency, $maxPrice, &$keys)
    {
        for ($i = 0; $i < $qty; $i++) {
            $request = new OrderAddRequest($this->client);
            $request
                ->setProductId($product)
                ->setCurrency($currency)
                ->setMaxPrice(intval($maxPrice))
                ->call()
            ;

            $addOrderResponse = $request->getResponse();

            $request = new OrderPaymentRequest($this->client);
            $request
                ->setOrderId($addOrderResponse->getOrderId())
                ->call()
            ;

            $payOrderResponse = $request->getResponse();
            try {
                $counter = 0;
                do {
                    $request = new OrderDetailsRequest($this->client);
                    $request
                        ->setOrderId($addOrderResponse->getOrderId())
                        ->call();

                    $orderDetailsResponse = $request->getResponse();
                    $status = $orderDetailsResponse->getStatus();
                    $counter += 1;
                    sleep(1);
                } while (
                    $status != 'complete' && $counter < 10
                );
                if ($counter >= 10) {
                    throw new \RuntimeException('Przekroczono limit zapytań o status ');
                }
            } catch (\RuntimeException $e) {
                $keys[] = sprintf($e->getMessage() . 'dla zamówienia ' . $addOrderResponse->getOrderId());
                continue;
            }

            $request = new OrderKeyRequest($this->client);
            $request
                ->setOrderId($addOrderResponse->getOrderId())
                ->call();

            $orderKeyResponse = $request->getResponse();
            $keys[] = $orderKeyResponse->getKey();
        }
        return;
    }
}
