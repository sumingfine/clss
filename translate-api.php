<?php
// 启用错误日志记录
ini_set('log_errors', 1);
ini_set('error_log', 'translate_debug.log');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 加载配置
$config = include 'translate-config.php';

// 中文到日文的常见演员名翻译字典
$chineseToJapanese = [
    '水菜丽' => 'みづなれい',
    '波多野结衣' => '波多野結衣',
    '苍井空' => '蒼井そら',
    '吉泽明步' => '吉沢明歩',
    '小泽玛利亚' => '小澤マリア',
    '麻生希' => '麻生希',
    '桃谷绘里香' => '桃谷エリカ',
    '三上悠亚' => '三上悠亜',
    '明日花绮罗' => '明日花キララ',
    '桥本有菜' => '橋本ありな',
    '深田咏美' => '深田えいみ',
    '筱田优' => '篠田ゆう',
    '高桥圣子' => '高橋聖子',
    '佐佐木明希' => '佐々木あき',
    '椎名由奈' => '椎名ゆな',
    '大桥未久' => '大橋未久',
    '希崎杰西卡' => '希崎ジェシカ',
    '里美尤利娅' => '里美ゆりあ',
    '天海翼' => '天海つばさ',
    '上原亚衣' => '上原亜衣',
    '西野翔' => '西野翔',
    '原更纱' => '原更紗',
    '葵司' => '葵つかさ',
    '星野娜美' => '星野ナミ',
    '相泽南' => '相沢みなみ',
    '桐谷茉莉' => '桐谷まつり',
    '有村千佳' => '有村千佳',
    '佐山爱' => '佐山愛',
    '初川南' => '初川みなみ',
    '白石茉莉奈' => '白石茉莉奈',
    '松本菜奈实' => '松本菜奈実',
    '神雪' => '神雪',
    '樱井步' => '桜井あゆ',
    '美竹铃' => '美竹すず',
    '枢木葵' => '枢木あおい',
    '夏目彩春' => '夏目彩春',
    '奥田咲' => '奥田咲',
    '滨崎真绪' => '浜崎真緒',
    '冬月枫' => '冬月かえで',
    '爱乃娜美' => '愛乃なみ',
    '凑莉久' => '湊莉久',
    '麻里梨夏' => '麻里梨夏',
    '佳苗瑠华' => '佳苗るか',
    '神咲诗织' => '神咲詩織',
    '友田彩也香' => '友田彩也香',
    '本田岬' => '本田岬',
    '大槻响' => '大槻ひびき',
    '推川悠里' => '推川ゆうり',
    '水野朝阳' => '水野朝陽',
    '北野望' => '北野のぞみ'
];

// 翻译函数
function translateToJapanese($text, $service = 'deeplx') {
    global $config, $chineseToJapanese;
    
    // 首先检查字典中是否有直接匹配
    if (isset($chineseToJapanese[$text])) {
        return $chineseToJapanese[$text];
    }
    
    // 检查是否包含字典中的任何名字
    foreach ($chineseToJapanese as $chinese => $japanese) {
        if (strpos($text, $chinese) !== false) {
            return str_replace($chinese, $japanese, $text);
        }
    }
    
    // 如果字典中没有，尝试使用在线翻译API
    switch ($service) {
        case 'deeplx':
            return translateWithDeepLX($text, $config['deeplx']);
        case 'baidu':
            return translateWithBaidu($text, $config['baidu']);
        case 'youdao':
            return translateWithYoudao($text, $config['youdao']);
        case 'google':
            return translateWithGoogle($text, $config['google']);
        case 'auto':
            // 自动模式：依次尝试各个翻译服务
            $result = translateWithDeepLX($text, $config['deeplx']);
            if ($result === $text && $config['baidu']['enabled']) {
                $result = translateWithBaidu($text, $config['baidu']);
            }
            if ($result === $text && $config['youdao']['enabled']) {
                $result = translateWithYoudao($text, $config['youdao']);
            }
            if ($result === $text && $config['google']['enabled']) {
                $result = translateWithGoogle($text, $config['google']);
            }
            return $result;
        default:
            return $text;
    }
}

