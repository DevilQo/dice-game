<?php
namespace App\Console\Commands;

use App\Models\WeatherRecordVariableMappings;
use App\Models\WeatherStation;
use Illuminate\Console\Command;
use App\Models\WeatherProvider;
use App\Models\WeatherRecordVariableMapping;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class FetchWeatherData extends Command
{
    // Názov commandu pre cronjob
    protected $signature = 'weather:fetch';

    // Popis
    protected $description = 'Fetch weather data for all providers and store relevant data';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Načítanie všetkých poskytovateľov počasia
        $providers = WeatherProvider::with('attributes.propertyDefinition')->get();
        $stations = WeatherStation::get();

        foreach ($providers as $provider) {
            // Vyhľadaj base_url z atribútov
            $baseUrlAttribute = $provider->attributes->first(function ($attribute) {
                return $attribute->propertyDefinition->key_name === 'base_url';
            });

            if (!$baseUrlAttribute) {
                $this->error('Base URL not found for provider ID ' . $provider->id);
                continue;
            }

            $baseUrl = $baseUrlAttribute->value;

            // Priprav query parametre pre API
            $queryParams = [];
            // Pre každú stanicu nahradíme GPS súradnice a vykonáme API request
            foreach ($stations as $station) {
                foreach ($provider->attributes as $attribute) {
                    if ($attribute->propertyDefinition->key_name === 'lat') {
                        $queryParams['lat'] = $station->latitude;  // Dynamicky nastavíme lat
                    } elseif ($attribute->propertyDefinition->key_name === 'lon') {
                        $queryParams['lon'] = $station->longitude;  // Dynamicky nastavíme lon
                    } else {
                        $queryParams[$attribute->propertyDefinition->key_name] = $attribute->value;
                    }
                }

                // Skladanie URL a volanie API
                $url = $this->buildUrl($baseUrl, $queryParams);
                $response = $this->callWeatherApi($url);

                // Ak volanie API bolo úspešné, parsuj len relevantné dáta
                if ($response) {
                    $this->parseAndStoreData($response, $provider->id, $station->id);
                }
            }
        }

        $this->info('Weather data fetched and stored successfully.');
    }

    private function buildUrl($baseUrl, $queryParams)
    {
        // Vytvorenie query stringu
        $queryString = http_build_query($queryParams);
        return $baseUrl . '?' . $queryString;
    }

    private function callWeatherApi($url)
    {
        // GET požiadavka na API
        $response = \Http::get($url);

        if ($response->successful()) {
            return $response->json(); // Vráti JSON response
        } else {
            $this->error('Failed to fetch weather data from URL: ' . $url);
            return null;
        }
    }

    private function parseAndStoreData($response, $providerId, $stationId)
    {
        // Načítaj mapovania z tabulky weather_record_variable_mappings
        $mappings = WeatherRecordVariableMappings::with('variableDefinition')->get();

        // Pre aktuálny timestamp
        $currentTimestamp = now()->timestamp;

        foreach ($mappings as $mapping) {
            // Získaj JSON path (napr. 'hourly.wind_speed')
            $jsonPath = $mapping->provider_variable_name;

            // Použi Arr::get() na extrahovanie hodnoty z response
            $value = Arr::get($response, $jsonPath);

            if ($value === null) {
                $pathParts = explode('.', $jsonPath);
                $firstPart = $pathParts[0];
                $parseValue = Arr::get($response, $firstPart);
//                if ($parseValue !== null) {
//                    $item = collect($response[$firstPart])->first(function ($item) use ($currentTimestamp) {
//                        return isset($item['dt']) && $item['dt'] <= $currentTimestamp && $currentTimestamp < $item['dt'] + 3600;
//                    });
//
//                    if ($item) {
//                        // Tu získaš špecifický kľúč, napríklad wind_speed
//                        $value = Arr::get($item, $pathParts[1]);  // Vezme hodnotu z druhého kľúča v path (napr. wind_speed)
//                    }
//                }
                if ($parseValue !== null) {
                    // Prejdi cez všetky hodiny alebo minúty a vyhľadaj zhodu na základe času (dt)
                    $item = collect($parseValue)->first(function ($item) use ($currentTimestamp) {
                        return isset($item['dt']) && $item['dt'] <= $currentTimestamp && $currentTimestamp < $item['dt'] + 3600;
                    });

                    if ($item) {
                        // Prejdeme cez zvyšné časti cesty, vrátane číselných hodnôt ako '1h'
                        $value = $item;
                        foreach (array_slice($pathParts, 1) as $part) {
                            // Skontroluj, či existuje kľúč v objekte
                            if (isset($value[$part])) {
                                $value = $value[$part];
                            } else {
                                // Ak kľúč neexistuje, nastav na null a ukonči slučku
                                $value = null;
                                break;
                            }
                        }
                    }
                }
            }
            if ($value !== null) {
                // Ulož dáta do databázy (predpokladáme tabulku weather_records)
                DB::table('weather_records')->insert([
                    'weather_provider_id' => $providerId,
                    'weather_station_id' => $stationId,
                    'variable_definition_id' => $mapping->variable_definition_id,
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->info("Stored data for {$mapping->variableDefinition->name}: $value");
            } else {
                $this->warn("No data found for {$mapping->variableDefinition->name}");
            }
        }
    }
}
