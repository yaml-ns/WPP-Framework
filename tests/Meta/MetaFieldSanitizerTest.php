<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Meta;

use PHPUnit\Framework\TestCase;
use YamlNs\WppFramework\Meta\MetaFieldSanitizer;

final class MetaFieldSanitizerTest extends TestCase
{
    private MetaFieldSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new MetaFieldSanitizer();
    }

    public function test_sanitize_text_strips_tags(): void
    {
        $this->assertSame('Hello', $this->sanitizer->sanitize('<strong>Hello</strong>', ['type' => 'text']));
    }

    public function test_sanitize_number_returns_float_or_zero(): void
    {
        $this->assertSame(12.5, $this->sanitizer->sanitize('12.5', ['type' => 'number']));
        $this->assertSame(0.0, $this->sanitizer->sanitize('nope', ['type' => 'number']));
        $this->assertSame(12, $this->sanitizer->sanitize('12.5', ['type' => 'integer']));
    }

    public function test_sanitize_checkbox_normalizes_to_string_boolean(): void
    {
        $this->assertSame('1', $this->sanitizer->sanitize('1', ['type' => 'checkbox']));
        $this->assertSame('0', $this->sanitizer->sanitize('on', ['type' => 'checkbox']));
    }

    public function test_sanitize_multiple_values_preserves_array(): void
    {
        $this->assertSame(['a', 'b'], $this->sanitizer->sanitize(['<b>a</b>', 'b'], ['type' => 'checkboxes']));
    }

    public function test_meta_type_maps_field_types(): void
    {
        $this->assertSame('number', $this->sanitizer->metaType('number'));
        $this->assertSame('string', $this->sanitizer->metaType('checkbox'));
        $this->assertSame('string', $this->sanitizer->metaType('text'));
    }
}
