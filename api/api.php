<?php

defined('_JEXEC') or die;

/**
 * AiMod AI Interface
 *
 * @package AIMod
 * @author Elias Ritter
 * @license GNU General Public license 2.0 or later
 */

class OpenAI_Interface
{
    /**
     * @var string Der Zugang zur Completions-API zur Interaktion mit Text
     */
    private const TEXT_API = 'https://api.openai.com/v1/completions';

    /**
     * @var array
     */
    private $config;

    public $app;

    function __construct($creds)
    {
        $this->app = JFactory::getApplication();
        $this->config = $creds;
    }

    /**
     * Führt die Anfragen an die OpenAI-API durch
     *
     * @param string $url Die API-URL
     * @param string $data Die zu übertragenden Daten als JSON
     * @return bool|object
     */
    private function curl(string $url, string $data)
    {
        $headers = [
            "Cache-Control: no-cache",
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->config['AUTHORIZATION']
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        if($return = curl_exec($ch)) {
            curl_close($ch);
            return json_decode($return, false);
        } else {
            return null;
        }
    }

    /**
     * Erzeugt eine beliebige Konversation
     *
     * @param string $text Die zu übertragende Nachricht
     * @return string|void
     */
    public function createText(string $text)
    {
        $error = 'An Error occured. Please try to shorten your AI-Request Text';
        $data = [
            "model" => 'text-davinci-003',
            "prompt" => $text,
            "temperature" => 0,
            "max_tokens" => 800,
            "top_p" => 1.0,
            "frequency_penalty" => 0,
            "presence_penalty" => 0
        ];
        $a = $this->curl(constant('self::TEXT_API'), json_encode($data));
        return trim($a->choices[0]->text) ?? $error;
    }
}