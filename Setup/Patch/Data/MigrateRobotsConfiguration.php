<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobots\Setup\Patch\Data;

use Hryvinskyi\SeoRobotsApi\Api\RobotsListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Migrate robots configuration from v1.x (code-based) to v2.0 (directive arrays)
 */
class MigrateRobotsConfiguration implements DataPatchInterface
{
    /**
     * Mapping of old codes to directive arrays
     */
    private const CODE_TO_DIRECTIVES_MAP = [
        RobotsListInterface::NOINDEX_NOFOLLOW => ['noindex', 'nofollow'],
        RobotsListInterface::NOINDEX_FOLLOW => ['noindex', 'follow'],
        RobotsListInterface::INDEX_NOFOLLOW => ['index', 'nofollow'],
        RobotsListInterface::INDEX_FOLLOW => ['index', 'follow'],
        RobotsListInterface::NOINDEX_NOFOLLOW_NOARCHIVE => ['noindex', 'nofollow', 'noarchive'],
        RobotsListInterface::NOINDEX_FOLLOW_NOARCHIVE => ['noindex', 'follow', 'noarchive'],
        RobotsListInterface::INDEX_NOFOLLOW_NOARCHIVE => ['index', 'nofollow', 'noarchive'],
        RobotsListInterface::INDEX_FOLLOW_NOARCHIVE => ['index', 'follow', 'noarchive'],
    ];

    public function __construct(
        private readonly WriterInterface $configWriter,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * @inheritDoc
     */
    public function apply(): void
    {
        $this->migrateMetaRobotsRules();
        $this->migrateHttpsMetaRobots();
        $this->migratePaginatedRobots();
    }

    /**
     * Migrate meta_robots configuration rules
     *
     * @return void
     */
    private function migrateMetaRobotsRules(): void
    {
        $configPath = 'hryvinskyi_seo/robots/meta_robots';
        $currentConfig = $this->scopeConfig->getValue($configPath);

        if (empty($currentConfig)) {
            return;
        }

        try {
            $rules = $this->serializer->unserialize($currentConfig);

            if (!is_array($rules)) {
                return;
            }

            $migratedRules = [];
            $needsMigration = false;

            foreach ($rules as $k => $rule) {
                // Check if this rule uses old 'option' field with integer code
                if (isset($rule['option']) && is_numeric($rule['option'])) {
                    $oldCode = (int)$rule['option'];
                    $directives = self::CODE_TO_DIRECTIVES_MAP[$oldCode] ?? ['index', 'follow'];

                    $migratedRules[$k] = [
                        'priority' => $rule['priority'] ?? 0,
                        'pattern' => $rule['pattern'] ?? '',
                        'meta_directives' => $directives,
                    ];

                    $needsMigration = true;
                } elseif (isset($rule['meta_directives'])) {
                    // Already migrated format
                    $migratedRules[] = $rule;
                } else {
                    // Unknown format, preserve as-is but add required fields
                    $migratedRules[] = array_merge($rule, [
                        'meta_directives' => ['index', 'follow'],
                    ]);
                }
            }

            if ($needsMigration) {
                $this->configWriter->save(
                    $configPath,
                    $this->serializer->serialize($migratedRules)
                );
            }
        } catch (\Exception $e) {
            // Log error but don't break installation
            // In production, you might want to use a logger here
        }
    }

    /**
     * Migrate https_meta_robots configuration
     *
     * @return void
     */
    private function migrateHttpsMetaRobots(): void
    {
        $configPath = 'hryvinskyi_seo/robots/https_meta_robots';
        $httpsCode = $this->scopeConfig->getValue($configPath);

        if (empty($httpsCode) || !is_numeric($httpsCode)) {
            return;
        }

        try {
            $directives = self::CODE_TO_DIRECTIVES_MAP[(int)$httpsCode] ?? ['index', 'follow'];

            $this->configWriter->save(
                $configPath,
                json_encode($directives)
            );
        } catch (\Exception $e) {
            // Log error but don't break installation
        }
    }

    /**
     * Migrate paginated_robots_type configuration
     *
     * @return void
     */
    private function migratePaginatedRobots(): void
    {
        $configPath = 'hryvinskyi_seo/robots/paginated_robots_type';
        $paginatedCode = $this->scopeConfig->getValue($configPath);

        if (empty($paginatedCode) || !is_numeric($paginatedCode)) {
            return;
        }

        try {
            $directives = self::CODE_TO_DIRECTIVES_MAP[(int)$paginatedCode] ?? ['index', 'follow'];

            $this->configWriter->save(
                $configPath,
                json_encode($directives)
            );
        } catch (\Exception $e) {
            // Log error but don't break installation
        }
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
