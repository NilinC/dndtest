<?php

namespace App\Formatter;

use App\Model\Headers;
use App\Model\Status;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductFormatter
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    /**
     * Cette fonction permet de créer le tableau des produits selon l'ordre des colonnes suivantes :
     * "Sku", "Status", "Price", "Description", "CreatedAt", "Slug"
     *
     * @param array $data
     * @return array
     */
    public function createProductsRows(array $data): array
    {
        // On doit transformer le tableau associatif en simple tableau pour le array_combine()
        $keys = $this->flatten(array_slice($data, 0, 1));
        // On supprime la première ligne du fichier car les headers sont définis via une constante
        $values = array_splice($data, 1, count($data));

        $result = $this->createAssociativeArrayWithKeysAndValues($keys, $values);

        return $this->organizeProductsColumns($result);
    }

    /**
     * Cette fonction permet de créer et afficher un Table Symfony spécifique pour les commandes
     *
     * @param array $products
     * @param OutputInterface $output
     * @return void
     */
    public function renderProductsTable(array $products, OutputInterface $output): void
    {
        $table = new Table($output);
        $table
            ->setHeaders(Headers::serialize())
            ->setRows(array_values($products))
        ;

        $table->render();
    }

    /**
     * Cette fonction permet de transformer un tableau associatif en simple tableau
     *
     * @param array $array
     * @return array
     */
    private function flatten(array $array): array
    {
        $flattenArray = [];

        array_walk_recursive($array, function ($value) use (&$flattenArray) {
            $flattenArray[] = $value;
        });

        return $flattenArray;
    }

    /**
     * Cette fonction permet de créer un tableau associatif en associant les clés à leur(s) valeur(s)
     *
     * @param array $keys
     * @param array $values
     * @return array
     */
    private function createAssociativeArrayWithKeysAndValues(array $keys, array $values): array
    {
        $result = [];
        foreach ($values as $line) {
            $result[] = array_combine($keys, $line);
        }

        return $result;
    }

    /**
     * Fonction qui va créer un tableau associatif pour positionner les données des produits dans les bonnes colonnes
     *
     * @param array $array
     * @return array
     */
    private function organizeProductsColumns(array $array): array
    {
        $result = [];
        foreach ($array as $line) {
            $result[] = [
                Headers::Sku->name => $line['sku'],
                Headers::Status->name => ('1' === $line['is_enabled']) ? Status::Enable->name : Status::Disable->name,
                Headers::Price->name => $this->formatPrice($line['price']) . $line['currency'],
                Headers::Description->name => $this->formatDescription($line['description']),
                Headers::CreatedAt->name => $this->formatDate($line['created_at']),
                Headers::Slug->name => $this->formatSlug($line['title']),
            ];
        }

        return $result;
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
     * Fonction qui interprète les balises HTML et les caractères de retour à la ligne
     *
     * @param string $string
     * @return string
     */
    private function formatDescription(string $string): string
    {
        $formattedString = str_replace(['\r\n', '\n', '\r'], "\n", $string);

        return preg_replace('/\<(\s*)?br(\s*)?\/?\>/i', "\n", $formattedString);
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
     * Fonction qui remplace les espaces et caractères spéciaux pour formater la colonne slug et la passer en minuscule
     * @param string $string
     * @return string
     */
    private function formatSlug(string $string) : string
    {
        return strtolower($this->slugger->slug($string));
    }
}
