<?php
declare(strict_types=1);

namespace framework\json\serialization;

use Attribute;

/**
 * JsonIgnore 属性表示在序列化 JSON 时，应该忽略被此属性标记的属性。
 *
 * 使用方法：
 * 在需要忽略的类属性前添加 #[JsonIgnore] 注解即可。
 *
 * 例如：
 * class Example {
 *     #[JsonIgnore]
 *     private string $ignoredProperty;
 *
 *     private string $serializedProperty;
 * }
 * 在上面的例子中，$ignoredProperty 将在序列化 JSON 时被忽略。
 *
 * 注意：此属性只能用于类的属性。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class JsonIgnore
{

}