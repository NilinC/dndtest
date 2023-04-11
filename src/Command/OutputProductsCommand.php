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

        $this->testFileExtension(pathinfo($filename, PATHINFO_EXTENSION));

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

        // On doit transformer le tableau associatif en simple tableau pour le array_combine()
        $keys = $this->flatten(array_slice($products, 0,1));
        // On supprime la première ligne du fichier car les headers sont définis via une constante
        $values = array_splice($products, 1, count($products));

        // On crée un tableau associatif avec la 1ère ligne du CSV comme keys et les lignes des produits comme values
        $combineArray = [];
        foreach ($values as $line) {
            $combineArray[] = array_combine($keys, $line);
        }

        $rows = [];
        foreach ($combineArray as $productLine) {
            $rows[] = [
                    $productLine['sku'],
                    ('1' === $productLine['is_enabled']) ? self::ENABLE : self::DISABLE,
                    $this->formatPrice($productLine['price']) . $productLine['currency'],
                    $this->br2nl($productLine['description']),
                    $this->formatDate($productLine['created_at']),
                    $this->formatSlug($productLine['title']),
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

    private function testFileExtension($fileExtension)
    {
        if ('csv' !== $fileExtension) {
            throw new \RuntimeException(
                'Le fichier fourni n\'a pas le bon type',
                Command::INVALID);
        }
    }

    /**
     * Cette fonction permet de transformer un tableau associatif en simple tableau
     *
     * @param $array
     * @return array
     */
    private function flatten($array): array
    {
        $flattenArray = [];

        array_walk_recursive($array, function($value) use (&$flattenArray) { $flattenArray[] = $value; });

        return $flattenArray;
    }

    /**
     * Fonction qui va afficher le prix, arrondi au dixième, avec 2 décimales après la virgule
     *
     * @param string $string
     * @return string
     */
    private function formatPrice(string $string): string
    {
        return number_format($string, 2, ",", null);
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
