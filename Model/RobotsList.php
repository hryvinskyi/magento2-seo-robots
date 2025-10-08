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
     * @inheritDoc
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
}