// DeepLX翻译函数
function translateWithDeepLX($text, $config) {
    if (!preg_match('/[\x{4e00}-\x{9fa5}]/u', $text)) {
        return $text;
    }
    
    if (!$config['enabled']) {
        return $text;
    }
    
    $maxRetries = 3; // 最大重试次数
    $baseDelay = 1; // 基础延迟时间（秒）
    
    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        try {
            error_log("DeepLX翻译尝试 {$attempt}/{$maxRetries}: {$text}");
            
            $url = $config['baseUrl'] . '/' . $config['apiKey'] . '/translate';
            $data = json_encode([
                'text' => $text,
                'source_lang' => 'ZH',
                'target_lang' => 'JA'
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: application/json',
                'Content-Length: ' . strlen($data)
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 增加到30秒超时
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 连接超时10秒
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlInfo = curl_getinfo($ch);
            curl_close($ch);
            
            // 记录详细的请求信息
            error_log("DeepLX响应 - 尝试{$attempt}: HTTP {$httpCode}, 耗时: {$curlInfo['total_time']}秒");
            
            if ($curlError) {
                error_log("DeepLX CURL错误 - 尝试{$attempt}: {$curlError}");
                throw new Exception("CURL错误: {$curlError}");
            }
            
            if ($httpCode === 200 && $response) {
                $result = json_decode($response, true);
                error_log("DeepLX响应内容 - 尝试{$attempt}: " . $response);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("JSON解析错误: " . json_last_error_msg());
                }
                
                // 处理不同的响应格式
                $translatedText = null;
                if (isset($result['data']) && !empty($result['data'])) {
                    $translatedText = $result['data'];
                } elseif (isset($result['alternatives']) && !empty($result['alternatives'])) {
                    $translatedText = $result['alternatives'][0];
                } elseif (isset($result['text']) && !empty($result['text'])) {
                    $translatedText = $result['text'];
                } elseif (isset($result['result']) && !empty($result['result'])) {
                    $translatedText = $result['result'];
                }
                
                if ($translatedText && $translatedText !== $text) {
                    error_log("DeepLX翻译成功 - 尝试{$attempt}: \"{$text}\" -> \"{$translatedText}\"");
                    return $translatedText;
                } else {
                    throw new Exception("翻译结果为空或与原文相同");
                }
            } elseif ($httpCode === 429) {
                // 请求频率限制
                throw new Exception("请求频率限制 (HTTP 429)");
            } elseif ($httpCode === 500) {
                // 服务器错误
                throw new Exception("服务器内部错误 (HTTP 500)");
            } elseif ($httpCode === 503) {
                // 服务不可用
                throw new Exception("服务不可用 (HTTP 503)");
            } else {
                throw new Exception("HTTP错误 {$httpCode}: " . substr($response, 0, 200));
            }
            
        } catch (Exception $e) {
            $errorMsg = "DeepLX翻译失败 - 尝试{$attempt}/{$maxRetries}: " . $e->getMessage();
            error_log($errorMsg);
            
            // 如果不是最后一次尝试，等待后重试
            if ($attempt < $maxRetries) {
                // 指数退避：1秒, 2秒, 4秒
                $delay = $baseDelay * pow(2, $attempt - 1);
                error_log("DeepLX将在{$delay}秒后重试...");
                sleep($delay);
            } else {
                error_log("DeepLX所有重试失败，返回原文: {$text}");
            }
        }
    }
    
    return $text;
}

// 百度翻译函数
function translateWithBaidu($text, $config) {
    if (!preg_match('/[\x{4e00}-\x{9fa5}]/u', $text)) {
        return $text;
    }
    
    if (!$config['enabled'] || $config['appid'] === 'YOUR_BAIDU_APPID') {
        return $text;
    }
    
    try {
        $salt = time();
        $sign = md5($config['appid'] . $text . $salt . $config['key']);
        
        $url = 'https://fanyi-api.baidu.com/api/trans/vip/translate';
        $data = http_build_query([
            'q' => $text,
            'from' => 'zh',
            'to' => 'jp',
            'appid' => $config['appid'],
            'salt' => $salt,
            'sign' => $sign
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $result = json_decode($response, true);
            if (isset($result['trans_result']) && !empty($result['trans_result'])) {
                return $result['trans_result'][0]['dst'];
            }
        }
    } catch (Exception $e) {
        error_log('百度翻译API调用失败: ' . $e->getMessage());
    }
    
    return $text;
}

// 有道翻译函数
function translateWithYoudao($text, $config) {
    if (!preg_match('/[\x{4e00}-\x{9fa5}]/u', $text)) {
        return $text;
    }
    
    if (!$config['enabled'] || $config['appKey'] === 'YOUR_YOUDAO_APPKEY') {
        return $text;
    }
    
    try {
        $salt = time();
        $curtime = time();
        $signStr = $config['appKey'] . $text . $salt . $curtime . $config['appSecret'];
        $sign = hash('sha256', $signStr);
        
        $url = 'https://openapi.youdao.com/api';
        $data = http_build_query([
            'q' => $text,
            'from' => 'zh-CHS',
            'to' => 'ja',
            'appKey' => $config['appKey'],
            'salt' => $salt,
            'sign' => $sign,
            'signType' => 'v3',
            'curtime' => $curtime
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $result = json_decode($response, true);
            if (isset($result['translation']) && !empty($result['translation'])) {
                return $result['translation'][0];
            }
        }
    } catch (Exception $e) {
        error_log('有道翻译API调用失败: ' . $e->getMessage());
    }
    
    return $text;
}

// Google翻译函数
function translateWithGoogle($text, $config) {
    if (!preg_match('/[\x{4e00}-\x{9fa5}]/u', $text)) {
        return $text;
    }
    
    if (!$config['enabled'] || $config['apiKey'] === 'YOUR_GOOGLE_API_KEY') {
        return $text;
    }
    
    try {
        $url = 'https://translation.googleapis.com/language/translate/v2?key=' . $config['apiKey'];
        $data = json_encode([
            'q' => $text,
            'source' => 'zh',
            'target' => 'ja',
            'format' => 'text'
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $result = json_decode($response, true);
            if (isset($result['data']['translations']) && !empty($result['data']['translations'])) {
                return $result['data']['translations'][0]['translatedText'];
            }
        }
    } catch (Exception $e) {
        error_log('Google翻译API调用失败: ' . $e->getMessage());
    }
    
    return $text;
}

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['text']) || empty($input['text'])) {
        echo json_encode(['error' => '缺少翻译文本']);
        exit;
    }
    
    $text = trim($input['text']);
    $service = isset($input['service']) ? $input['service'] : 'deeplx';
    
    $translatedText = translateToJapanese($text, $service);
    
    echo json_encode([
        'success' => true,
        'original' => $text,
        'translated' => $translatedText,
        'service' => $service
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['debug']) && $_GET['debug'] === 'logs') {
    // 调试模式：查看翻译日志
    header('Content-Type: text/plain; charset=utf-8');
    $logFile = 'translate_debug.log';
    
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        // 只显示最近100行
        $lines = explode("\n", $logs);
        $recentLines = array_slice($lines, -100);
        echo "=== 翻译调试日志 (最近100行) ===\n\n";
        echo implode("\n", $recentLines);
    } else {
        echo "日志文件不存在或暂无日志记录";
    }
} else {
    echo json_encode(['error' => '仅支持POST请求']);
}
?> 