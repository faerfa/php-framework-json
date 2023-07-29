<?php
declare(strict_types=1);

use framework\json\Json;
use framework\json\JsonException;

spl_autoload_register(function ($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $filePath = sprintf("%s%s.php", __DIR__ . DIRECTORY_SEPARATOR, str_replace("framework/json/", "src/", $class));
    if (is_readable($filePath)) {
        require_once $filePath;
        if (class_exists($class)) {
            return true;
        }
    }
    return false;
});


enum Type
{
    case User;
}

class User
{
    public string $name;

    public string $password;

    public Type $type;

    public DateTime $dateTime;

}

$user = new User();
$user->name = "name";
$user->password = "123456";
$user->type = Type::User;
$user->dateTime = new DateTime();

try {
    $json = Json::serialize($user);
    var_dump($json);
    /**
     * string(71) "{"name":"name","password":"123456","type":"User","dateTime":1690613979}"
     */

} catch (JsonException $e) {
    var_dump($e->getMessage());
}

try {
    $user = Json::deserialize($json, User::class);
    var_dump($user);
    /**
    object(User)#9 (4) {
    ["name"]=>
    string(4) "name"
    ["password"]=>
    string(6) "123456"
    ["type"]=>
    enum(Type::User)
    ["dateTime"]=>
    object(DateTime)#15 (3) {
    ["date"]=>
    string(26) "2023-07-29 06:59:39.000000"
    ["timezone_type"]=>
    int(1)
    ["timezone"]=>
    string(6) "+00:00"
    }
    }
     */
} catch (JsonException $e) {
    var_dump($e->getMessage());
}

