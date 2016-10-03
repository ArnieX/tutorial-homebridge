<?php
include('forecast.io.php');
$api_key = 'ZDE_VLOŽTE_SVŮJ_API_KLÍČ_Z_DARKSKY_API';
$latitude = '50.085403';   //ZDE ZADEJTE POLOHU
$longitude = '14.422086';
$units = 'auto';
$lang = 'cs';
$forecast = new ForecastIO($api_key, $units, $lang);

$condition = $forecast->getCurrentConditions($latitude, $longitude);

$humidity = $condition->getHumidity();
$temperature = $condition->getTemperature();

echo('{"temperature": '.$temperature.',"humidity": '.round((float)$humidity * 100 ).'}');
?>