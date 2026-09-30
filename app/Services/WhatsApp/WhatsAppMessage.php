<?php

namespace App\Services\WhatsApp;

/**
 * A WhatsApp message. Business-initiated messages outside the 24-hour customer-service window must use
 * a Meta-approved template, so a message carries both: the template (used when configured) and plain text.
 */
class WhatsAppMessage
{
    public function __construct(
        public string $text,
        public ?string $template = null,
        public array $templateParams = [],
        public ?string $language = null,
    ) {}
}
