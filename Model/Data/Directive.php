<?php
/**
 * Copyright (c) 2021-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobots\Model\Data;

use Hryvinskyi\SeoRobotsApi\Api\Data\DirectiveInterface;

/**
 * Structured robot directive implementation
 */
class Directive implements DirectiveInterface
{
    private string $value = '';
    private string $bot = '';
    private string $modification = '';

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function setValue(string $value): DirectiveInterface
    {
        $this->value = trim($value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getBot(): string
    {
        return $this->bot;
    }

    /**
     * @inheritDoc
     */
    public function setBot(string $bot): DirectiveInterface
    {
        $this->bot = trim($bot);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getModification(): string
    {
        return $this->modification;
    }

    /**
     * @inheritDoc
     */
    public function setModification(string $modification): DirectiveInterface
    {
        $this->modification = trim($modification);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        $parts = [];

        if ($this->bot !== '') {
            $parts[] = $this->bot;
        }

        if ($this->value !== '') {
            $parts[] = $this->value;
        }

        if ($this->modification !== '') {
            $parts[] = $this->modification;
        }

        return implode(':', $parts);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [
            self::KEY_VALUE => $this->value,
            self::KEY_BOT => $this->bot,
            self::KEY_MODIFICATION => $this->modification,
        ];
    }

    /**
     * @inheritDoc
     */
    public function fromArray(array $data): DirectiveInterface
    {
        $this->value = trim((string)($data[self::KEY_VALUE] ?? ''));
        $this->bot = trim((string)($data[self::KEY_BOT] ?? ''));
        $this->modification = trim((string)($data[self::KEY_MODIFICATION] ?? ''));

        return $this;
    }

    /**
     * Create a new Directive from array data
     *
     * @param array $data
     * @return self
     */
    public static function create(array $data = []): self
    {
        $directive = new self();
        if (!empty($data)) {
            $directive->fromArray($data);
        }
        return $directive;
    }

    /**
     * Parse a string representation back into a Directive
     * Supports formats:
     * - "noindex" -> value only
     * - "max-snippet:50" -> value with modification
     * - "googlebot:noindex" -> bot with value
     * - "googlebot:max-snippet:50" -> bot with value and modification
     *
     * @param string $str
     * @param array $knownDirectives List of known directive names
     * @return self
     */
    public static function fromString(string $str, array $knownDirectives = []): self
    {
        $directive = new self();
        $str = trim($str);

        if ($str === '') {
            return $directive;
        }

        $parts = explode(':', $str);

        if (count($parts) === 1) {
            // Simple directive: "noindex"
            $directive->setValue($parts[0]);
        } elseif (count($parts) === 2) {
            // Could be "bot:value" or "value:modification"
            $firstPart = strtolower($parts[0]);

            // Check if first part is a known directive (then it's value:modification)
            if (in_array($firstPart, $knownDirectives)) {
                $directive->setValue($parts[0]);
                $directive->setModification($parts[1]);
            } else {
                // Assume it's bot:value
                $directive->setBot($parts[0]);
                $directive->setValue($parts[1]);
            }
        } elseif (count($parts) >= 3) {
            // bot:value:modification
            $directive->setBot($parts[0]);
            $directive->setValue($parts[1]);
            $directive->setModification(implode(':', array_slice($parts, 2)));
        }

        return $directive;
    }
}
