<?php

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * AIMod AI Content-Generator
 *
 * @package AIMod
 * @author Elias Ritter
 * @license GNU General Public license 2.0 or later
 *
 * @var object $params
 * @var object $module
 */

require_once(__DIR__ . '/api/api.php');

$cache_dir = __DIR__ . '/../../cache/aimod_cache';

if(!is_dir($cache_dir)) mkdir($cache_dir);

$application = JFactory::getApplication();

if(empty($params->get('authorization'))) {
    $application->enqueueMessage('AIModule: You need to provide a valid AI Authorization Key to use this Module', 'warning');
    return;
}

$creds = array(
    'AUTHORIZATION' => $params->get('authorization'),
    #'ORGANIZATION' => $params->get('organization')
);

if(!class_exists('requestHelper')) {

    /**
     * AiMod Controller / Helper
     *
     * @package AIMod
     * @author Elias Ritter
     * @license GNU General Public license 2.0 or later
     */
    class requestHelper {

        var $params;
        var $id;
        var $cache;
        var $filename;
        var $return;
        var $creds;

        public function __construct($cachedir, $module, $params, $creds)
        {
            $this->cache = $cachedir;

            try {
                $this->return             = new stdClass();
                $this->return->written    = 0;
                $this->return->request    = '';
                $this->return->response   = '';

                $this->creds = $creds;
                $this->id = $module->id;

                if(empty($this->id)) return;

                $this->params = $params;

                $this->filename = $this->cache . '/cached_'.$this->id . '.json';

                if(!file_exists($this->filename) && !empty($params->get('content'))) {
                    $this->CreateNewResponse();
                    $this->cacheResponse();
                } else {
                    $this->getCachedResponse();
                }
            } catch (Throwable $t) {
                global $application;
                $application->enqueueMessage('AIModule: ' . $t->getMessage(), 'error');
            }
        }

        private function CreateNewResponse(): void
        {
            /** @noinspection PhpUndefinedClassInspection */
            $session                  = new openAI_Interface($this->creds);
            $text                     = $session->createText($this->params->get('content'));

            $this->return->written    = time();
            $this->return->response   = static::removeMisplacedChars($text, array('.'));
            $this->return->request    = $this->params->get('content');
        }

        private static function removeMisplacedChars($text, $filter): string
        {
            $text = trim($text);
            if(in_array(mb_substr($text, 0, 1), $filter)) {
                return mb_substr($text, 1);
            } else {
                return $text;
            }
        }

        private function cacheResponse(): void
        {
            if($stream = fopen($this->filename, "w")) {

                $write              = new stdClass();
                $write->written     = $this->return->written;
                $write->response    = $this->return->response;
                $write->request     = $this->return->request;

                $encoder = function(&$string) {
                    if(is_int($string)) return;
                    $string = base64_encode(gzdeflate($string, 9));
                };

                array_walk($write, $encoder);
                fwrite($stream, json_encode($write));
                fclose($stream);
            }
        }

        private function getCachedResponse(): void
        {
            $return = json_decode(file_get_contents($this->filename));

            $decoder = function(&$string) {
                if(is_int($string)) return;
                $string = gzinflate(base64_decode($string));
            };

            array_walk($return, $decoder);

            if($return->request !== $this->params->get('content')) {
                unlink($this->filename);
                $this->CreateNewResponse();
                $this->cacheResponse();
            } else {
                $this->return = $return;
            }
        }

        public function getReturn(): object
        {
            return $this->return;
        }
    }
}

$session = new requestHelper($cache_dir, $module, $params, $creds);
$return = $session->getReturn();

require JModuleHelper::getLayoutPath('mod_aimod');