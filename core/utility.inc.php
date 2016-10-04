<?php
define('ASCII_US', "\x1f");

function _v(&$a, $k, $default)
{
    if (isset($a[$k])) {
        return $a[$k];
    }

    return $default;
}

function s_v(&$a, $k, $v)
{
    $a[$k] = $v;
}

function gv($k, $default = null)
{
    return _v($_GET, $k, $default);
}

function pv($k, $default = null)
{
    global $action;
    if (isset($_SESSION['pending_post']) && $action !== 'login') {
        error_log('restoring pending post');
        //when restoring a pending post, you can lose the current post data. data is now appended such that current post data is used only if it doesn't override pending post
        //this may need to be changed if you want the logic to be use current form data, plus any old data
        $_POST = $_SESSION['pending_post'] + $_POST;
        unset($_SESSION['pending_post']);
    }

    return _v($_POST, $k, $default);
}

function sv($k, $default = null)
{
    return _v($_SESSION, $k, $default);
}

function ssv($k, $v)
{
    $_SESSION[$k] = $v;
}

function redirect($url = null)
{
    if (empty($url)) {
        $url = Config::get('baseurl');
    }

    if (stripos($url, '/') === 0) {
        $url = trim(Config::get('baseurl'), '/') . $url;
    }
    ob_end_clean();

    header('Location: ' . $url);
    exit();
}

function redirect_get($get = null)
{
    if (empty($get)) {
        $get = &$_GET;
    }
    if (empty($get['action'])) {
        redirect(Config::get('baseurl'));
    }
    $action = $get['action'];
    unset($get['action']);
    $url = rtrim(Config::get('baseurl'), '/') . "/$action";
    foreach ($get as $k => $v) {
        $url .= '/' . urlencode($k) . '/' . urlencode($v);
    }
    redirect($url);
}

/* Use this instead of readfile if you need to pull content off
  of another server (e.g. CLF parts) and the server this app is
  on has fopen_wrappers disabled
 */

function _readfile($url)
{
    $defaults = [
        CURLOPT_URL => $url,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 4
    ];

    $ch = curl_init();
    curl_setopt_array($ch, $defaults);
    if (!$result = curl_exec($ch)) {
        trigger_error(curl_error($ch));
    }
    curl_close($ch);
    echo $result;
}

function parse_query_string()
{
    if (isset($_GET['q'])) {
        $qs = rtrim($_GET['q']);
        unset($_GET['q']);
        $_GET['action'] = strtok($qs, '/');
        while ($k = strtok('/')) {
            $v = strtok('/');
            $_GET[$k] = $v;
        }
    }
}

