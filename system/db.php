<?php

// драйвер для удобной работы с базой mysql
class db
{
    private static $link;
    private static $arg;

    // настройка подключения
    public static function init($host, $user, $pwd, $db)
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        self::$link = new mysqli($host, $user, $pwd, $db);
        self::$link->set_charset("utf8");
    }

    // запрос к базе
    private static function query($sql, $arg = [])
    {
        // кеш
        $cache = 0;
        if (preg_match('~\[cache:(\d+)\]~', $sql, $a)) {
            $sql = str_replace($a[0], '', $sql);
            $cache = $a[1];
            $hash = 'cache/db/' . md5($sql . serialize($arg)) . '.txt';
            if (is_file($hash) and filemtime($hash) + $cache > time()) {
                return unserialize(file_get_contents($hash));
            }
        }
        // сам запрос
        $data = self::execute($sql, $arg);
        // кеш
        if ($cache) {
            file_put_contents($hash, serialize($data), LOCK_EX);
        }

        return $data;
    }

    // SQL-запрос к базе
    private static function execute($sql, $arg = [])
    {
        self::$arg = $arg;
        if ($arg) {
            $sql = preg_replace_callback('~\?(a|k|s|n|i|f|d|r)?~', 'self::replace', $sql);
        }
        $res = self::$link->query($sql);
        $data = [];
        if ($res === true) {
            $data = self::$link->insert_id;
        } else {
            while ($r = $res->fetch_assoc()) {
                /*if ($r['id']) {
                    $data[$r['id']] = $r;
                } else {*/
                    $data[] = $r;
                //}
            }
            $res->close();
        }
        return $data;
    }

    // заменяет placeholder-ы на значения
    private static function replace($in)
    {
        $r = array_shift(self::$arg);
        switch ($in[0]) {
            // массив
            case '?a':
                $res = [];
                foreach ((array)$r as $v) {
                    $res[] = '"' . self::escape($v) . '"';
                }
                return $res ? implode(', ', $res) : 'null';
            // ключь-значение
            case '?k':
                $res = [];
                foreach ($r as $k => $v) {
                    $res[] = '`' . self::escape($k) . '` = ' . ($v ? '"' . self::escape($v) . '"' : 'default');
                }
                return implode(', ', $res);
            // строка без кавычек
            case '?s':
                return self::escape($r);
            // целое положительное
            case '?n':
                return (int)($r < 0 ? 0 : $r);
            // целое
            case '?i':
                return (int)$r;
            // дробное
            case '?f':
                return (float)$r;
            // дата
            case '?d':
                $r = is_numeric($r) ? $r : strtotime($r);
                return is_numeric($r) ? '"' . date('Y-m-d H:i:s', $r) . '"' : 'null';
            // без защиты
            case '?r':
                return $r;
            // по-умолчанию
            default:
                return '"' . self::escape($r) . '"';
        }
    }

    // экранирование данных
    public static function escape($text)
    {
        return self::$link->real_escape_string($text);
    }

    // форматирование времени
    public static function time($time)
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        return is_numeric($time) ? date('Y-m-d H:i:s', $time) : null;
    }

    // Выборка данных из базы
    // - SQL запрос
    // * 0..9 переменных
    public static function sql($sql)
    {
        return self::query($sql, array_slice(func_get_args(), 1));
    }

    // получение первого результата запроса
    // - SQL запрос
    // * 0..9 переменных
    public static function one($sql)
    {
        $sql .= ' limit 0,1';
        $x = self::query($sql, array_slice(func_get_args(), 1));
        $x = array_slice($x, 0, 1);
        return $x[0] ? $x[0] : [];
    }

    // взять запись из таблицы по индификатору
    // - таблица
    // - индификатор(ы)
    public static function id($table, $ids)
    {
        $x = self::query("select * from `?s` where id in (?a)", [$table, $ids]);
        if (is_numeric($ids)) {
            $x = array_slice($x, 0, 1);
            $x = $x[0] ? $x[0] : [];
        }
        return $x;
    }

    // Вставляем запись в таблицу
    // - таблица
    // - массив данных
    public static function insert($table, $data)
    {
        return self::query('insert into `?s` set ?k', [$table, $data]);
    }

    // Изменяем данные в таблице
    // - таблица
    // - массив заменяемых значений
    // - индификатор(ы)
    public static function update($table, $data, $ids)
    {
        self::query('update `?s` set ?k where id in (?a)', [$table, $data, $ids]);
    }

    // Удаляем из базы
    // - таблица
    // - индификатор(ы)
    public static function delete($table, $ids)
    {
        self::query('delete from `?s` where id in (?a)', [$table, $ids]);
    }
}
