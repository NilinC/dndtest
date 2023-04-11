<?php

namespace App\Formatter;

use App\Model\Headers;
use App\Model\Status;
use Symfony\Component\String\Slugger\SluggerInterface;

class Formatter
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    /**
     * Cette fonction permet de transformer un tableau associatif en simple tableau
     *
     * @param array $array
     * @return array
     */
    public function flatten(array $array): array
    {
        $flattenArray = [];

        array_walk_recursive($array, function ($value) use (&$flattenArray) {
            $flattenArray[] = $value;
        });

        return $flattenArray;
    }

    /**
     * Fonction qui va créer un tableau associatif pour positionner les données des produits dans les bonnes colonnes
     *
     * @param array $array
     * @return array
     */
    public function createProductsRows(array $array, Formatter $formatter): array
    {
        $result = [];
        foreach ($array as $line) {
            $result[] = [
                Headers::Sku->name => $line['sku'],
                Headers::Status->name => ('1' === $line['is_enabled']) ? Status::Enable->name : Status::Disable->name,
                Headers::Price->name => $formatter->formatPrice($line['price']) . $line['currency'],
                Headers::Description->name => $formatter->formatDescription($line['description']),
                Headers::CreatedAt->name => $formatter->formatDate($line['created_at']),
                Headers::Slug->name => $formatter->formatSlug($line['title']),
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
    public function formatPrice(string $string): string
    {
        return number_format($string, 2, ",", null);
    }

    /**
     * Fonction qui interprète les balises HTML et les caractères de retour à la ligne
     *
     * @param string $string
     * @return string
     */
    public function formatDescription(string $string): string
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
    public function formatDate(string $string): string
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
    public function formatSlug(string $string) : string
    {
        return strtolower($this->slugger->slug($string));
    }
}
