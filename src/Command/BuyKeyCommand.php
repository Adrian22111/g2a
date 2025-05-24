<?php

namespace App\Command;

use RuntimeException;
use App\Service\G2aClient;
use App\Service\InventoryFactory;
use Exception;
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

        $keys = [];
        try {
            $inventoryClient = InventoryFactory::create($inventory);
            $inventoryClient->buyKeys($product, $qty, $currency, $maxPrice, $keys);
        } catch (ValidatorException $e) {
            $errors[] = 'Bad request: ' . $e->getMessage();
        } catch (BadResponseException $e) {
            $errors[] = 'API error: ' . $e->getResponse()->getMessage() . ' (' . $e->getResponse()->getCode() . ')';
        } catch (IntegrationApiException $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
        if (!empty($keys))
            $io->info($keys);
        if (!empty($errors)) {
            $io->error($errors);
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }


    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $inventory = $input->getOption('inventory');
        $product = $input->getOption('product');
        $qty = $input->getOption('qty');
        $currency = $input->getOption('currency');
        $maxPrice = $input->getOption('maxprice');

        $errors = [];

        if ($inventory === null) {
            $errors[] = 'Opcja inventory jest wymagana';
        }
        if ($product === null) {
            $errors[] = 'Opcja product jest wymagana';
        }
        if ($qty === null) {
            $errors[] = 'Opcja qty jest wymagana';
        } else if (filter_var($qty, FILTER_VALIDATE_INT) === false || $qty < 1) {
            $errors[] = 'Opcja qty (ilość kluczy) musi być liczbą całkowitą większą od 0';
        }
        if ($currency === null) {
            $errors[] = 'Opcja currency jest wymagana';
        }
        if ($maxPrice === null) {
            $errors[] = 'Opcja maxprice jest wymagana';
        } else if (!is_numeric($maxPrice) || (float)$maxPrice < 1) {
            $errors[] = 'Maxprice musi być liczbą większą od 0';
        }

        if (!empty($errors)) {
            throw new \RuntimeException(implode("\n", $errors));
        }
    }
}