function render_normal($action, $template = 'view', $template_data = null)
{
    //note, $template may contain directory separator
    if (!$template) {
        $template = 'view';
    }
    if ($template === 'json') {
        header('Content-Type: application/json');
        echo $template_data['json'];
    } else if ($template === 'connect') {
        header('Content-Type: application/json');
        echo $template_data['json'];
    } else if ($template === 'docstore') {
        header('Content-Description: DocStore File Access');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=' . $template_data["name"]);
        header('Expires: 0');
        header('Cache-Control: no-store, must-revalidate');
        echo $template_data['pdf'];
    } else if ($template === 'pickslips') {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=' . $template_data["name"]);
        header('Expires: 100');
        echo $template_data['url'];
    } else {
        require(Config::get('approot') . '/view/_theme/' . Config::get('theme') . '/header_footer.inc.php');

        $view_dir = Config::get('approot') . '/view/' . $action . '/';
        $includes_dir = Config::get('approot') . '/view/_includes/';
        $view_file = $view_dir . $template . '.twig.html';

        theme_header(1);
        $title = Config::get('app_display_name');
        $subtitle = ucfirst($action);

        if (isset($template_data['_titletag'])) {
            $subtitle = $template_data['_titletag'];
        }

        echo "<title>$subtitle :: $title</title>";

        if (file_exists($view_dir . '/style.css')) {
            echo '<link rel="stylesheet" href="/staticfile/view/' . $action . '/res/style.css" />';
        }

        if (file_exists($view_dir . '/script-head.js')) {
            echo '<script src="/staticfile/view/' . $action . '/res/script-head.js" /></script>';
        }

        echo '<link href="//fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,400,600,300"  rel="stylesheet" type="text/css">';
        echo '<link href="//fonts.googleapis.com/css?family=Source+Code+Pro:200,300"                    rel="stylesheet" type="text/css">';
        echo '<link href="//cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css"                    rel="stylesheet" type="text/css">';
        echo '<link href="' . Config::get("baseurl") . 'css/font-awesome/css/font-awesome.min.css"          rel="stylesheet" type="text/css" />';
        echo '<link href="' . Config::get("baseurl") . 'css/jquery.qtip.min.css"                            rel="stylesheet" type="text/css" />';
        echo '<link href="' . Config::get("baseurl") . 'css/ui/1.10.4/themes/smoothness/jquery-ui.css"      rel="stylesheet" type="text/css" />';
        echo '<link href="' . Config::get("baseurl") . 'css/style.css"                                      rel="stylesheet" type="text/css" />';


        echo '<script src="' . Config::get('baseurl') . 'js/jquery_plugins/jquery.foundation.reveal.js"></script>';
        echo '<script src="' . Config::get('baseurl') . 'js/jquery_plugins/jquery.qtip.min.js"></script>';
        echo '<script src="' . Config::get('baseurl') . 'js/script-head.js"></script>';
        echo '<script src="' . Config::get('baseurl') . 'js/cr-staff.js"></script>';

        # datatables related
        echo '<script src="//cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>';

        theme_header(2);

        if (sv('message')) {
            echo '<p id="smessage">' . sv('message') . '</p>';
            ssv('message', null);
        }
        Twig_Autoloader::register();

        if (file_exists($view_file)) {
            $loader = new Twig_Loader_Filesystem([
                $view_dir,
                $includes_dir]);
            echo Config::get('env');
            $twig = new Twig_Environment($loader, [
                'cache' => ((Config::get('environment') === 'production') ? Config::get('approot') . '/cache' : false),
                'debug' => ((Config::get('environment') === 'production') ? false : true)
            ]);
            /*  'cache' => Config::get('approot') . '/cache',  */

            //access in all templates
            $twig->addGlobal('session', $_SESSION);
            $twig->addGlobal('puid', sv('puid'));
            $twig->addGlobal('auth_fname', sv('firstname'));
            $twig->addGlobal('isCopyright', sv('isCopyright'));
            $twig->addGlobal('base_url', rtrim(Config::get('baseurl'), '/'));

            $_utility = getModel('utility');

            $twig->addGlobal('menu_links', $_utility->getMenuBrokenLinks());
            $twig->addGlobal('menu_count', $_utility->getMenuNewItems());
            $twig->addGlobal('menu_space', $_utility->getDiskSpace());


            //safely disable
            $twig->addExtension(new Twig_Extension_Debug());

            $template = $twig->loadTemplate($template . '.twig.html');
            $template->display($template_data);

        } else {
            echo '<p class="alert alert-error">Missing view &ldquo;' . htmlspecialchars($view_file) . '&rdquo;.</p>';
            //var_dump($template_data);
        }

        theme_footer(1);

        if (file_exists($view_dir . '/script-foot.js')) {
            echo '<script src="/staticfile/view/' . $action . '/res/script-foot.js" /></script>';
        }

        echo '<script src="' . Config::get('baseurl') . 'js/script-foot.js"></script>';

        theme_footer(2);
    }
}

function getController($controller_name)
{
    if (preg_match('/\.\//', $controller_name)) {
        die();
    }//prevent directory traversal
    $file = Config::get('approot') . '/controller/' . $controller_name . '/control.inc.php';
    if (file_exists($file)) {
        include_once($file);
        $className = 'Controller_' . $controller_name;

        return new $className();
    } else {
        return false;
    }
}

/**
 * Get Model
 *
 * @param string $model_name the of the model to load
 *
 * @return Model_licr|Model_bibdata|Model_details|Model_docstore|Model_metadata|Model_summon|Model_utility|Model_voyager|Model_home|bool return the model
 *                                                                                                                                       or false if model
 *                                                                                                                                       not found
 */
function getModel($model_name)
{
    if (preg_match('/\.\//', $model_name)) {
        die();
    }
    $file = Config::get('approot') . '/model/' . $model_name . '/model.inc.php';
    if (file_exists($file)) {
        include_once($file);
        $className = 'Model_' . $model_name;

        return new $className();
    } else {
        return false;
    }
}

/**
 * for controller functions returning JSON
 *
 * @param mixed $val
 */
