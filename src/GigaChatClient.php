<?php

namespace App;

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
        // Отключаем проверку SSL-сертификатов
        $this->client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'verify' => false, // <-- Отключение проверки SSL
        ]);
        $this->authKey = $authKey;
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
                'verify' => false, // <-- Отключение проверки SSL
            ]);

            $result = \json_decode($response->getBody(), true);

            return $result['access_token'];

        } catch (RequestException $e) {
            throw new \Exception('ошибка получения токена: ' . $e->getMessage());
        }
    }

    private function generateUuid()
    {
        return \sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            \mt_rand(0, 0xffff), \mt_rand(0, 0xffff),
            \mt_rand(0, 0xffff),
            \mt_rand(0, 0x0fff) | 0x4000,
            \mt_rand(0, 0x3fff) | 0x8000,
            \mt_rand(0, 0x3fff) | 0x8000,
            \mt_rand(0, 0xffff), \mt_rand(0, 0xffff), \mt_rand(0, 0xffff)
        );
    }

    public function checkForAI($text)
    {
        $prompt = "проанализируй следующий текст и оцени, насколько вероятно, что он был сгенерирован искусственным интеллектом. ответь коротко: 'низкая', 'средняя' или 'высокая' вероятность";

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
                'verify' => false, // <-- Отключение проверки SSL
            ]);

            $result = \json_decode($response->getBody(), true);

            $ai_probability = $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';

            return $ai_probability;

        } catch (\Exception $e) {
            return 'ошибка при обращении к GigaChat API: ' . $e->getMessage();
        }
    }

    public function checkForImpersonalStyle($text)
    {
        $prompt = "проверь, избегает ли текст безличных конструкций (например, 'считается', 'принято считать', 'говорится', 'отмечается'). если такие конструкции есть — укажи, где именно.";

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
                'verify' => false, // <-- Отключение проверки SSL
            ]);

            $result = \json_decode($response->getBody(), true);

            $check_result = $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';

            return $check_result;

        } catch (\Exception $e) {
            return 'ошибка при обращении к GigaChat API: ' . $e->getMessage();
        }
    }

    public function checkForPastTense($text)
    {
        $prompt = "проверь, используются ли в тексте глаголы в прошедшем времени (для описания проделанной работы). если нет — укажи, где это нарушено.";

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
                'verify' => false, // <-- Отключение проверки SSL
            ]);

            $result = \json_decode($response->getBody(), true);

            $check_result = $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';

            return $check_result;

        } catch (\Exception $e) {
            return 'ошибка при обращении к GigaChat API: ' . $e->getMessage();
        }
    }

    public function checkForIntroduction($text)
    {
        $prompt = "проверь, есть ли в тексте введение с целями и задачами. если нет — укажи, где это нарушено.";

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
                'verify' => false, // <-- Отключение проверки SSL
            ]);

            $result = \json_decode($response->getBody(), true);

            $check_result = $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';

            return $check_result;

        } catch (\Exception $e) {
            return 'ошибка при обращении к GigaChat API: ' . $e->getMessage();
        }
    }

    public function checkForMainBody($text)
    {
        $prompt = "проверь, есть ли в тексте основная часть с определёнными разделами. если нет — укажи, где это нарушено.";

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
                'verify' => false, // <-- Отключение проверки SSL
            ]);

            $result = \json_decode($response->getBody(), true);

            $check_result = $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';

            return $check_result;

        } catch (\Exception $e) {
            return 'ошибка при обращении к GigaChat API: ' . $e->getMessage();
        }
    }

    public function checkForConclusion($text)
    {
        $prompt = "проверь, есть ли в тексте заключение с результатами и до 3–5 предложений. если нет — укажи, где это нарушено.";

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
                'verify' => false, // <-- Отключение проверки SSL
            ]);

            $result = \json_decode($response->getBody(), true);

            $check_result = $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';

            return $check_result;

        } catch (\Exception $e) {
            return 'ошибка при обращении к GigaChat API: ' . $e->getMessage();
        }
    }

    public function checkForTableFormatting($text)
    {
        $prompt = "проверь, правильно ли оформлены таблицы в тексте. если есть нарушения — укажи, где именно.";

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
                'verify' => false, // <-- Отключение проверки SSL
            ]);

            $result = \json_decode($response->getBody(), true);

            $check_result = $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';

            return $check_result;

        } catch (\Exception $e) {
            return 'ошибка при обращении к GigaChat API: ' . $e->getMessage();
        }
    }

    public function checkForFigureFormatting($text)
    {
        $prompt = "проверь, правильно ли оформлены иллюстрации в тексте. если есть нарушения — укажи, где именно.";

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
                'verify' => false, // <-- Отключение проверки SSL
            ]);

            $result = \json_decode($response->getBody(), true);

            $check_result = $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';

            return $check_result;

        } catch (\Exception $e) {
            return 'ошибка при обращении к GigaChat API: ' . $e->getMessage();
        }
    }
}