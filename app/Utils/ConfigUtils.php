<?php

namespace App\Utils;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;

class ConfigUtils
{
    #[Inject]
    private ConfigInterface $config;

    public function getNacosConfigLanhai()
    {
        // 直接获取，配置更新会自动生效
        var_dump($this->config->get('nacos_config.lanhai'));
        return $this->config->get('nacos_config.lanhai', 100);
    }
}