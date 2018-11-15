<?php

namespace Schoenef\HereApiConnectorBundle\Service;


use GuzzleHttp\Client;
use Schoenef\HereApiConnectorBundle\DependencyInjection\Configuration;

class HereApiConnector {

    const COUNTRY_MAP = [
        'DE' => 'DEU'
    ];

    private $config;

    private $autocompleteClient;
    private $geocoderClient;

    private $lang;
    private $country;
    private $app_id;
    private $app_code;

    public function __construct(array $connectorConfig){
        $this->config = $connectorConfig;

        $this->geocoderClient = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://geocoder.api.here.com/6.2/',
            // You can set any number of default request options.
            'timeout'  => $this->config[Configuration::KEY_TIMEOUT],
        ]);

        $this->autocompleteClient = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://autocomplete.geocoder.api.here.com/6.2/',
            // You can set any number of default request options.
            'timeout'  => $this->config[Configuration::KEY_TIMEOUT],
        ]);

        $this->lang = $this->config[Configuration::KEY_LANG];
        $this->country = $this->config[Configuration::KEY_COUNTRY];
        $this->app_id = $this->config[Configuration::KEY_APP_ID];
        $this->app_code = $this->config[Configuration::KEY_APP_CODE];
    }


    /**
     * this function will convert the HereApi result to geojson http://geojson.org/
     *
     * @param $query
     * @param array $options allows to define additional parameters to the call
     * @param array $filter the filter allows to reduce the results to certain types
     * @return array|bool
     * @throws \Exception
     */
    public function searchLocation ($query, $options = [], $filter = []) {

        $options = $this->getStandardOptions($options);

        if ($this->country) {

            $country = $this->country;

            if (strlen($country) === 2 && !array_key_exists($country, self::COUNTRY_MAP)) {
                throw new \Exception("Please use iso 3 country codes for the " . Configuration::KEY_COUNTRY . " parameter or create a PR with the apropriate mapping - $country can not be mapped at the moment.");
            } else if (strlen($country) === 2) {
                $country = self::COUNTRY_MAP[$country];
            }

            $options['country'] = $country;
        }

        $options['query'] = $query;

        $response = $this->autocompleteClient->request('GET', 'suggest.json',['query' => $options]);
        if ($response->getStatusCode() == '200') {
            return $this->filterResult(json_decode($response->getBody()->getContents(), true)['suggestions'],$filter);
        }

        return false;
    }

    /**
     * pulls additional geo informtaion for a result
     *
     * @param array $result
     * @return array
     */
    public function getLocationDetails (array $result) {
        $options = $this->getStandardOptions();

        $options['locationid'] = $result['properties']['id'];
        $options['gen'] = 9; // hardcode to generation 9
        $options['jsonattributes'] = 1; // to have a unified api - first letter is forced to be lowercase

        $response = $this->geocoderClient->request('GET', 'geocode.json',['query' => $options]);

        if ($response->getStatusCode() == '200') {

            $extendedResult = json_decode($response->getBody()->getContents(), true)['response']['view'][0]['result'][0];

            if ($extendedResult) {

                $geo = $extendedResult['location']['displayPosition'];

                // enrich the information with the lon/lat array
                $result['geometry']['coordinates'] = [$geo['longitude'], $geo['latitude']];

                // we take the much more beautifull label as well
                $result['properties']['label'] = $extendedResult['location']['address']['label'];
            }

        }

        return $result;
    }


    public function filterResult ($hitsArray, $filter = []) {

        if (count($filter)) {
            foreach ($filter as $key => $allowedValues) {
                $filteredResult = [];
                foreach ($hitsArray as $entry) {

                    $entry = $this->convertToGeoJSON($entry);

                    if (array_key_exists($key, $entry['properties']) && in_array($entry['properties'][$key], $allowedValues)) {
                        $filteredResult[] = $entry;
                    }
                }

                $hitsArray = $filteredResult;
            }
        } else {
            // we still need to convert this into geoJson ;)
            foreach ($hitsArray as $index => $entry) {
                $hitsArray[$index] = $this->convertToGeoJSON($entry);
            }
        }

        return $hitsArray;
    }

    public function convertToGeoJSON ($entry) {
        $geoEntry = [
            "type" => "Feature",
            "geometry" => [
                "type" => "Point",
                "coordinates" => []
            ],
            "properties" => []
        ];

        foreach ($entry as $attr => $val) {
            switch ($attr) {
                default:
                    $geoEntry['properties'][$attr] = $val;
                    break;
                case 'locationId':
                    $geoEntry['properties']['id'] = $val;
                    break;
                case 'address':
                    foreach ($val as $addressKey => $addressVal) {
                        $geoEntry['properties'][$addressKey] = $addressVal;

                        if ($addressKey === 'city') {
                            $geoEntry['properties']['name'] = $addressVal;
                        }
                    }
                    break;
            }
        }

        return $geoEntry;
    }


    protected function getStandardOptions ($options = []) {
        $options['app_id'] = $this->app_id;
        $options['app_code'] = $this->app_code;

        if ($this->lang) {
            $options['language'] = $this->lang;
        }

        return $options;
    }

}