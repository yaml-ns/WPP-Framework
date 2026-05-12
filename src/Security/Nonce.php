<?php
declare(strict_types=1);

namespace YamlNs\WppFramework\Security;

final class Nonce
{
    public function create(string $action): string
    {
        return wp_create_nonce($action);
    }

    public function verify(string $nonce, string $action): bool
    {
        return (bool) wp_verify_nonce($nonce, $action);
    }

    /**
     * Verify the nonce of a POST request and throw when invalid.
     *
     * This method throws RuntimeException; it does not handle rendering.
     * The caller (controller, admin page) should wrap it in try/catch:
     *
     *   try {
     *       $this->nonce->verifyPost('my-action');
     *   } catch (\RuntimeException $e) {
     *       wp_die(esc_html($e->getMessage()), 403);
     *   }
     */
    public function verifyPost(string $action, string $field = '_wpnonce'): void
    {
        if (!isset($_POST[$field])) {
            throw new \RuntimeException('Missing nonce.');
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[$field]));

        if (!$this->verify($nonce, $action)) {
            throw new \RuntimeException('Invalid nonce.');
        }
    }
}
