<?php
// 翻译API配置文件
// 请根据需要配置相应的API密钥

return [
    // 百度翻译API配置
    'baidu' => [
        'appid' => 'YOUR_BAIDU_APPID',     // 请替换为您的百度翻译APPID
        'key' => 'YOUR_BAIDU_KEY',         // 请替换为您的百度翻译密钥
        'enabled' => false                 // 设置为true启用百度翻译
    ],
    
    // 有道翻译API配置
    'youdao' => [
        'appKey' => 'YOUR_YOUDAO_APPKEY',   // 请替换为您的有道翻译APPKEY
        'appSecret' => 'YOUR_YOUDAO_SECRET', // 请替换为您的有道翻译密钥
        'enabled' => false                   // 设置为true启用有道翻译
    ],
    
    // Google翻译API配置
    'google' => [
        'apiKey' => 'YOUR_GOOGLE_API_KEY',  // 请替换为您的Google翻译API密钥
        'enabled' => false                  // 设置为true启用Google翻译
    ],
    
    // DeepLX翻译API配置
    'deeplx' => [
        'apiKey' => 'BKhQOHsjkLUONv5IUyCuu_LtA6v9m3jmEr5GpRQGIjM',  // DeepLX API密钥
        'baseUrl' => 'https://api.deeplx.org',  // DeepLX API基础URL
        'enabled' => true                       // 设置为true启用DeepLX翻译
    ],
    
    // 腾讯翻译API配置（需要服务端实现）
    'tencent' => [
        'secretId' => 'YOUR_TENCENT_SECRET_ID',
        'secretKey' => 'YOUR_TENCENT_SECRET_KEY',
        'enabled' => false
    ]
];

// API申请地址和说明
$apiHelp = [
    'baidu' => [
        'url' => 'https://fanyi-api.baidu.com/',
        'description' => '百度翻译开放平台，每月免费200万字符',
        'pros' => ['免费额度大', '速度快', '国内访问稳定'],
        'cons' => ['翻译质量一般']
    ],
    
    'youdao' => [
        'url' => 'https://ai.youdao.com/',
        'description' => '有道智云翻译API，每月免费100万字符',
        'pros' => ['翻译质量较高', '支持多种语言', '国内访问稳定'],
        'cons' => ['免费额度相对较少']
    ],
    
    'google' => [
        'url' => 'https://cloud.google.com/translate/',
        'description' => 'Google Cloud Translation API，按使用量付费',
        'pros' => ['翻译质量最高', '支持语言最多', '技术最先进'],
        'cons' => ['需要VPN访问', '完全付费', '配置复杂']
    ],
    
    'deeplx' => [
        'url' => 'https://github.com/OwO-Network/DeepLX',
        'description' => 'DeepLX免费翻译API，基于DeepL技术',
        'pros' => ['完全免费', '翻译质量高', '无需注册', '支持多种语言'],
        'cons' => ['可能有请求频率限制', '依赖第三方服务']
    ],
    
    'tencent' => [
        'url' => 'https://cloud.tencent.com/product/tmt',
        'description' => '腾讯云机器翻译，每月免费500万字符',
        'pros' => ['免费额度最大', '翻译质量好', '国内访问稳定'],
        'cons' => ['签名算法复杂', '需要服务端实现']
    ]
];

/*
=== 翻译API配置说明 ===

1. 选择翻译服务商并注册账号
2. 获取API密钥
3. 在上方配置数组中配置密钥
4. 将enabled设置为true启用服务

推荐配置顺序：
1. DeepLX翻译（完全免费，质量高，推荐首选）
2. 百度翻译（免费额度大，适合测试）
3. 有道翻译（翻译质量好）
4. 腾讯翻译（免费额度最大，需服务端）
5. Google翻译（质量最高，需VPN）

注意：请妥善保管API密钥，不要泄露给他人！
*/
?> 