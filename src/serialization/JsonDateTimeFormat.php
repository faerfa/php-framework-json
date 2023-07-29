<?php
declare(strict_types=1);

namespace framework\json\serialization;

use Attribute;

/**
 * `JsonDateTimeFormat` 属性类用于定义在序列化和反序列化 JSON 时，被此属性标记的日期和时间类属性应该以何种格式表示。
 *
 * 使用方法：
 * 在类属性前添加 #[JsonDateTimeFormat("date_time_format")] 注解即可，
 * 其中 "date_time_format" 是该类属性在 JSON 中对应的日期和时间格式。
 *
 * 例如：
 * class Example {
 *     #[JsonDateTimeFormat("Y-m-d")]
 *     private DateTime $date;
 * }
 * 在上面的例子中，$date 属性将在序列化为 JSON 时被表示为 "Y-m-d" 格式的日期，
 * 在从 JSON 反序列化时，"Y-m-d" 格式的日期将被反序列化为 $date 属性。
 *
 * 注意：此属性只能用于类的属性。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class JsonDateTimeFormat
{
    /**
     * @var string 存储日期和时间的格式字符串
     */
    private string $format;

    /**
     * JsonDateTimeFormat构造函数
     *
     * @param string $format 日期和时间的格式字符串，默认值是 "Y-m-d H:i:s"
     */
    public function __construct(string $format = "Y-m-d H:i:s")
    {
        // 设置日期和时间的格式
        $this->format = $format;
    }

    /**
     * 获取当前的日期和时间格式字符串
     *
     * @return string 当前的日期和时间格式字符串
     */
    public function getFormat(): string
    {
        return $this->format;
    }
}