<?php
/**
 * CSCA Bridge - 自动加载器
 * PSR-4风格的自动加载
 */

spl_autoload_register(function ($class) {
    // 类名映射到文件路径
    $prefixes = [
        'App\\' => __DIR__ . '/../classes/',
        'Model\\' => __DIR__ . '/../classes/models/',
        'Service\\' => __DIR__ . '/../classes/services/',
    ];
    
    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
    
    // 尝试直接加载
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($file)) {
        require $file;
        return;
    }
    
    $file = __DIR__ . '/' . $class . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

/**
 * 加载语言包
 */
function lang(string $key, array $params = []): string
{
    static $translations = null;
    
    if ($translations === null) {
        $lang = $_SESSION['lang'] ?? 'zh_CN';
        $langFile = __DIR__ . '/../lang/' . $lang . '/common.php';
        $translations = file_exists($langFile) ? require $langFile : [];
    }
    
    $value = $translations[$key] ?? $key;
    
    if (!empty($params)) {
        foreach ($params as $k => $v) {
            $value = str_replace(':' . $k, $v, $value);
        }
    }
    
    return $value;
}

/**
 * 获取当前语言
 */
function getCurrentLang(): string
{
    return $_SESSION['lang'] ?? 'zh_CN';
}

/**
 * 切换语言
 */
function switchLang(string $lang): void
{
    $availableLocales = ['zh_CN', 'en_US'];
    if (in_array($lang, $availableLocales)) {
        $_SESSION['lang'] = $lang;
    }
}

/**
 * 安全输出HTML
 */
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * 生成URL
 */
function url(string $path = '', array $params = []): string
{
    $baseUrl = 'https://cscabridge.com';
    $url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

/**
 * 获取配置
 */
function config(string $key, $default = null)
{
    static $configs = [];
    
    $parts = explode('.', $key);
    $file = $parts[0];
    
    if (!isset($configs[$file])) {
        $configFile = __DIR__ . '/../config/' . $file . '.php';
        $configs[$file] = file_exists($configFile) ? require $configFile : [];
    }
    
    $value = $configs[$file];
    
    for ($i = 1; $i < count($parts); $i++) {
        if (isset($value[$parts[$i]])) {
            $value = $value[$parts[$i]];
        } else {
            return $default;
        }
    }
    
    return $value ?? $default;
}
