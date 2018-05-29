<?php

namespace Doitonlinemedia\Sentry;

class SentryAPI
{
    public static function setEnvironmentVariable($key, $value){
        $env = base_path()."/".app()->environmentFile();
        $contents = file_get_contents($env);
        $oldValue = env($key);
        file_put_contents($env, strpos($contents, $key) ? str_replace("{$key}={$oldValue}", "{$key}={$value}\n", $contents) : $contents . "\n\n{$key}={$value}\n");
    }

    public static function setExceptionHandler(){
        $handler = app_path('Exceptions/Handler.php');
        $contents = file_get_contents($handler);
        $handlerCode = "\t\tif (app()->bound('sentry')".' && $this->shouldReport($exception)) {' . "\n". "\t\t\t".'app(\'sentry\')->captureException($exception);' . "\n" ."\t\t".'}'."\n";

        if(strpos($contents, $handlerCode) !== false) return 'Exception handler is already set!';

        $explode = explode(' ', substr($contents, strpos($contents, 'public function report(Exception $exception)')));
        $explode[7] .= $handlerCode;
        file_put_contents($handler, explode('public function report(Exception $exception)', $contents)[0].= implode(' ', $explode));
        return true;
    }

    public static function createProject($name){
        self::cURLRequest("https://sentry.io/api/0/teams/jesper-menting/jesper-menting/projects/", "POST", "{\"name\": \" Laravel_" . $name ."\", \"platform\": \"php-laravel\"}");
        self::cURLRequest("https://sentry.io/api/0/teams/jesper-menting/jesper-menting/projects/", "POST", "{\"name\": \" Javascript_" . $name ."\", \"platform\": \"javascript\"}");
    }

    public static function getIssues($type){
        return json_decode(self::cURLRequest("https://sentry.io/api/0/projects/" .config('sentry.team_name'). "/" .$type. "_" .config('sentry.project_name'). "/issues/", "GET"));
    }

    public static function install($project){
        $response = self::cURLRequest("https://sentry.io/api/0/projects/jesper-menting/".$project."/keys/", "GET");
        $dsn = json_decode($response)[0]->dsn;
        if(strpos($project, 'laravel_') !== false){
            SentryAPI::setEnvironmentVariable('SENTRY_LARAVEL_DSN', $dsn->secret);
            SentryAPI::setExceptionHandler();
        }else if(strpos($project, 'javascript_') !== false){
            SentryAPI::setJsScript($dsn->public);
        }
    }

    public static function setJsScript($dsn){
        $ravenJs = '<script src="https://cdn.ravenjs.com/3.25.2/raven.min.js" crossorigin="anonymous"></script>';
        $ravenConfig = '<script>Raven.config("' . $dsn . '").install();</script>';
        $file = fopen(config_path('raven.php'), 'w');
        fwrite($file, "<?php" . "\n\n" .  'return array(' . "\n\t" . "'ravenJs' => '" . $ravenJs . "', \n\t'ravenConfig' => '" . $ravenConfig . "', \n );");
        fclose($file);
    }

    private static function cURLRequest($url, $method, $field = null){
        $curl = curl_init();
        $setopt_array = array(CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . config('sentry.bearer_token'),
                "Cache-Control: no-cache",
                "Content-Type: application/json"
            ),
        );

        if($field != null) $setopt_array[CURLOPT_POSTFIELDS] = $field;
        curl_setopt_array($curl, $setopt_array);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) return "cURL Error #:" . $err;
        return $response;
    }

}
