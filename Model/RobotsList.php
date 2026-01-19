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

        // Check if this is a structured directive array and use appropriate method
        if (isset($directives[0]) && is_array($directives[0])) {
            return $this->buildFromStructuredDirectives($directives);
        }

        // Group directives by bot name
        $botDirectives = [];

        foreach ($directives as $directive) {
            // Skip non-string or empty directives
            if (!is_string($directive) || empty(trim($directive))) {
                continue;
            }

            // Check if directive has bot name prefix (e.g., "googlebot:noindex")
            if (strpos($directive, ':') !== false) {
                $parts = explode(':', $directive, 2);

                // Check if this is a bot-specific directive or an advanced directive with value
                // Advanced directives: max-snippet, max-image-preview, max-video-preview, unavailable_after
                $advancedDirectives = ['max-snippet', 'max-image-preview', 'max-video-preview', 'unavailable_after'];

                if (!in_array(strtolower($parts[0]), $advancedDirectives)) {
                    // This is a bot-specific directive
                    $botName = strtolower(trim($parts[0]));
                    $directiveValue = strtolower(trim($parts[1]));

                    if (!isset($botDirectives[$botName])) {
                        $botDirectives[$botName] = [];
                    }

                    // Avoid duplicates for this bot
                    if (!in_array($directiveValue, $botDirectives[$botName])) {
                        $botDirectives[$botName][] = $directiveValue;
                    }
                    continue;
                }
            }

            // Global directive (no bot name)
            if (!isset($botDirectives['*'])) {
                $botDirectives['*'] = [];
            }

            // Avoid duplicates for global directives
            $lowerDirective = strtolower(trim($directive));
            if (!in_array($lowerDirective, $botDirectives['*'])) {
                $botDirectives['*'][] = $lowerDirective;
            }
        }

        // Build the result
        $result = [];

        // Add global directives first (without bot name)
        if (isset($botDirectives['*'])) {
            $result[] = strtoupper(implode(', ', $botDirectives['*']));
            unset($botDirectives['*']);
        }

        // Add bot-specific directives
        foreach ($botDirectives as $botName => $botDirs) {
            $result[] = $botName . ': ' . strtoupper(implode(', ', $botDirs));
        }

        return implode(', ', $result);
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

    /**
     * @inheritDoc
     */
    public function buildFromStructuredDirectives(array $directives): string
    {
        if (empty($directives)) {
            return '';
        }

        $result = [];

        foreach ($directives as $directive) {
            $value = $directive['value'] ?? '';
            $modification = $directive['modification'] ?? '';

            if ($value === '') {
                continue;
            }

            // Build directive string: value:modification
            $directiveStr = strtoupper($value);
            if ($modification !== '') {
                $directiveStr .= ':' . $modification;
            }

            $result[] = $directiveStr;
        }

        return implode(', ', array_unique($result));
    }

    /**
     * @inheritDoc
     */
    public function buildXRobotsFromStructuredDirectives(array $directives): string
    {
        if (empty($directives)) {
            return '';
        }

        // Group by bot
        $botGroups = [];

        foreach ($directives as $directive) {
            $value = $directive['value'] ?? '';
            $bot = $directive['bot'] ?? '';
            $modification = $directive['modification'] ?? '';

            if ($value === '') {
                continue;
            }

            // Build directive string: value:modification
            $directiveStr = strtolower($value);
            if ($modification !== '') {
                $directiveStr .= ':' . $modification;
            }

            $botKey = $bot !== '' ? strtolower($bot) : '*';
            if (!isset($botGroups[$botKey])) {
                $botGroups[$botKey] = [];
            }

            if (!in_array($directiveStr, $botGroups[$botKey])) {
                $botGroups[$botKey][] = $directiveStr;
            }
        }

        // Build result
        $result = [];

        // Global directives first
        if (isset($botGroups['*'])) {
            $result[] = strtoupper(implode(', ', $botGroups['*']));
            unset($botGroups['*']);
        }

        // Bot-specific directives
        foreach ($botGroups as $bot => $dirs) {
            $result[] = $bot . ': ' . strtoupper(implode(', ', $dirs));
        }

        return implode(', ', $result);
    }

    /**
     * @inheritDoc
     */
    public function validateStructuredDirectives(array $directives): array
    {
        $errors = [];

        if (empty($directives)) {
            return ['valid' => true, 'errors' => []];
        }

        // Group directives by bot for conflict checking
        $botDirectives = [];

        foreach ($directives as $index => $directive) {
            $value = strtolower($directive['value'] ?? '');
            $bot = strtolower($directive['bot'] ?? '*');
            $modification = $directive['modification'] ?? '';

            if ($value === '') {
                continue;
            }

            if (!isset($botDirectives[$bot])) {
                $botDirectives[$bot] = [];
            }
            $botDirectives[$bot][] = $value;

            // Validate advanced directive modifications
            if (in_array($value, $this->getAdvancedDirectives())) {
                $valueError = $this->validateAdvancedDirective($value, $modification);
                if ($valueError) {
                    $errors[] = $valueError;
                }
            }
        }

        // Check for conflicts within each bot group
        foreach ($botDirectives as $bot => $dirs) {
            foreach (self::CONFLICTING_PAIRS as [$dir1, $dir2]) {
                if (in_array($dir1, $dirs) && in_array($dir2, $dirs)) {
                    $botLabel = $bot === '*' ? 'global' : $bot;
                    $errors[] = sprintf(
                        'Conflicting directives for %s: "%s" and "%s" cannot be used together',
                        $botLabel,
                        $dir1,
                        $dir2
                    );
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * @inheritDoc
     */
    public function convertToStructured(array $legacyDirectives): array
    {
        $result = [];
        $advancedDirectives = $this->getAdvancedDirectives();

        foreach ($legacyDirectives as $directive) {
            $directive = trim($directive);
            if ($directive === '') {
                continue;
            }

            $parts = explode(':', $directive);
            $structured = [
                'value' => '',
                'bot' => '',
                'modification' => '',
            ];

            if (count($parts) === 1) {
                // Simple: "noindex"
                $structured['value'] = $parts[0];
            } elseif (count($parts) === 2) {
                $firstLower = strtolower($parts[0]);
                // Check if first part is an advanced directive (value:modification)
                if (in_array($firstLower, $advancedDirectives)) {
                    $structured['value'] = $parts[0];
                    $structured['modification'] = $parts[1];
                } else {
                    // Assume bot:value
                    $structured['bot'] = $parts[0];
                    $structured['value'] = $parts[1];
                }
            } elseif (count($parts) >= 3) {
                // bot:value:modification
                $structured['bot'] = $parts[0];
                $structured['value'] = $parts[1];
                $structured['modification'] = implode(':', array_slice($parts, 2));
            }

            $result[] = $structured;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getRobotsDirectives(): array
    {
        return [
            'indexing' => [
                [
                    'value' => 'all',
                    'label' => 'all',
                    'description' => 'No restrictions for indexing or serving (default)',
                    'conflicts' => ['noindex', 'nofollow', 'none']
                ],
                [
                    'value' => 'index',
                    'label' => 'index',
                    'description' => 'Allow different indexing',
                    'conflicts' => ['noindex', 'none']
                ],
                [
                    'value' => 'follow',
                    'label' => 'follow',
                    'description' => 'Follow links on this page',
                    'conflicts' => ['nofollow', 'none']
                ],
                [
                    'value' => 'noindex',
                    'label' => 'noindex',
                    'description' => 'Do not show this page in search results',
                    'conflicts' => ['index', 'all']
                ],
                [
                    'value' => 'nofollow',
                    'label' => 'nofollow',
                    'description' => 'Do not follow links on this page',
                    'conflicts' => ['follow', 'all']
                ],
                [
                    'value' => 'none',
                    'label' => 'none',
                    'description' => 'Equivalent to noindex, nofollow',
                    'conflicts' => ['index', 'follow', 'all']
                ]
            ],
            'snippets' => [
                [
                    'value' => 'noarchive',
                    'label' => 'noarchive',
                    'description' => 'Do not show a cached link in search results'
                ],
                [
                    'value' => 'nosnippet',
                    'label' => 'nosnippet',
                    'description' => 'Do not show a text snippet or video preview'
                ],
                [
                    'value' => 'max-snippet',
                    'label' => 'max-snippet',
                    'description' => 'Maximum text length of snippet',
                    'hasModification' => true,
                    'modificationType' => 'number',
                    'modificationPlaceholder' => 'Enter character count',
                    'formatter' => 'numericColon'
                ],
                [
                    'value' => 'max-image-preview',
                    'label' => 'max-image-preview',
                    'description' => 'Maximum size of image preview',
                    'hasModification' => true,
                    'modificationType' => 'select',
                    'modificationOptions' => ['none', 'standard', 'large'],
                    'formatter' => 'standardColon'
                ],
                [
                    'value' => 'max-video-preview',
                    'label' => 'max-video-preview',
                    'description' => 'Maximum video preview duration in seconds',
                    'hasModification' => true,
                    'modificationType' => 'number',
                    'modificationPlaceholder' => 'Enter seconds (-1 for no limit)',
                    'formatter' => 'numericColon'
                ]
            ],
            'images' => [
                [
                    'value' => 'noimageindex',
                    'label' => 'noimageindex',
                    'description' => 'Do not index images on this page'
                ]
            ],
            'translations' => [
                [
                    'value' => 'notranslate',
                    'label' => 'notranslate',
                    'description' => 'Do not offer translation of this page'
                ]
            ],
            'crawling' => [
                [
                    'value' => 'unavailable_after',
                    'label' => 'unavailable_after',
                    'description' => 'Do not show after specified date/time',
                    'hasModification' => true,
                    'modificationType' => 'datetime',
                    'modificationPlaceholder' => 'YYYY-MM-DD HH:MM:SS UTC',
                    'formatter' => 'standardColon'
                ]
            ]
        ];
    }
}
