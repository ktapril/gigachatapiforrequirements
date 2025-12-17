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
            ]);

            $result = \json_decode($response->getBody(), true);

            $ai_probability = $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';

            return $ai_probability;

        } catch (\Exception $e) {
            return 'ошибка при обращении к GigaChat API: ' . $e->getMessage();
        }
    }

    public function checkForRequirements($text)
    {
        $requirementsConfig = json_decode(file_get_contents('../config/requirements.json'), true);

        $requirementTexts = [];
        if ($requirementsConfig['uses_specialized_terminology']) {
            $requirementTexts[] = 'использование специальной терминологии.';
        }
        if ($requirementsConfig['no_impersonal_style']) {
            $requirementTexts[] = 'избегать безличных конструкций (например, “считается”, “принято считать”).';
        }
        if ($requirementsConfig['verbs_in_past_tense_for_work_description']) {
            $requirementTexts[] = 'глаголы в прошедшем времени (для описания проделанной работы).';
        }
        if ($requirementsConfig['has_introduction_with_goals_tasks']) {
            $requirementTexts[] = 'наличие введения с целями и задачами.';
        }
        if ($requirementsConfig['has_main_body_with_specific_sections']) {
            $requirementTexts[] = 'наличие основной части с определёнными разделами.';
        }
        if ($requirementsConfig['has_conclusion_with_results_and_up_to_5_proposals']) {
            $requirementTexts[] = 'наличие заключения с результатами и до 3–5 предложений.';
        }

        $requirementsList = implode("\n- ", $requirementTexts);
        $prompt = "проверь, соответствует ли следующий текст этим требованиям:\n- $requirementsList\n\nесли какие-то требования не соблюдены - укажи, где именно и как исправить.";

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

            $requirements_check = $result['choices'][0]['message']['content'] ?? 'не удалось получить ответ';

            return $requirements_check;

        } catch (\Exception $e) {
            return 'ошибка при обращении к GigaChat API: ' . $e->getMessage();
        }
    }
}