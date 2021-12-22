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
    public const XML_CONF_HTTPS_META_ROBOTS = 'hryvinskyi_seo/robots/https_meta_robots';
    public const XML_CONF_IS_NOINDEX_NOFOLLOW_FOR_NO_ROUTE_INDEX = 'hryvinskyi_seo/robots/is_noindex_nofollow_for_no_route_index';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     */
    public function __construct(ScopeConfigInterface $scopeConfig, SerializerInterface $serializer)
    {
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_CONF_ENABLED, $scopeType, $scopeCode);
    }

    /**
     * @inheritDoc
     */
    public function getMetaRobots($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): array
    {
        return $this->serializer->unserialize(
            $this->scopeConfig->getValue(self::XML_CONF_META_ROBOTS, $scopeType, $scopeCode)
        );
    }

    /**
     * @inheritDoc
     */
    public function getHttpsMetaRobots($scopeCode = null, string $scopeType = ScopeInterface::SCOPE_STORE): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_CONF_HTTPS_META_ROBOTS, $scopeType, $scopeCode);
    }

    /**
     * @inheritDoc
     */
    public function isNoindexNofollowForNoRouteIndex(
        $scopeCode = null,
        string $scopeType = ScopeInterface::SCOPE_STORE
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::XML_CONF_IS_NOINDEX_NOFOLLOW_FOR_NO_ROUTE_INDEX,
            $scopeType,
            $scopeCode
        );
    }
}
