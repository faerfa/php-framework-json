<?php
declare(strict_types=1);

namespace framework\json\serialization;

use Attribute;

/**
 * JsonPropertyName 属性类代表在序列化和反序列化 JSON 时，被此属性标记的类属性应该对应于具有特定名称的 JSON 属性。
 *
 * 使用方法：
 * 在类属性前添加 #[JsonPropertyName("json_property_name")] 注解即可，
 * 其中 "json_property_name" 是该类属性在 JSON 中对应的属性名。
 *
 * 例如：
 * class Example {
 *     #[JsonPropertyName("id")]
 *     private string $identifier;
 * }
 * 在上面的例子中，$identifier 属性将在序列化为 JSON 时被表示为 "id"，
 * 在从 JSON 反序列化时，"id" 将被反序列化为 $identifier 属性。
 *
 * 注意：此属性只能用于类的属性。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class JsonPropertyName
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}