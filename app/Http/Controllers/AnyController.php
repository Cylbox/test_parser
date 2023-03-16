<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use JetBrains\PhpStorm\NoReturn;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Symfony\Component\DomCrawler\Crawler;

class AnyController extends Controller
{
    public function __invoke() {
        $mainUrl = 'https://ru.tradingeconomics.com/country-list/population';

        $arrayAll = $this->getArrayFromUrl($mainUrl);
        $queries = $this->getQueriesArray($mainUrl);

        foreach ($queries as $query){
            $arrayAll = array_merge($this->getArrayFromUrl($mainUrl . $query), $arrayAll);
        }

        $jsonAll = json_encode(array_unique($arrayAll));

        return dd($jsonAll);
    }

    private function getCrawlerFromUrl($url): Crawler
    {
        $client = new Client();
        $response = $client->request('GET', $url);
        $html = $response->getBody()->getContents();

        return new Crawler($html);
    }

    private function getQueriesArray($url): array
    {
        $crawler = $this->getCrawlerFromUrl($url);
        $queries = $crawler->filter('ul#pagemenutabs li a');
        $queriesArray = array();

        foreach ($queries as $query) {
            if ($query->getAttribute('href')) {
                $queriesArray[] = $query->getAttribute('href');
            }
            else continue;
        }

        return $queriesArray;
    }

    private function getArrayFromUrl($url): array
    {
        $crawler = $this->getCrawlerFromUrl($url);
        $keys = ['key','value','last','date','block'];

        $tableRows = $crawler->filter('table tr')->each(function (Crawler $node, $i) use ($keys) {
            $row = $node->filter('td')->each(function (Crawler $node, $i) {
                if (preg_match('~^\d{4}\-(0[1-9]|1[012])$~', $node->text()))
                    return date("d-m-Y", strtotime($node->text() . '-01'));
                else if (preg_match('~[0-9]+~', $node->text()))
                    return $node->text();
                else return GoogleTranslate::trans($node->text());
            });
            if (count($row)>0)
                return json_encode(array_combine($keys, $row));
        });

        unset($tableRows[0]);

        return $tableRows;
    }
}
