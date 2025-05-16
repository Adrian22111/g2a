<?php

namespace App\Command;

use App\Service\G2aClient;
use Symfony\Component\Console\Command\Command;
use G2A\IntegrationApi\Request\OrderAddRequest;
use G2A\IntegrationApi\Request\OrderKeyRequest;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use G2A\IntegrationApi\Request\OrderDetailsRequest;
use G2A\IntegrationApi\Request\OrderPaymentRequest;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use G2A\IntegrationApi\Exception\IntegrationApiException;
use G2A\IntegrationApi\Exception\Request\ValidatorException;
use G2A\IntegrationApi\Exception\Response\BadResponseException;

#[AsCommand(
    name: 'app:buy-key',
    description: 'Komenda do zakupów na platformie G2A',
)]
class BuyKeyCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('inventory', null, InputOption::VALUE_REQUIRED, 'Inventory')
            ->addOption('product', null, InputOption::VALUE_REQUIRED, 'product')
            ->addOption('qty', null, InputOption::VALUE_REQUIRED, 'qty')
            ->addOption('currency', null, InputOption::VALUE_REQUIRED, 'currency')
            ->addOption('maxprice', null, InputOption::VALUE_REQUIRED, 'maxprice')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $inventory = $input->getOption('inventory');
        $product = $input->getOption('product');
        $qty = $input->getOption('qty');
        $currency = $input->getOption('currency');
        $maxPrice = $input->getOption('maxprice');

        if ($inventory === null) {
            $io->error('Opcja inventory jest wymagana');
            return Command::FAILURE;
        }
        if ($product === null) {
            $io->error('Opcja product jest wymagana');
            return Command::FAILURE;
        }
        if ($qty === null) {
            $io->error('Opcja qty jest wymagana');
            return Command::FAILURE;
        }
        if ($currency === null) {
            $io->error('Opcja currency jest wymagana');
            return Command::FAILURE;
        }
        if ($maxPrice === null) {
            $io->error('Opcja maxprice jest wymagana');
            return Command::FAILURE;
        }

        switch ($inventory) {
            case 'g2a':
                for ($i = 0; $i < $qty; $i++) {
                    try {
                        $g2aClient = G2aClient::create(
                            $_ENV['G2A_EMAIL'],
                            $_ENV['ENV_DOMAIN'],
                            $_ENV['CLIENT_ID'],
                            $_ENV['CLIENT_SECRET']
                        );

                        $request = new OrderAddRequest($g2aClient);
                        $request
                            ->setProductId($product)
                            ->setCurrency($currency)
                            ->setMaxPrice(intval($maxPrice))
                            ->call()
                        ;

                        $addOrderResponse = $request->getResponse();

                        $request = new OrderPaymentRequest($g2aClient);
                        $request
                            ->setOrderId($addOrderResponse->getOrderId())
                            ->call()
                        ;

                        $payOrderResponse = $request->getResponse();

                        do {
                            $request = new OrderDetailsRequest($g2aClient);
                            $request
                                ->setOrderId($addOrderResponse->getOrderId())
                                ->call();

                            $orderDetailsResponse = $request->getResponse();
                            $status = $orderDetailsResponse->getStatus();
                        } while (
                            $status != 'complete'
                        );

                        $request = new OrderKeyRequest($g2aClient);
                        $request
                            ->setOrderId($addOrderResponse->getOrderId())
                            ->call();

                        $orderDetailsResponse = $request->getResponse();
                        $io->info($orderDetailsResponse->getKey());
                    } catch (ValidatorException $e) {
                        $io->error('Bad request: ' . $e->getMessage());
                        return Command::FAILURE;
                    } catch (BadResponseException $e) {
                        $io->error('API error: ' . $e->getResponse()->getMessage() . ' (' . $e->getResponse()->getCode() . ')');
                        return Command::FAILURE;
                    } catch (IntegrationApiException $e) {
                        $io->error('Error: ' . $e->getMessage());
                        return Command::FAILURE;
                    }
                }
                return Command::SUCCESS;
                break;
            default:
                $io->error('Nieobsługiwane inventory');
                return Command::FAILURE;
        }
    }
}
