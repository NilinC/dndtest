<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:output-products',
    description: 'Commande pour lire un fichier csv en entrée et afficher un tableau formaté'
)]
class OutputProductsCommand extends Command
{
    private const HEADERS = [
        'Sku',
        'Status',
        'Price',
        'Description',
        'Created At',
        'Slug',
    ];
    private const ENABLE = "Enable";
    private const DISABLE = "Disable";
    private const FILENAME_PATH = 'filename-path';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filename = $input->getArgument(self::FILENAME_PATH);

        $this->testFileExtension(pathinfo($filename, PATHINFO_EXTENSION), $output);

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

        // On supprime la première ligne du fichier car les headers sont définis via une constante
        $productsLines = array_splice($products, 1, count($products));

        $rows = [];
        foreach ($productsLines as $productLine) {
            $rows[] = [
                    $productLine[0],
                    ('1' === $productLine[2]) ? self::ENABLE : self::DISABLE,
                    $this->formatPrice($productLine[3]) . $productLine[4],
                    $this->br2nl($productLine[5]),
                    $this->formatDate($productLine[6]),
                    $this->formatSlug($productLine[1]),
            ];
        }

        $table = new Table($output);
        $table
            ->setHeaders(self::HEADERS)
            ->setRows($rows)
        ;
        $table->render();

        fclose($handle);

        return Command::SUCCESS;
    }

    protected function configure()
    {
        $this
            ->addArgument(self::FILENAME_PATH, InputArgument::REQUIRED, 'lien vers le fichier CSV à afficher')
        ;
    }

    private function testFileExtension($fileExtension, $output)
    {
        if ('csv' !== $fileExtension) {
            $output->write('Le fichier fourni n\'a pas le bon type');

            return Command::INVALID;
        }
    }

    /**
     * Fonction qui va afficher le prix en remplaçant le . par une ,
     *
     * @param string $string
     * @return string
     */
    private function formatPrice(string $string): string
    {
        return preg_replace('/(\d+)\./', '${1},',$string);
    }

    /**
     * Fonction qui interprète les balises HTML
     *
     * @param string $string
     * @return string
     */
    private function br2nl(string $string): string
    {
        return preg_replace('/\<(\s*)?br(\s*)?\/?\>/i', "\n", $string);
    }

    /**
     * Fonction qui va formater la date selon la RFC 850 (standard for interchange of USENET messages)
     *
     * @param string $string
     * @return string
     */
    private function formatDate(string $string): string
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $string);
        $date->setTimezone(new \DateTimeZone('CET'));

        return $date->format(\DateTimeInterface::RFC850);
    }


    /**
     * Fonction qui remplace les espaces et caractères spéciaux pour formatter la colonne slug
     * @param string $string
     * @return string
     */
    private function formatSlug(string $string) : string
    {
        $formattedString = preg_replace('/[\/\&%#,\$]/i', '_', $string);
        $formattedString = preg_replace('/ /', "-", $formattedString);

        return strtolower($formattedString);
    }
}
