<?php

namespace App\Command;

use App\Formatter\ProductFormatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:output-products',
    description: 'Commande pour lire un fichier csv en entrée et afficher un tableau formaté'
)]
class OutputProductsCommand extends Command
{
    private const FILENAME_PATH = 'filename-path';
    private const JSON_OUTPUT_OPTION = 'json';

    private ProductFormatter $formatter;

    public function __construct(ProductFormatter $formatter)
    {
        $this->formatter = $formatter;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filename = $input->getArgument(self::FILENAME_PATH);

        $this->testFileExtensionIsCsv(pathinfo($filename, PATHINFO_EXTENSION));

        try {
            $handle = fopen($filename, 'r');
        } catch (\ErrorException $exception) {
            $output->writeln('Le lien vers le fichier est incorrect ' . $exception);

            return Command::INVALID;
        }

        $lines = [];
        while (($data = fgetcsv($handle, null, ";")) !== FALSE) {
            $lines[] = $data;
        }

        fclose($handle);

        $products = $this->formatter->createProductsRows($lines);

        if ($input->getOption(self::JSON_OUTPUT_OPTION)) {
            $output->writeln(json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return Command::SUCCESS;
        }

        $this->formatter->renderProductsTable($products, $output);

        return Command::SUCCESS;
    }

    protected function configure()
    {
        $this
            ->addArgument(self::FILENAME_PATH, InputArgument::REQUIRED, 'lien vers le fichier CSV à afficher')
            ->addOption(self::JSON_OUTPUT_OPTION, null, InputOption::VALUE_NONE, 'Option pour afficher les produits sous format JSON plutôt que tableau formaté')
        ;
    }

    private function testFileExtensionIsCsv($fileExtension)
    {
        if ('csv' !== $fileExtension) {
            throw new \RuntimeException(
                'Le fichier fourni n\'a pas le bon type',
                Command::INVALID);
        }
    }
}
