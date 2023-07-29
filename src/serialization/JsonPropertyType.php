<?php
declare(strict_types=1);

namespace framework\json\serialization;

use Attribute;

/**
 * `JsonPropertyType` 属性类用于定义在序列化和反序列化 JSON 时，被此属性标记的类属性应该对应的 JSON 数据类型。
 *
 * 使用方法：
 * 在类属性前添加 #[JsonPropertyType("data_type")] 注解即可，
 * 其中 "data_type" 是该类属性在 JSON 中对应的数据类型。
 *
 * 例如：
 * class Example {
 *     #[JsonPropertyType("integer")]
 *     private $id;
 * }
 * 在上面的例子中，$id 属性将在序列化为 JSON 时被表示为 integer 类型，
 * 在从 JSON 反序列化时，integer 类型的数据将被反序列化为 $id 属性。
 *
 * 注意：此属性只能用于类的属性，且数据类型应对应于 JSON 的数据类型，例如 "string"、"number"、"object"、"array"、"boolean"、"null"。
 *
 * @target PHP 属性
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class JsonPropertyType
{
    private string $name;

    /**
     * 构造函数
     *
     * @param string $name 类属性在 JSON 中的数据类型
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * 获取类属性在 JSON 中的数据类型
     *
     * @return string 类属性在 JSON 中的数据类型
     */
    public function getName(): string
    {
        return $this->name;
    }
}