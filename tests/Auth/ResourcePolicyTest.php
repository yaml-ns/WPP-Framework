<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Tests\Auth;

use PHPUnit\Framework\TestCase;
use YamlNs\WppFramework\Auth\ResourcePolicy;
use YamlNs\WppFramework\Tests\Support\WordPressState;

final class ResourcePolicyTest extends TestCase
{
    protected function setUp(): void
    {
        WordPressState::reset();
    }

    public function test_reads_use_wordpress_read_capability_by_default(): void
    {
        $policy = new ResourcePolicy();

        $this->assertTrue($policy->viewAny());
        $this->assertTrue($policy->view(new \WP_Post(10)));

        WordPressState::$capabilities['read'] = false;

        $this->assertFalse($policy->viewAny());
        $this->assertFalse($policy->view(new \WP_Post(10)));
    }

    public function test_mutations_use_wordpress_capabilities(): void
    {
        $policy = new ResourcePolicy();
        WordPressState::$capabilities['delete_post'] = false;

        $this->assertTrue($policy->create());
        $this->assertTrue($policy->update(new \WP_Post(10)));
        $this->assertFalse($policy->delete(new \WP_Post(10)));
    }
}
