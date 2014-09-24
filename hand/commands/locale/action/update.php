<?php
namespace Hand\Commands\Locale\Action;

use Hand\Abstracts\Command;

class Update extends Command {

    public function __construct($scan_folders, $locale_folder, $trans_methods = []) {
        $this->scan_folders  = $scan_folders;
        $this->locale_folder = $locale_folder;
        $this->trans_methods = array_merge([
            'locale' => array(
                '@locale\(\s*(\'.*\')\s*\)@U',
                '@locale\(\s*(".*")\s*\)@U',
            ),
        ], $trans_methods);
    }

    public function execute() {
        // Search all folders to create word list by trans_methods pattern
        $word_list = [];
        foreach($this->scan_folders as $path) {
            foreach($this->getPHPandViewFiles($path) as $php_file_path => $dumb) {
                $words = [];
                foreach($this->extractTranslationFromPHPandViewFile($php_file_path) as $k => $v) {
                    $word         = eval("return $k;");
                    $words[$word] = $word;
                }
                $word_list = array_merge($word_list , $words);
            }
        }

        // Loop up locale directory and then update the message file in each langauge
        $locale_folder = $this->locale_folder;
        $job_list      = [];

        foreach (scandir($locale_folder) as $locale) {
            if (in_array($locale , array("." , ".." )) === false) {
                $language_folder = $locale_folder.DIRECTORY_SEPARATOR.$locale;

                if (is_dir($language_folder) === true) {
                    $message_file  = $language_folder.DIRECTORY_SEPARATOR.'/message.php';

                    if (is_dir($locale_folder) === false) {
                        mkdir($locale_folder, 0777, true);
                    }

                    if (is_writable($locale_folder) == false) {
                        $this->fail('The locale directory can not write ('.$locale_folder.')');
                    }

                    if (touch($message_file) === false ) {
                        $this->fail('Unable to touch message file '.$message_file);
                    }

                    if (is_readable($message_file) === false) {
                        $this->fail('Unable to read message file '.$message_file);
                    }

                    if (is_writable($message_file) === false) {
                        $this->fail('Unable to write in message file '.$message_file);
                    }

                    $messages       = include($message_file);
                    $final_messages = [];
                    $old_messages   = is_array($messages) === true ? $messages : [];
                    $new_messages   = $word_list;

                    $outdate_messages = array_diff_key($old_messages , $new_messages);
                    $latest_messages  = array_diff_key($new_messages , $old_messages);
                    $already_messages = array_intersect_key($old_messages , $new_messages);

                    ksort($outdate_messages);
                    ksort($latest_messages);
                    ksort($already_messages);

                    if (count($latest_messages) > 0) {
                        $final_messages = array_merge($final_messages, $latest_messages);
                    }

                    if (count($already_messages) > 0) {
                        $final_messages = array_merge($final_messages, $already_messages);
                    }

                    // Not generate outdated message into message file
                    //
                    // if (count($outdate_messages) > 0) {
                    //     $final_messages = array_merge($final_messages, $outdate_messages);
                    // }

                    if (empty($final_messages) === false) {
                        $content = var_export($final_messages , true);

                        $file_content = "<?php\n";
                        $file_content.= "return ".$content.";";

                        $job_list[$message_file] = $file_content;
                    }
                }
            }
        }

        // Write updated messages to file
        if (count($job_list) > 0) {
            foreach ($job_list as $message_file => $file_content) {
                file_put_contents($message_file , $file_content);
            }
        }
    }

    private function getPHPandViewFiles($path) {
        if (is_dir( $path) === true) {
            return new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path , \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST,
                    \RecursiveIteratorIterator::CATCH_GET_CHILD // Skip permission denied
                ),
                '/^.+\.(php|html)$/i',
                \RecursiveRegexIterator::GET_MATCH
            );
        }else{
            return [];
        }
    }

    private function extractTranslationFromPHPandViewFile($path) {
        $result = [];
        $string = file_get_contents($path);
        foreach($this->array_flatten($this->trans_methods) as $method) {
            preg_match_all($method , $string , $matches);

            foreach ($matches[1] as $k => $v) {
                if (strpos($v , '$') !== false) {
                    unset($matches[1][$k]);
                }

                if (strpos($v , '::') !== false) {
                    unset($matches[1][$k]);
                }
            }

            $result = array_merge($result , array_flip( $matches[1]));
        }

        return $result;
    }

    private function array_flatten($array) {
        $return = [];

        array_walk_recursive($array, function($x) use (&$return) {
            $return[] = $x;
        });

        return $return;
    }

}
