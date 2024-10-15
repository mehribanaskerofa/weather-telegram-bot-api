<?php
require 'vendor/autoload.php';
require 'config.php';

use GuzzleHttp\Client;



$client = new Client();

function getWeather($client, $city) {
    $url = WEATHER_API_URL . "?q={$city}&appid=" . WEATHER_API_KEY . "&units=metric&lang=ru";

    try {
        $response = $client->request('GET', $url);
        $data = json_decode($response->getBody(), true);

     
        if (isset($data['cod']) && $data['cod'] === 200) {
            $temperature = $data['main']['temp'];
            $humidity = $data['main']['humidity'];
            $description = ucfirst($data['weather'][0]['description']);
            return "Температура: {$temperature}°C\nВлажность: {$humidity}%\nОписание: {$description}";

        } else {
        
            return "Город '{$city}' не найден. Пожалуйста, проверьте введенное название города и попробуйте снова.";
        }
    } catch (\GuzzleHttp\Exception\RequestException $e) {
       
        return "Произошла ошибка при получении данных о погоде. Пожалуйста, попробуйте позже.";
    } catch (\Exception $e) {
     
        return "Произошла ошибка. Пожалуйста, попробуйте позже.";
    }
}

function sendTelegramMessage($client, $chatId, $message) {
    $params = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML', 
    ];

    try {
        $client->post(TELEGRAM_API_URL, ['json' => $params]);
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        error_log('Ошибка при отправке сообщения: ' . $e->getMessage());
    }
}

$update = json_decode(file_get_contents('php://input'), true);

if (isset($update['message'])) {
    $chatId = $update['message']['chat']['id'];
    $userInput = trim($update['message']['text']); 

    
    if (preg_match('/^\/\w+/', $userInput)) {
        sendTelegramMessage($client, $chatId, "Привет! Пожалуйста, введите название города для получения погоды.");
    } else {
        if (!empty($userInput)) {
            $weatherInfo = getWeather($client, $userInput);
            sendTelegramMessage($client, $chatId, $weatherInfo);
        } else {
            sendTelegramMessage($client, $chatId, "Пожалуйста, введите название города.");
        }
    }
}
?>
