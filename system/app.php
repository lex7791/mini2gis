<?php

// автозагрузчик
spl_autoload_register(function ($class) {
    $file = (strpos($class, '_') ? '' : 'system/') . strtr($class, '_', '/') . '.php';
    require $file;
});

// алиас app::url для шаблонов
function url()
{
    return app::url(func_get_args());
}

//
class app
{
    //
    private static $route = [];

    // обработка запроса
    public static function init($routes = [])
    {
        // дообработка данных из строки адреса
        $url = $_SERVER['PATH_INFO'] ? $_SERVER['PATH_INFO'] : $_SERVER['REQUEST_URI'];
        $url = urldecode($url) . '?';
        $url = substr($url, 0, strpos($url, '?'));

        // создаем ссылки
        foreach ($routes as $k => $v) {
            if (is_numeric($k)) {
                $k = strtr($v, '.', '/');
            }
            $route[$k] = $v;
        }
        // сортировка роутов по длине
        uksort($route, function ($a, $b) {
            if (strlen($a) < strlen($b)) {
                return 1;
            }
            return -1;
        });
        // поиск метода
        self::$route = $route;
        $def = [];
        $arg = [];
        foreach ($route as $k => $v) {
            if (strpos('^' . $url, $k)) {
                $def = explode('_', strtr($v, '.', '_'), 2);
                $arg = explode('/', trim(substr(trim($url, '/ '), strlen($k)), '/ '));
                break;
            }
        }
        if ($url == '/') {
            $def = ['index', 'index'];
        }
        if ($arg[0] == '') {
            $arg = [];
        }
        $file = ('action/' . $def[0] . '.php');
        $def[0] = 'action_' . $def[0];
        $def[1] = strtolower($_SERVER['REQUEST_METHOD'] . '_' . $def[1]);
        if (!is_file($file) or !method_exists($def[0], $def[1])) {
            print self::error('Format url is incorrect, please go read docs');
        }
        // ajax - контент
        $html = call_user_func_array($def, $arg);
        if (is_array($html)) {
            header('Content-Type: application/json');
            print json_encode($html, JSON_UNESCAPED_UNICODE);
            exit;
        }
        // отображение страницы
        $base = ($_SERVER['HTTPS'] ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']
            . rtrim(dirname($_SERVER['SCRIPT_NAME']), '\\/');
        // вычищаем пробельные символы
        $html = preg_replace('~\s+~u', ' ', $html);
        //
        print $html;
    }

    // обработка "кубика"
    public static function box($item, $data)
    {
        $path = 'items/' . $item . '/' . basename($item);
        $data = self::sandbox($path . '.php', $data);
        $html = self::display($path, (array)$data);
        $html = str_replace('="./', '="items/' . $item . '/', $html);
        self::attach($path . '.js');
        self::attach($path . '.css');
        return $html;
    }

    // песочница
    private static function sandbox($path, $data)
    {
        if (is_file($path)) {
            $fun = include $path;
            return call_user_func_array($fun, $data);
        }
        return [];
    }

    // генерируем url на основе имени контроллера
    public static function url($arg)
    {
        // обходим пути
        $route = array_flip(self::$route);
        $arg[0] = $route[$arg[0]];
        $url = implode('/', $arg);

        return $url;
    }

    public static function error($message)
    {
        $result = ['status' => 'error'];
        $result['message'] = $message;
        return json_encode($result);
    }

    public static function decode($data)
    {
        if(!$data) return self::error('No data or incorrect request');
        $result = ['status' => 'ok'];
        $result['result'] = $data;
        return json_encode($result);
    }
}