function return_json($val)
{
    header('Content-type: application/json');//or should it be application/json, idk
    $str = @json_encode($val, true);
    if (!$str) {
        $str = json_encode([
            'success' => false,
            'message' => 'JSON encoding error'
        ]);
    }
    $cl = strlen($str);
    header('Content-length: ' . $cl);
    echo $str;
    exit();
}

/*
 * Ensure UTF Encoding
 *
 * @param string $fromto the type of encoding to convert from and the type to convert to
 * @param array $input the array that will have its values encoded
 * @return array encoded array
 *
 * */
function array_recode($fromto, $input)
{
    if (!is_array($input)) {
        $uns = @unserialize($input);
        if (is_array($uns)) {
            $uns = array_recode($fromto, $uns);

            return serialize($uns);
        } else {
            @json_encode($input);
            if (json_last_error()) {
                return recode_string($fromto, $input);
            } else {
                return $input;
            }
        }
    } else {
        foreach ($input as $i => $v) {
            $input [$i] = array_recode($fromto, $v);
        }
        return $input;
    }
}


/*
 *  Static class to manage memcached
 */

class MC
{

    /**
     * @var $memchcache Memcached
     */
    private static $memcache = NULL;


    public static function set($key, $val, $timeout = null)
    {
        if (!self::$memcache) {
            self::_init();
        }

        return self::$memcache->set($key, $val, $timeout);
    }

    public static function get($key)
    {
        if (!self::$memcache) {
            self::_init();
        }

        return self::$memcache->get($key);
    }

    public static function getDuration($length = '')
    {
        $env=Config::get('environment');
        if ($env === 'development'
            || $env === 'staging'
            || $env === 'stg') {
            $short = 25;
            $medium = 25;
            $long = 25;
            $forever = 25;
        } else {
            $short = 300;
            $medium = 1800;
            $long = 7200;
            $forever = 0;
        }

        switch ($length) {
            case 'short':
                return $short;
                break;
            case 'medium':
                return $medium;
                break;
            case 'long':
                return $long;
                break;
            case 'forever':
                return $forever;
                break;
            default:
                return $short;
        }
    }

    public static function getResultCode()
    {
        if (!self::$memcache) {
            self::_init();
        }
        return self::$memcache->getResultCode();
    }

    public static function flush()
    {
        if (!self::$memcache) {
            self::_init();
        }
        return self::$memcache->flush();
    }

    public static function runCommand($command)
    {
        if (!self::$memcache) {
            self::_init();
        }

        return self::$memcache->$command();
    }

    private static function _init()
    {
        if (self::$memcache) {
            return;
        }
        self::$memcache = new Memcached('CRS' . Config::get('env'));
        $sl = self::$memcache->getServerList();
        if (0 === count ($sl)) {
            self::$memcache->addServer('localhost', 11211);
        }
    }
}

/*
 *  Static class to create alerts
 */

class Reportinator
{

    private static $developers = [
        'person@email'
    ];

    private static $jiraEmails = [
        'person@email'
    ];

    private static $copyrightEmails = [
        'person@email'
    ];

    public static function alertDevelopers($subject, $message)
    {
        $emails = rtrim(implode(',', self::$developers), ',');
        self::sendmail($emails, $subject, $message);
    }

    public static function alertCopyright($subject, $message)
    {
        $emails = rtrim(implode(',', self::$copyrightEmails), ',');
        self::sendmail($emails, $subject . ' - Attn: Bryan', $message);
    }

    public static function createTicket($subject, $message)
    {
        $emails = rtrim(implode(',', self::$jiraEmails), ',');
        self::sendmail($emails, $subject, $message);
    }

    public static function log($subject, $findme = '', $silent = false)
    {
        if (!$silent) {
            if (isset($findme) && $findme !== '') {
                error_log($findme);
            }
            error_log($subject);
        }
    }

    public static function json_log($subject, $findme = '', $silent = false)
    {
        if (!$silent) {
            if ($findme !== '') {
                error_log($findme);
            }
            error_log(json_encode($subject));
        }
    }


    private static function sendmail($emails, $subject, $message)
    {
        error_log(json_encode(['subject' => $subject, 'message' => $message]));
        #$headers = 'From: ' . "\r\n" . 'Reply-To: ' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
        #mail($emails, $subject . ' - ' . date('dS m,Y H:i:s', time()), $message, $headers);
    }
}

