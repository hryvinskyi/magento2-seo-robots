<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobots\Model;

use Hryvinskyi\SeoRobotsApi\Api\RobotsListInterface;

class RobotsList implements RobotsListInterface
{
    /**
     * Conflicting directive pairs that cannot be used together
     */
    private const CONFLICTING_PAIRS = [
        [self::DIRECTIVE_INDEX, self::DIRECTIVE_NOINDEX],
        [self::DIRECTIVE_FOLLOW, self::DIRECTIVE_NOFOLLOW],
        [self::DIRECTIVE_ALL, self::DIRECTIVE_NONE],
        [self::DIRECTIVE_ALL, self::DIRECTIVE_NOINDEX],
        [self::DIRECTIVE_ALL, self::DIRECTIVE_NOFOLLOW],
        [self::DIRECTIVE_NONE, self::DIRECTIVE_INDEX],
        [self::DIRECTIVE_NONE, self::DIRECTIVE_FOLLOW],
    ];

    /**
     * Valid values for max-image-preview directive
     */
    private const MAX_IMAGE_PREVIEW_VALUES = ['none', 'standard', 'large'];

    /**
     * @inheritDoc
     * @deprecated Use buildMetaRobotsFromDirectives() instead
     */
    public function getMetaRobotsByCode(int $code): string
    {
        switch ($code) {
            case self::NOINDEX_NOFOLLOW:
                return 'NOINDEX,NOFOLLOW';
            case self::NOINDEX_FOLLOW:
                return 'NOINDEX,FOLLOW';
            case self::INDEX_NOFOLLOW:
                return 'INDEX,NOFOLLOW';
            case self::NOINDEX_NOFOLLOW_NOARCHIVE:
                return 'NOINDEX,NOFOLLOW,NOARCHIVE';
            case self::NOINDEX_FOLLOW_NOARCHIVE:
                return 'NOINDEX,FOLLOW,NOARCHIVE';
            case self::INDEX_NOFOLLOW_NOARCHIVE:
                return 'INDEX,NOFOLLOW,NOARCHIVE';
            case self::INDEX_FOLLOW_NOARCHIVE:
                return 'INDEX,FOLLOW,NOARCHIVE';
            case self::INDEX_FOLLOW:
            default:
                return 'INDEX,FOLLOW';
        }
    }

    /**
     * @inheritDoc
     */
    public function buildMetaRobotsFromDirectives(array $directives): string
    {
        if (empty($directives)) {
            return '';
        }

        // Convert all directives to uppercase and join with comma
        return strtoupper(implode(',', $directives));
    }

    /**
     * @inheritDoc
     */
    public function getBasicDirectives(): array
    {
        return [
            self::DIRECTIVE_INDEX,
            self::DIRECTIVE_NOINDEX,
            self::DIRECTIVE_FOLLOW,
            self::DIRECTIVE_NOFOLLOW,
            self::DIRECTIVE_NOARCHIVE,
            self::DIRECTIVE_NOSNIPPET,
            self::DIRECTIVE_NOTRANSLATE,
            self::DIRECTIVE_NOIMAGEINDEX,
            self::DIRECTIVE_NONE,
            self::DIRECTIVE_ALL,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAdvancedDirectives(): array
    {
        return [
            self::DIRECTIVE_MAX_SNIPPET,
            self::DIRECTIVE_MAX_IMAGE_PREVIEW,
            self::DIRECTIVE_MAX_VIDEO_PREVIEW,
            self::DIRECTIVE_UNAVAILABLE_AFTER,
        ];
    }

    /**
     * @inheritDoc
     */
    public function validateDirectives(array $directives): array
    {
        $errors = [];

        if (empty($directives)) {
            return ['valid' => true, 'errors' => []];
        }

        // Extract base directives (without values) for conflict checking
        $baseDirectives = [];
        foreach ($directives as $directive) {
            if (strpos($directive, ':') !== false) {
                [$name, $value] = explode(':', $directive, 2);
                $baseDirectives[] = $name;

                // Validate advanced directive values
                $valueError = $this->validateAdvancedDirective($name, $value);
                if ($valueError) {
                    $errors[] = $valueError;
                }
            } else {
                $baseDirectives[] = $directive;
            }
        }

        // Check for conflicting directives
        foreach (self::CONFLICTING_PAIRS as [$dir1, $dir2]) {
            if (in_array($dir1, $baseDirectives) && in_array($dir2, $baseDirectives)) {
                $errors[] = sprintf('Conflicting directives: "%s" and "%s" cannot be used together', $dir1, $dir2);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate advanced directive value
     *
     * @param string $name Directive name
     * @param string $value Directive value
     * @return string|null Error message or null if valid
     */
    private function validateAdvancedDirective(string $name, string $value): ?string
    {
        switch ($name) {
            case self::DIRECTIVE_MAX_SNIPPET:
                if (!is_numeric($value) || (int)$value < -1) {
                    return sprintf('max-snippet value must be -1 or a positive number, got "%s"', $value);
                }
                break;

            case self::DIRECTIVE_MAX_IMAGE_PREVIEW:
                if (!in_array($value, self::MAX_IMAGE_PREVIEW_VALUES)) {
                    return sprintf(
                        'max-image-preview must be one of: %s, got "%s"',
                        implode(', ', self::MAX_IMAGE_PREVIEW_VALUES),
                        $value
                    );
                }
                break;

            case self::DIRECTIVE_MAX_VIDEO_PREVIEW:
                if (!is_numeric($value) || (int)$value < -1) {
                    return sprintf('max-video-preview value must be -1 or a positive number, got "%s"', $value);
                }
                break;

            case self::DIRECTIVE_UNAVAILABLE_AFTER:
                if (strtotime($value) === false) {
                    return sprintf('unavailable_after must be a valid date format, got "%s"', $value);
                }
                break;
        }

        return null;
    }
}
