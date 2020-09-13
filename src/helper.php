<?php

/**
 * @return \Mikro\Mikro
 */
function mikro()
{
    return Mikro::create();
}

/**
 * @return bool
 */
function is_ssl()
{
    return arr_get($_SERVER, 'HTTPS') === 'on' || arr_get($_SERVER, 'HTTP_X_FORWARDED_PROTO') === 'https' || arr_get($_SERVER, 'HTTP_X_FORWARDED_SSL') === 'https' || strpos(arr_get($_SERVER, 'HTTP_CF_VISITOR'), 'https' ) !== false;
}

/**
 * @return string
 */
function host_name()
{
    return arr_get($_SERVER, 'HTTP_HOST');
}

/**
 * @return string
 */
function domain_name()
{
    return str_replace('www.', '', host_name());
}

/**
 * @return string
 */
function url_scheme()
{
    return 'http' . (is_ssl() ? 's' : '') . '://';
}

/**
 * @param string|null $uri
 * @param bool $root
 *
 * @return string
 */
function url_path(string $uri = null, bool $root = false)
{
    if (is_null($uri)) {
        $uri = mikro()->getRequestUri();
    }

    $path = ($base = mikro()->getBasePath()) !== '/' ? $base . '/' : ($root ? '/': '');

    if ($uri) {
        $path = rtrim($path, '/') . '/' . rtrim(ltrim($uri, '/'), '/');
    }

    return $path;
}

/**
 * @param string|null $uri
 *
 * @return string
 */
function site_url(string $uri = null)
{
    return url_scheme() . host_name() . url_path($uri, true);
}

/**
 * @return string
 */
function current_url()
{
    return site_url(mikro()->getRequestUri());
}

/**
 * @return string
 */
function full_url()
{
    return current_url() . (($query = mikro()->getQuery()) ? "?{$query}" : '');
}

/**
 * @param array $array
 * @param string $key
 * @param mixed $value
 *
 * @return void
 */
function arr_set(array &$array, string $key, $value)
{
    $keys = explode('.', $key);

    while (count($keys) > 1) {
        if ( ! array_key_exists(($key = array_shift($keys)), $array)) {
            $array[$key] = [];
        }

        $array =& $array[$key];
    }

    $array[array_shift($keys)] = $value;
}

/**
 * @param array $array
 * @param string $key
 * @param mixed|null $fallback
 *
 * @return mixed|null
 */
function arr_get(array $array, string $key, $fallback = null)
{
    $keys = explode('.', $key);
    
    foreach ($keys as $key) {
        if ( ! is_array($array) || ! array_key_exists($key, $array)) {
            return $fallback;
        }
        
        $array = &$array[$key];
    }

    return $array;
}

/**
 * @param array $array
 * @param string $key
 *
 * @return void
 */
function arr_erase(array &$array, string $key) {
    $keys = explode('.', $key);

    while (count($keys) > 1) {
        if (array_key_exists(($key = array_shift($keys)), $array)) {
            $array = &$array[$key];
        }
    }

    if (array_key_exists($key = array_shift($keys), $array)) {
        unset($array[$key]);
    }
}

/**
 * @param array $args
 *
 * @return array
 */
function arr_sort(array $args)
{
    $data = array_shift($args);

    foreach ($args as $index => $field) {
        if (is_string($field)) {
            $tmp = [];

            foreach ($data as $key => $row) {
                $tmp[$key] = $row[$field];
            }

            $args[$index] = $tmp;
        }
    }

    $args[] = &$data;

    call_user_func_array('array_multisort', $args);

    return array_pop($args);
}

/**
 * @param array $array
 *
 * @return array
 */
function arr_trim(array $array)
{
    return array_values(array_filter(array_map('trim', $array)));
}

/**
 * @param string $filePath
 *
 * @return mixed|null
 */
function file_get(string $filePath)
{
    if (is_readable($filePath)) {
        return require_once $filePath;
    }
}

/**
 * @param mixed $data
 * @param bool $exit
 *
 * @return void
 */
function dd($data, bool $exit = true)
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';

    if ($exit) {
        exit;
    }
}