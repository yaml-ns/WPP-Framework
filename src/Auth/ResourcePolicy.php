<?php

declare(strict_types=1);

namespace YamlNs\WppFramework\Auth;

use WP_Post;

class ResourcePolicy
{
    public function viewAny(): bool
    {
        return $this->can($this->viewAnyCapability());
    }

    public function view(WP_Post $post): bool
    {
        return $this->can($this->viewCapability(), $post->ID);
    }

    public function create(): bool
    {
        return $this->can($this->createCapability());
    }

    public function update(WP_Post $post): bool
    {
        return $this->can($this->updateCapability(), $post->ID);
    }

    public function delete(WP_Post $post): bool
    {
        return $this->can($this->deleteCapability(), $post->ID);
    }

    /**
     * Return null only when the collection should be publicly readable.
     * Override with a stricter capability for private resources.
     */
    protected function viewAnyCapability(): ?string
    {
        return 'read';
    }

    /**
     * Return null only when the resource should be publicly readable.
     * Override with a stricter capability for private resources.
     */
    protected function viewCapability(): ?string
    {
        return 'read';
    }

    protected function createCapability(): ?string
    {
        return 'edit_posts';
    }

    protected function updateCapability(): ?string
    {
        return 'edit_post';
    }

    protected function deleteCapability(): ?string
    {
        return 'delete_post';
    }

    protected function can(?string $capability, mixed ...$args): bool
    {
        return $capability === null || current_user_can($capability, ...$args);
    }
}
