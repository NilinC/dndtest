<?php

namespace App\Command;

use App\Model\Headers;
use App\Formatter\Formatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
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

    private Formatter $formatter;

    public function __construct(Formatter $formatter)
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

        $products = [];
        while (($data = fgetcsv($handle, null, ";")) !== FALSE) {
            $products[] = $data;
        }

        fclose($handle);

        // On doit transformer le tableau associatif en simple tableau pour le array_combine()
        $keys = $this->formatter->flatten(array_slice($products, 0, 1));
        // On supprime la première ligne du fichier car les headers sont définis via une constante
        $values = array_splice($products, 1, count($products));

        // On crée un tableau associatif avec la 1ère ligne du CSV comme keys et les lignes des produits comme values
        $combineArray = [];
        foreach ($values as $line) {
            $combineArray[] = array_combine($keys, $line);
        }

        $rows = $this->formatter->createProductsRows($combineArray, $this->formatter);

        if ($input->getOption(self::JSON_OUTPUT_OPTION)) {
            $output->writeln(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table
            ->setHeaders(Headers::serialize())
            ->setRows(array_values($rows))
        ;
        $table->render();

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
