<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoRobots\Model;

use Hryvinskyi\SeoRobotsApi\Api\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ConfigInterface
{
    /**
     * Configuration paths
     */
    public const XML_CONF_ENABLED = 'hryvinskyi_seo/robots/enabled';
    public const XML_CONF_META_ROBOTS = 'hryvinskyi_seo/robots/meta_robots';
    public const XML_CONF_NO_ROUTE_ROBOTS_TYPES = 'hryvinskyi_seo/robots/no_route_robots_types';
    public const XML_CONF_PAGINATED_ROBOTS = 'hryvinskyi_seo/robots/paginated_robots';
    public const XML_CONF_PAGINATED_META_ROBOTS = 'hryvinskyi_seo/robots/paginated_robots_type';
    public const XML_CONF_ROBOTS_XHEADER_ENABLED = 'hryvinskyi_seo/robots/robots_xheader_enabled';
    public const XML_CONF_XROBOTS_RULES = 'hryvinskyi_seo/robots/xrobots_rules';
    public const XML_CONF_HTTPS_XROBOTS_DIRECTIVES = 'hryvinskyi_seo/robots/https_xrobots_directives';
    public const XML_CONF_XROBOTS_NO_ROUTE_TYPES = 'hryvinskyi_seo/robots/xrobots_no_route_types';
    public const XML_CONF_XROBOTS_PAGINATED_ENABLED = 'hryvinskyi_seo/robots/xrobots_paginated_enabled';
    public const XML_CONF_XROBOTS_PAGINATED_TYPES = 'hryvinskyi_seo/robots/xrobots_paginated_types';
    public const XML_CONF_PAGINATED_FILTERED_ROBOTS = 'hryvinskyi_seo/robots/paginated_filtered_robots';
    public const XML_CONF_PAGINATED_FILTERED_META_ROBOTS = 'hryvinskyi_seo/robots/paginated_filtered_robots_type';
    public const XML_CONF_XROBOTS_PAGINATED_FILTERED_ENABLED = 'hryvinskyi_seo/robots/xrobots_paginated_filtered_enabled';
    public const XML_CONF_XROBOTS_PAGINATED_FILTERED_TYPES = 'hryvinskyi_seo/robots/xrobots_paginated_filtered_types';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isEnabled($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(static::XML_CONF_ENABLED, $scopeType, $scopeCode);
    }

    /**
     * @inheritDoc
     */
    public function getMetaRobots($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): array
    {
        return $this->serializer->unserialize(
            $this->scopeConfig->getValue(static::XML_CONF_META_ROBOTS, $scopeType, $scopeCode)
        );
    }

    /**
     * Convert legacy code to directive array (for migration compatibility)
     *
     * @param int $code
     * @return array
     */
    private function convertCodeToDirectives(int $code): array
    {
        $map = [
            1 => ['noindex', 'nofollow'],
            2 => ['noindex', 'follow'],
            3 => ['index', 'nofollow'],
            4 => ['index', 'follow'],
            5 => ['noindex', 'nofollow', 'noarchive'],
            6 => ['noindex', 'follow', 'noarchive'],
            7 => ['index', 'nofollow', 'noarchive'],
            8 => ['index', 'follow', 'noarchive'],
        ];

        return $map[$code] ?? ['index', 'follow'];
    }

    /**
     * @inheritDoc
     */
    public function getNoRouteRobotsTypes(
        $scopeCode = null,
        string $scopeType = ScopeInterface::SCOPE_STORE
    ): array {
        $value = $this->scopeConfig->getValue(static::XML_CONF_NO_ROUTE_ROBOTS_TYPES, $scopeType, $scopeCode);
        return $value ? $this->serializer->unserialize($value) : [];
    }

    /**
     * @inheritDoc
     */
    public function isPaginatedRobots($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(static::XML_CONF_PAGINATED_ROBOTS, $scopeType, $scopeCode);
    }
    
    /**
     * @inheritDoc
     */
    public function getPaginatedMetaRobots($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): array
    {
        $value = $this->scopeConfig->getValue(static::XML_CONF_PAGINATED_META_ROBOTS, $scopeType, $scopeCode);
        return $value ? $this->serializer->unserialize($value) : [];
    }

    /**
     * @inheritDoc
     */
    public function isRobotsXheaderEnabled($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_CONF_ROBOTS_XHEADER_ENABLED,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * @inheritDoc
     */
    public function getXRobotsRules($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): array
    {
        $value = $this->scopeConfig->getValue(static::XML_CONF_XROBOTS_RULES, $scopeType, $scopeCode);
        return $value ? $this->serializer->unserialize($value) : [];
    }

    /**
     * @inheritDoc
     */
    public function getHttpsXRobotsDirectives($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): array
    {
        $value = $this->scopeConfig->getValue(static::XML_CONF_HTTPS_XROBOTS_DIRECTIVES, $scopeType, $scopeCode);

        if (is_string($value) && !empty($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function getNoRouteXRobotsTypes(
        $scopeCode = null,
        string $scopeType = ScopeInterface::SCOPE_STORE
    ): array {
        $value = $this->scopeConfig->getValue(static::XML_CONF_XROBOTS_NO_ROUTE_TYPES, $scopeType, $scopeCode);
        return $value ? $this->serializer->unserialize($value) : [];
    }

    /**
     * @inheritDoc
     */
    public function isXRobotsPaginatedEnabled($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(static::XML_CONF_XROBOTS_PAGINATED_ENABLED, $scopeType, $scopeCode);
    }

    /**
     * @inheritDoc
     */
    public function getPaginatedXRobots($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): array
    {
        $value = $this->scopeConfig->getValue(static::XML_CONF_XROBOTS_PAGINATED_TYPES, $scopeType, $scopeCode);
        return $value ? $this->serializer->unserialize($value) : [];
    }

    /**
     * @inheritDoc
     */
    public function isPaginatedFilteredRobots($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(static::XML_CONF_PAGINATED_FILTERED_ROBOTS, $scopeType, $scopeCode);
    }

    /**
     * @inheritDoc
     */
    public function getPaginatedFilteredMetaRobots($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): array
    {
        $value = $this->scopeConfig->getValue(static::XML_CONF_PAGINATED_FILTERED_META_ROBOTS, $scopeType, $scopeCode);
        return $value ? $this->serializer->unserialize($value) : [];
    }

    /**
     * @inheritDoc
     */
    public function isXRobotsPaginatedFilteredEnabled($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(static::XML_CONF_XROBOTS_PAGINATED_FILTERED_ENABLED, $scopeType, $scopeCode);
    }

    /**
     * @inheritDoc
     */
    public function getPaginatedFilteredXRobots($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): array
    {
        $value = $this->scopeConfig->getValue(static::XML_CONF_XROBOTS_PAGINATED_FILTERED_TYPES, $scopeType, $scopeCode);
        return $value ? $this->serializer->unserialize($value) : [];
    }
}
