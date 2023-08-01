<?php
declare(strict_types=1);

namespace framework\json;

use DateTime;
use Exception;
use framework\json\serialization\JsonDateTimeFormat;
use framework\json\serialization\JsonIgnore;
use framework\json\serialization\JsonPropertyName;
use framework\json\serialization\JsonPropertyType;
use ReflectionClass;
use ReflectionEnum;
use ReflectionException;
use ReflectionNamedType;
use ReflectionObject;

/**
 * Json类，包含序列化和反序列化方法。
 */
class Json
{
    /**
     * 将给定的对象或数组序列化为 JSON 字符串。
     *
     * @param object|array $value 要序列化的对象或数组。
     * @return string 返回的 JSON 字符串。
     * @throws JsonException 如果 JSON 编码失败，则抛出异常。
     */
    public static function serialize(object|array $value): string
    {
        $string = json_encode(self::_serialize($value), JSON_UNESCAPED_UNICODE);
        if (json_last_error() != JSON_ERROR_NONE) throw new JsonException(json_last_error_msg());
        return $string;
    }

    /**
     * 内部方法，用于将对象或数组序列化为 PHP 数组或对象。
     *
     * @param object|array $value 要序列化的对象或数组。
     * @return array|object 序列化后的 PHP 数组或对象。
     */
    private static function _serialize(object|array $value): array|object
    {
        $object = is_array($value) ? (object)$value : $value;

        $reflectionObject = new ReflectionObject($object);

        $values = [];

        foreach ($reflectionObject->getProperties() as $reflectionProperty) {
            if (!$reflectionProperty->isInitialized($object)) continue;

            $name = $reflectionProperty->getName();
            $value = $reflectionProperty->getValue($object);

            unset($jsonIgnore, $jsonPropertyName, $jsonPropertyType, $jsonDateTimeFormat);
            foreach ($reflectionProperty->getAttributes() as $attribute) {
                $newInstance = $attribute->newInstance();
                if ($newInstance instanceof JsonIgnore) {
                    $jsonIgnore = $newInstance;
                } elseif ($newInstance instanceof JsonPropertyName) {
                    $jsonPropertyName = $newInstance;
                } elseif ($newInstance instanceof JsonPropertyType) {
                    $jsonPropertyType = $newInstance;
                } elseif ($newInstance instanceof JsonDateTimeFormat) {
                    $jsonDateTimeFormat = $newInstance;
                }
            }

            if (isset($jsonIgnore)) {
                continue;
            }

            if (isset($jsonPropertyName)) {
                $name = $jsonPropertyName->getName();
            }

            if ((!is_numeric($name) && !$reflectionProperty->hasType()) || is_numeric($name)) {
                if (is_object($value)) {
                    $values[$name] = self::_serialize($value);
                } else {
                    $values[$name] = $value;
                }
                continue;
            }

            if (!$reflectionProperty->hasType()) {
                continue;
            }

            $reflectionPropertyType = $reflectionProperty->getType();

            if (!$reflectionPropertyType instanceof ReflectionNamedType) {
                continue;
            }

            if ($reflectionPropertyType->isBuiltin()) {
                if ($reflectionPropertyType->getName() == "array" || is_array($value)) {
                    $array = [];
                    foreach ($value as $item) {
                        if ($item instanceof DateTime) {
                            if (isset($jsonDateTimeFormat)) {
                                $array[$name] = $item->format($jsonDateTimeFormat->getFormat());
                            } else {
                                $array[$name] = $item->getTimestamp();
                            }

                        } else if (isset($jsonPropertyType)) {

                            if (enum_exists($jsonPropertyType->getName())) {
                                try {
                                    $reflectionEnum = new ReflectionEnum($item);
                                    if ($reflectionEnum->isBacked()) {
                                        $values[$name] = $item;
                                    } else {
                                        $values[$name] = $item->name;
                                    }
                                } catch (ReflectionException) {
                                    continue;
                                }

                            } else if (class_exists($jsonPropertyType->getName())) {
                                $array[$name] = self::_serialize($item);
                            } else {
                                $array[$name] = $item;
                            }

                        } else {
                            $array[$name] = $item;
                        }
                    }
                    $values[$name] = $array;
                } else if ($reflectionPropertyType->getName() == "object" || is_object($value)) {
                    $values[$name] = self::_serialize($value);
                } else {
                    $values[$name] = $value;
                }
            } else {

                if ($value instanceof DateTime) {
                    if (isset($jsonDateTimeFormat)) {
                        $values[$name] = $value->format($jsonDateTimeFormat->getFormat());
                    } else {
                        $values[$name] = $value->getTimestamp();
                    }
                } else if (enum_exists($reflectionPropertyType->getName())) {
                    try {
                        $reflectionEnum = new ReflectionEnum($value);
                        if ($reflectionEnum->isBacked()) {
                            $values[$name] = $value;
                        } else {
                            $values[$name] = $value->name;
                        }
                    } catch (ReflectionException) {
                        continue;
                    }
                } else {
                    $values[$name] = self::_serialize($value);
                }

            }

        }

        return empty($values) ? (object)[] : $values;
    }

