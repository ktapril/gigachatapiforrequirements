<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GigaChatClient
{
    private $client;
    private $authKey;
    private $tokenUrl = 'https://ngw.devices.sberbank.ru:9443/api/v2/oauth';
    private $chatUrl = 'https://gigachat.devices.sberbank.ru/api/v1/chat/completions';

    public function __construct($authKey)
    {
        $this->authKey = $authKey;
        $this->client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    private function getAccessToken()
    {
        try {
            $response = $this->client->post($this->tokenUrl, [
                'headers' => [
                    'Authorization' => 'Basic ' . $this->authKey,
                    'RqUID' => $this->generateUuid(),
                ],
                'form_params' => [
                    'scope' => 'GIGACHAT_API_PERS',
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            return $result['access_token'];

        } catch (RequestException $e) {
            throw new Exception('Ошибка получения токена: ' . $e->getMessage());
        }
    }

    private function generateUuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function checkForAI($text)
    {
        $prompt = "проанализируй следующий текст и оцени, насколько вероятно, что он был сгенерирован искусственным интеллектом. ответь коротко: 'низкая', 'средняя' или 'высокая' вероятность.";

        try {
            $token = $this->getAccessToken();

            $response = $this->client->post($this->chatUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => [
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt . "\n\n" . $text,
                        ]
                    ],
                    'temperature' => 0.1,
                    'stream' => false,
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            $ai_probability = $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';

            return $ai_probability;

        } catch (Exception $e) {
            return 'ошибка при обращении к GigaChat API: ' . $e->getMessage();
        }
    }
}
