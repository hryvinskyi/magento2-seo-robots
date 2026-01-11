<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobots\Model;

use Hryvinskyi\SeoRobotsApi\Api\DirectiveValidatorInterface;
use Hryvinskyi\SeoRobotsApi\Api\RobotsListInterface;

class DirectiveValidator implements DirectiveValidatorInterface
{
    public function __construct(private readonly RobotsListInterface $robotsList)
    {
    }

    /**
     * @inheritDoc
     */
    public function isValid(string $directive): bool
    {
        if (empty($directive)) {
            return false;
        }

        // Check if it's a basic directive
        $basicDirectives = $this->robotsList->getBasicDirectives();
        if (in_array($directive, $basicDirectives)) {
            return true;
        }

        // Check if it's an advanced directive with value
        if (strpos($directive, ':') !== false) {
            [$name, $value] = explode(':', $directive, 2);
            $advancedDirectives = $this->robotsList->getAdvancedDirectives();

            if (in_array($name, $advancedDirectives)) {
                return $this->isValidValue($name, $value);
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function findConflicts(array $directives): array
    {
        $conflicts = [];
        $conflictingPairs = [
            [RobotsListInterface::DIRECTIVE_INDEX, RobotsListInterface::DIRECTIVE_NOINDEX],
            [RobotsListInterface::DIRECTIVE_FOLLOW, RobotsListInterface::DIRECTIVE_NOFOLLOW],
            [RobotsListInterface::DIRECTIVE_ALL, RobotsListInterface::DIRECTIVE_NONE],
            [RobotsListInterface::DIRECTIVE_ALL, RobotsListInterface::DIRECTIVE_NOINDEX],
            [RobotsListInterface::DIRECTIVE_ALL, RobotsListInterface::DIRECTIVE_NOFOLLOW],
            [RobotsListInterface::DIRECTIVE_NONE, RobotsListInterface::DIRECTIVE_INDEX],
            [RobotsListInterface::DIRECTIVE_NONE, RobotsListInterface::DIRECTIVE_FOLLOW],
        ];

        // Extract base directives (without values) for conflict checking
        $baseDirectives = [];
        foreach ($directives as $directive) {
            if (strpos($directive, ':') !== false) {
                [$name] = explode(':', $directive, 2);
                $baseDirectives[] = $name;
            } else {
                $baseDirectives[] = $directive;
            }
        }

        foreach ($conflictingPairs as [$dir1, $dir2]) {
            if (in_array($dir1, $baseDirectives) && in_array($dir2, $baseDirectives)) {
                $conflicts[] = [$dir1, $dir2];
            }
        }

        return $conflicts;
    }

    /**
     * @inheritDoc
     */
    public function isValidValue(string $directiveName, string $value): bool
    {
        switch ($directiveName) {
            case RobotsListInterface::DIRECTIVE_MAX_SNIPPET:
            case RobotsListInterface::DIRECTIVE_MAX_VIDEO_PREVIEW:
                return is_numeric($value) && (int)$value >= -1;

            case RobotsListInterface::DIRECTIVE_MAX_IMAGE_PREVIEW:
                return in_array($value, ['none', 'standard', 'large']);

            case RobotsListInterface::DIRECTIVE_UNAVAILABLE_AFTER:
                return strtotime($value) !== false;

            default:
                return false;
        }
    }
}
