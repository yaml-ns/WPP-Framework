<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Http;

use YamlNs\WppFramework\Http\PostInput;
use YamlNs\WppFramework\Tests\Support\TestCase;

final class PostInputTest extends TestCase
{
    public function test_all_returns_unslashed_post_array(): void
    {
        $_POST = [
            'title' => 'L\\\'Appartement',
            'nested' => [
                'value' => 'A\\\"B',
            ],
        ];

        $this->assertSame([
            'title' => "L'Appartement",
            'nested' => [
                'value' => 'A"B',
            ],
        ], (new PostInput())->all());
    }
}