    /**
     * 反序列化 JSON 字符串为指定的对象或类的实例。
     *
     * @param string $json 要反序列化的 JSON 字符串。
     * @param object|string $target 反序列化的目标对象或目标类的类名。
     * @return mixed 反序列化后的对象。
     * @throws JsonException 如果 JSON 解码失败，则抛出异常。
     */
    public static function deserialize(string $json, object|string $target): mixed
    {
        $json = json_decode($json);
        if (json_last_error() != JSON_ERROR_NONE) throw new JsonException(json_last_error_msg());
        return self::_deserialize($json, $target);
    }

    /**
     * 内部方法，用于将 JSON 对象反序列化为指定的对象或类的实例。
     *
     * @param object $object 要反序列化的 JSON 对象。
     * @param object|string $target 反序列化的目标对象或目标类的类名。
     * @return mixed 反序列化后的对象。
     * @throws JsonException 如果类型匹配失败或无法创建目标类的实例，则抛出异常。
     */
    private static function _deserialize(object $object, object|string $target): mixed
    {
        if (gettype($target) != "object") {
            try {
                $target = (new ReflectionClass($target))->newInstanceWithoutConstructor();
            } catch (ReflectionException $e) {
                throw new JsonException($e->getMessage());
            }
        }

        $reflectionObject = new ReflectionObject($object);
        $targetReflectionObject = new ReflectionObject($target);

        foreach ($targetReflectionObject->getProperties() as $targetReflectionProperty) {

            $targetPropertyName = $targetReflectionProperty->getName();
            $targetPropertyType = $targetReflectionProperty->getType();

            if (!$targetPropertyType instanceof ReflectionNamedType) {
                continue;
            }

            unset($jsonPropertyName, $jsonPropertyType);
            foreach ($targetReflectionProperty->getAttributes() as $attribute) {
                $newInstance = $attribute->newInstance();
                if ($newInstance instanceof JsonPropertyName) {
                    $jsonPropertyName = $newInstance;
                } elseif ($newInstance instanceof JsonPropertyType) {
                    $jsonPropertyType = $newInstance;
                }
            }

            if (isset($jsonPropertyName)) {
                $targetPropertyName = $jsonPropertyName->getName();
            }

            if (!$reflectionObject->hasProperty($targetPropertyName)) {
                continue;
            }

            $reflectionProperty = $reflectionObject->getProperty($targetPropertyName);

            $objectPropertyValue = $reflectionProperty->getValue($object);
            $objectPropertyType = str_replace(["NULL", "boolean", "integer", "double"], ["null", "bool", "int", "float"], gettype($objectPropertyValue));

            if ($targetPropertyType->isBuiltin()) {
                if ($objectPropertyType != $targetPropertyType->getName() || !settype($objectPropertyValue, $targetPropertyType->getName())) {
                    throw new JsonException("Type match failed param " . $targetPropertyName);
                }

                if ($targetPropertyType->getName() == "array") {
                    $array = [];
                    if (isset($jsonPropertyType)) {
                        foreach ($objectPropertyValue as $item) {
                            if ($jsonPropertyType->getName() == DateTime::class) {

                                if (preg_match("/^\d+$/", (string)$item)) {
                                    $datetime = "@" . substr((string)$item, 0, 10);
                                } else {
                                    $datetime = $item;
                                }

                                try {
                                    $array[] = new DateTime($datetime);
                                } catch (Exception) {
                                    throw new JsonException("Invalid DateTime format: " . $objectPropertyValue);
                                }

                            } else if (enum_exists($jsonPropertyType->getName())) {
                                try {
                                    $reflectionEnum = new ReflectionEnum($jsonPropertyType->getName());
                                    if ($reflectionEnum->isBacked()) {
                                        foreach ($reflectionEnum->getCases() as $backedCase) {
                                            if ($backedCase->getBackingValue() != $item) continue;
                                            $array[] = $backedCase->getValue();
                                            continue 2;
                                        }
                                        throw new JsonException("Invalid enum value: " . $item . " for enum " . $targetPropertyName);
                                    } else {
                                        $array[] = $reflectionEnum->getCase($item)->getValue();
                                    }
                                } catch (ReflectionException) {
                                    throw new JsonException("Invalid enum value: " . $item . " for enum " . $targetPropertyName);
                                }
                            } else if (class_exists($jsonPropertyType->getName())) {
                                $array[] = self::_deserialize($objectPropertyValue, $jsonPropertyType->getName());
                            } else {
                                $itemType = str_replace(["NULL", "boolean", "integer", "double"], ["null", "bool", "int", "float"], gettype($item));
                                if ($itemType != $jsonPropertyType->getName() || !settype($item, $jsonPropertyType->getName())) {
                                    throw new JsonException("Type match failed param " . $targetPropertyName);
                                }
                                $array[] = $item;
                            }
                        }
                    } else {
                        $array[] = $objectPropertyValue;
                    }

                    $targetReflectionProperty->setValue($target, $array);
                } else {
                    $targetReflectionProperty->setValue($target, $objectPropertyValue);
                }

            } else {

                if ($targetPropertyType->getName() == DateTime::class) {

                    if (preg_match("/^\d+$/", (string)$objectPropertyValue)) {
                        $datetime = "@" . substr((string)$objectPropertyValue, 0, 10);
                    } else {
                        $datetime = $objectPropertyValue;
                    }

                    try {
                        $targetReflectionProperty->setValue($target, new DateTime($datetime));
                    } catch (Exception) {
                        throw new JsonException("Invalid DateTime format: " . $objectPropertyValue);
                    }

                } else if (enum_exists($targetPropertyType->getName())) {

                    try {
                        $reflectionEnum = new ReflectionEnum($targetPropertyType->getName());
                        if ($reflectionEnum->isBacked()) {
                            foreach ($reflectionEnum->getCases() as $backedCase) {
                                if ($backedCase->getBackingValue() != $objectPropertyValue) continue;
                                $targetReflectionProperty->setValue($target, $backedCase->getValue());
                                break;
                            }
                            throw new JsonException("Invalid enum value: " . $objectPropertyValue . " for enum " . $targetPropertyName);
                        } else {
                            $targetReflectionProperty->setValue($target, $reflectionEnum->getCase($objectPropertyValue)->getValue());
                        }
                    } catch (ReflectionException) {
                        throw new JsonException("Invalid enum value: " . $objectPropertyValue . " for enum " . $targetPropertyName);
                    }

                } else if (class_exists($targetPropertyType->getName())) {
                    $targetReflectionProperty->setValue($target, self::_deserialize($objectPropertyValue, $targetPropertyType->getName()));
                }

            }

        }

        return $target;
    }
}