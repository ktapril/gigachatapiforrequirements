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
        $this->authKey = $authKey;
        $this->client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'verify' => false, // Отключаем SSL-проверку
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
                'verify' => false,
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
        $prompt = "найди в тексте признаки, характерные для текстов, написанных ИИ. найди и выпиши: шаблонные фразы (например, 'важно отметить'), общие формулировки без конкретики, повторяющуюся структуру. если таких признаков нет,то напиши: 'признаки ИИ не найдены.' не пиши вводных фраз, только список.";
        try {
            $token = $this->getAccessToken();

            $response = $this->client->post($this->chatUrl, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'json' => [
                    'model' => 'GigaChat',
                    'messages' => [['role' => 'user', 'content' => $prompt . "\n\n" . $text]],
                    'temperature' => 0.1,
                    'stream' => false,
                ],
                'verify' => false,
            ]);

            $result = \json_decode($response->getBody(), true);
            return $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';
        } catch (\Exception $e) {
            return 'ошибка GigaChat: ' . $e->getMessage();
        }
    }


    public function checkForPastTense($text)
    {
        $prompt = "найди в тексте все глаголы в прошедшем времени (например: 'провёл', 'описал', 'проанализировал', 'использовал', 'разработал', 'проверил'). выведи их в списке. если таких глаголов нет, то напиши: 'глаголы в прошедшем времени не найдены.'";

        try {
            $token = $this->getAccessToken();

            $response = $this->client->post($this->chatUrl, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'json' => [
                    'model' => 'GigaChat',
                    'messages' => [['role' => 'user', 'content' => $prompt . "\n\n" . $text]],
                    'temperature' => 0.1,
                    'stream' => false,
                ],
                'verify' => false,
            ]);

            $result = \json_decode($response->getBody(), true);
            return $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';
        } catch (\Exception $e) {
            return 'ошибка GigaChat: ' . $e->getMessage();
        }
    }

    public function checkForIntroduction($text)
    {
        $prompt = "определи, содержит ли введение: цель работы, задачи, актуальность темы, объект и предмет исследования. перечисли, что из этого присутствует. если введение отсутствует или не содержит этих элементов, напиши: 'введение не обнаружено или неполное.'";

        try {
            $token = $this->getAccessToken();

            $response = $this->client->post($this->chatUrl, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'json' => [
                    'model' => 'GigaChat',
                    'messages' => [['role' => 'user', 'content' => $prompt . "\n\n" . $text]],
                    'temperature' => 0.1,
                    'stream' => false,
                ],
                'verify' => false,
            ]);

            $result = \json_decode($response->getBody(), true);
            return $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';
        } catch (\Exception $e) {
            return 'ошибка GigaChat: ' . $e->getMessage();
        }
    }

    public function checkForMainBody($text)
    {
        $prompt = "найди в тексте разделы с заголовками (например: '1. анализ данных', '2. методология', '3. результаты'). перечисли их в порядке следования. если основная часть отсутствует или не имеет структуры с подзаголовками, напиши: 'основная часть без чёткой структуры.'";

        try {
            $token = $this->getAccessToken();

            $response = $this->client->post($this->chatUrl, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'json' => [
                    'model' => 'GigaChat',
                    'messages' => [['role' => 'user', 'content' => $prompt . "\n\n" . $text]],
                    'temperature' => 0.1,
                    'stream' => false,
                ],
                'verify' => false,
            ]);

            $result = \json_decode($response->getBody(), true);
            return $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';
        } catch (\Exception $e) {
            return 'ошибка GigaChat: ' . $e->getMessage();
        }
    }

    public function checkForConclusion($text)
    {
        $prompt = "определи, содержит ли заключение: выводы по цели, итоги по задачам, рекомендации или предложения. перечисли, что из этого есть. если заключение отсутствует, напиши: 'заключение не найдено.'";

        try {
            $token = $this->getAccessToken();

            $response = $this->client->post($this->chatUrl, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'json' => [
                    'model' => 'GigaChat',
                    'messages' => [['role' => 'user', 'content' => $prompt . "\n\n" . $text]],
                    'temperature' => 0.1,
                    'stream' => false,
                ],
                'verify' => false,
            ]);

            $result = \json_decode($response->getBody(), true);
            return $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';
        } catch (\Exception $e) {
            return 'ошибка GigaChat: ' . $e->getMessage();
        }
    }

    public function checkForTableFormatting($text)
    {
        $prompt = "найди в тексте упоминания таблиц (например: 'таблица 1', 'табл. 2', 'см. таблицу ниже'). проверь, есть ли подписи вида 'таблица 1 - название'. выведи список найденных таблиц с подписями. если таблиц нет, напиши: 'таблицы не обнаружены.'";

        try {
            $token = $this->getAccessToken();

            $response = $this->client->post($this->chatUrl, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'json' => [
                    'model' => 'GigaChat',
                    'messages' => [['role' => 'user', 'content' => $prompt . "\n\n" . $text]],
                    'temperature' => 0.1,
                    'stream' => false,
                ],
                'verify' => false,
            ]);

            $result = \json_decode($response->getBody(), true);
            return $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';
        } catch (\Exception $e) {
            return 'ошибка GigaChat: ' . $e->getMessage();
        }
    }

    public function checkForFigureFormatting($text)
    {
        $prompt = "найди в тексте упоминания иллюстраций (например: 'рисунок 1', 'рис. 2', 'см. схему' и т.д.). проверь, есть ли подписи вида 'рисунок 1 - описание'. выведи список. если иллюстраций нет, напиши: 'Иллюстрации не обнаружены.'";

        try {
            $token = $this->getAccessToken();

            $response = $this->client->post($this->chatUrl, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'json' => [
                    'model' => 'GigaChat',
                    'messages' => [['role' => 'user', 'content' => $prompt . "\n\n" . $text]],
                    'temperature' => 0.1,
                    'stream' => false,
                ],
                'verify' => false,
            ]);

            $result = \json_decode($response->getBody(), true);
            return $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';
        } catch (\Exception $e) {
            return 'ошибка GigaChat: ' . $e->getMessage();
        }
    }
}