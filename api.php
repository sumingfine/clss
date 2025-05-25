<?php
// 修复版API文件 - 使用正确的登录和路径发现
header('Content-Type: application/json');

$api_base = 'https://cl.533133.xyz';
$mode = $_GET['mode'] ?? 'keyword';
$keyword = trim($_GET['keyword'] ?? '');
$page = intval($_GET['page'] ?? 1);
$sortBy = $_GET['sortBy'] ?? 'date';
$sortOrder = $_GET['sortOrder'] ?? 'desc';
$debug = isset($_GET['debug']) && $_GET['debug'] == '1'; // 添加调试模式
$result = ['html' => '', 'hasMore' => false];

// 认证信息
$admin_username = '5772668';
$admin_password = '5772668aa';

// Cookie存储文件
$cookie_file = sys_get_temp_dir() . '/api_cookies.txt';

// 登录函数
function loginToAPI() {
    global $api_base, $admin_username, $admin_password, $cookie_file;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "$api_base/api/login",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'username' => $admin_username,
            'password' => $admin_password
        ]),
        CURLOPT_COOKIEJAR => $cookie_file,
        CURLOPT_COOKIEFILE => $cookie_file,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Requested-With: XMLHttpRequest'
        ]
    ]);
    
    $login_response = curl_exec($ch);
    curl_close($ch);
    
    if ($login_response === false) {
        return false;
    }
    
    $login_data = json_decode($login_response, true);
    return ($login_data && isset($login_data['success']) && $login_data['success']);
}

// 尝试多个API路径
function tryMultiplePaths($endpoint_type, $params = []) {
    global $api_base, $cookie_file;
    
    // 定义可能的API路径
    $path_patterns = [
        'search' => [
            '/search',
            '/movies/search',
            '/api/search',
            '/api/movies/search',
            '/api/v1/search',
            '/api/v1/movies/search',
            '/api/v2/search',
            '/api/v2/movies/search'
        ],
        'movies' => [
            '/movies',
            '/api/movies',
            '/api/v1/movies',
            '/api/v2/movies'
        ],
        'movie_detail' => [
            '/movies/{id}',
            '/api/movies/{id}',
            '/api/v1/movies/{id}',
            '/api/v2/movies/{id}'
        ],
        'magnets' => [
            '/magnets/{id}',
            '/api/magnets/{id}',
            '/api/v1/magnets/{id}',
            '/api/v2/magnets/{id}',
            '/movies/{id}/magnets',
            '/api/movies/{id}/magnets'
        ]
    ];
    
    $paths = $path_patterns[$endpoint_type] ?? [];
    
    foreach ($paths as $path) {
        // 替换路径中的参数
        $url = $api_base . $path;
        if (isset($params['id'])) {
            $url = str_replace('{id}', $params['id'], $url);
        }
        
        // 添加查询参数
        if (!empty($params['query'])) {
            $url .= '?' . http_build_query($params['query']);
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE => $cookie_file,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-Requested-With: XMLHttpRequest'
            ]
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false || $http_code == 404) {
            continue;
        }
        
        if (strpos($response, '<title>登录</title>') !== false) {
            return false; // 需要重新登录
        }
        
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // 检查是否是有效的响应
            if ($endpoint_type === 'search' && isset($data['movies'])) {
                return $data;
            } elseif ($endpoint_type === 'movies' && isset($data['movies'])) {
                return $data;
            } elseif ($endpoint_type === 'movie_detail' && isset($data['id'])) {
                return $data;
            } elseif ($endpoint_type === 'magnets' && is_array($data)) {
                return $data;
            }
        }
    }
    
    return null;
}

// 主要的API请求函数
function makeApiRequest($endpoint_type, $params = []) {
    // 首先尝试直接请求
    $data = tryMultiplePaths($endpoint_type, $params);
    
    // 如果失败，先登录再重试
    if ($data === false || $data === null) {
        if (loginToAPI()) {
            $data = tryMultiplePaths($endpoint_type, $params);
        } else {
            return null;
        }
    }
    
    return $data;
}

// 图片封面优先使用 DMM sample 图片（避免 javbus 防盗链）
function proxyImage($info) {
    // 首先尝试使用样本图片（无防盗链）
    if (isset($info['samples'][0]['src'])) {
        return $info['samples'][0]['src']; // DMM 图源，无防盗链
    }
    
    // 尝试多个可能的图片字段
    $imageFields = [
        'img', 'cover', 'poster', 'thumb', 'image', 'pic', 
        'cover_url', 'poster_url', 'thumb_url', 'image_url',
        'coverImage', 'posterImage', 'thumbnailImage',
        'screenshot', 'preview', 'thumbnail'
    ];
    $imgUrl = '';
    
    foreach ($imageFields as $field) {
        if (isset($info[$field]) && !empty($info[$field])) {
            $imgUrl = $info[$field];
            break;
        }
    }
    
    // 如果还是没找到，尝试深层次的字段
    if (empty($imgUrl)) {
        $deepFields = [
            ['cover', 'url'], ['cover', 'src'], ['cover', 'image'],
            ['poster', 'url'], ['poster', 'src'], ['poster', 'image'],
            ['thumbnail', 'url'], ['thumbnail', 'src'], ['thumbnail', 'image'],
            ['image', 'url'], ['image', 'src'],
            ['pics', 0], ['images', 0], ['screenshots', 0]
        ];
        
        foreach ($deepFields as $fieldPath) {
            $value = $info;
            foreach ($fieldPath as $key) {
                if (isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    $value = null;
                    break;
                }
            }
            if (!empty($value) && is_string($value)) {
                $imgUrl = $value;
                break;
            }
        }
    }
    
    // 检查图片URL是否存在
    if (empty($imgUrl)) {
        // 返回默认占位图
        return 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22120%22%20height%3D%22145%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20120%20145%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3A%20Arial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder%22%3E%3Crect%20width%3D%22120%22%20height%3D%22145%22%20fill%3D%22%23eee%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20id%3D%22holder_text%22%20x%3D%2236%22%20y%3D%2277.8%22%3E无图片%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
    }
    
    // 检查是否是已知的防盗链网站
    $knownAntiLeechDomains = [
        'javbus.com',
        'javdb.com',
        'dmm.co.jp',
        'fanza.com',
        'r18.com',
        'pics.dmm.co.jp',
        'www.javbus.com',
        'www.javdb.com',
        'images.javbus.com',
        'cdn.javbus.com',
        'jp.netcdn.space',
        'us.netcdn.space'
    ];
    
    $isAntiLeechDomain = false;
    foreach ($knownAntiLeechDomains as $domain) {
        if (strpos($imgUrl, $domain) !== false) {
            $isAntiLeechDomain = true;
            // 临时调试
            error_log("检测到防盗链域名: $domain, URL: $imgUrl");
            break;
        }
    }
    
    // 对于已知防盗链域名或所有外部URL，直接返回占位图，让前端异步替换
    if ($isAntiLeechDomain || strpos($imgUrl, 'http') === 0) {
        // 临时调试
        error_log("返回占位图，防盗链: " . ($isAntiLeechDomain ? 'true' : 'false') . ", HTTP: " . (strpos($imgUrl, 'http') === 0 ? 'true' : 'false'));
        
        // 直接返回占位图，避免防盗链问题，依靠前端异步获取高清图
        return 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22120%22%20height%3D%22145%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20120%20145%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3A%20Arial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder%22%3E%3Crect%20width%3D%22120%22%20height%3D%22145%22%20fill%3D%22%23f5f5f5%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20id%3D%22holder_text%22%20x%3D%2260%22%20y%3D%2270%22%20text-anchor%3D%22middle%22%3E%3Ctspan%20x%3D%2260%22%20dy%3D%22-5%22%3E%F0%9F%8E%AC%3C%2Ftspan%3E%3Ctspan%20x%3D%2260%22%20dy%3D%2215%22%3E加载中%3C%2Ftspan%3E%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
        
        // 以下是备用方案（如果需要的话可以取消注释）：
        
        // 方法1: 使用本地代理
        // return 'proxy-image.php?url=' . urlencode($imgUrl);
        
        // 方法2: 使用imageproxy.pimg.tw代理
        // return 'https://imageproxy.pimg.tw/resize?url=' . urlencode($imgUrl);
        
        // 方法3: 使用images.weserv.nl代理
        // return 'https://images.weserv.nl/?url=' . urlencode($imgUrl);
    }
    
    // 对于其他图片，直接返回
    return $imgUrl;
}

// 辅助函数：获取影片的多种可能图片URL
function getImageUrlsByMovieId($movieId) {
    // 移除可能存在的连字符，并提取番号的字母和数字部分
    $cleanId = str_replace('-', '', $movieId);
    $originalId = $movieId;
    
    // 匹配番号格式，提取前缀和数字
    preg_match('/([a-zA-Z]+)[-_]?(\d+)/', $originalId, $matches);
    $prefix = isset($matches[1]) ? strtolower($matches[1]) : '';
    $number = isset($matches[2]) ? (int)$matches[2] : '';
    
    // 准备多种可能的图片URL格式
    $urls = [
        // DMM数字格式
        "https://pics.dmm.co.jp/digital/video/{$cleanId}/{$cleanId}jp-1.jpg",
        
        // DMM预览图格式
        "https://pics.dmm.co.jp/mono/movie/adult/{$cleanId}/{$cleanId}ps.jpg",
        
        // mgstage.com格式
        "https://image.mgstage.com/images/shirouto/{$prefix}/{$number}/cap_e_0_{$prefix}-{$number}.jpg",
        
        // javbus缩略图格式
        "https://www.javbus.com/pics/thumb/{$originalId}.jpg"
    ];
    
    // 返回主URL和备用URL列表
    return [
        'main' => $urls[0],  // 主要URL
        'alternates' => $urls // 所有备用URL
    ];
}

// ✅ 关键词搜索
if ($mode === 'keyword' && $keyword !== '') {
    $res = makeApiRequest('search', [
        'query' => [
            'keyword' => $keyword,
            'page' => $page,
            'magnet' => 'all'
        ]
    ]);
    
    if (!$res || !isset($res['movies'])) {
        echo json_encode($result);
        exit;
    }
    
    // 调试模式：直接返回原始API数据
    if ($debug) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'debug' => true,
            'api_response' => $res,
            'first_movie_structure' => isset($res['movies'][0]) ? $res['movies'][0] : null,
            'available_fields' => isset($res['movies'][0]) ? array_keys($res['movies'][0]) : []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    foreach ($res['movies'] as $movie) {
        $movieId = $movie['id'];
        
        // 直接使用占位图，让前端异步获取高清图片
        $imgSrc = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22120%22%20height%3D%22145%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20120%20145%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3A%20Arial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder%22%3E%3Crect%20width%3D%22120%22%20height%3D%22145%22%20fill%3D%22%23f5f5f5%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20id%3D%22holder_text%22%20x%3D%2260%22%20y%3D%2270%22%20text-anchor%3D%22middle%22%3E%3Ctspan%20x%3D%2260%22%20dy%3D%22-5%22%3E%F0%9F%8E%AC%3C%2Ftspan%3E%3Ctspan%20x%3D%2260%22%20dy%3D%2215%22%3E加载中%3C%2Ftspan%3E%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
        
        // 准备备用URL的JSON数据（用于错误重试）
        $imageUrls = getImageUrlsByMovieId($movieId);
        $alternateUrlsJson = htmlspecialchars(json_encode($imageUrls['alternates']));
        
        $tags = isset($movie['tags']) ? implode(' ', array_map(fn($t) => "<span class='am-badge am-badge-success am-margin-right-sm'>{$t}</span>", $movie['tags'])) : '';
        $title = htmlspecialchars($movie['title']);
        $result['html'] .= <<<HTML
<div class="card" onclick="openCodeSearch('{$movie['id']}')">
  <img src="{$imgSrc}" 
       alt="{$movie['id']}" 
       data-movie-id="{$movie['id']}" 
       data-alternate-urls='{$alternateUrlsJson}'
       onerror="handleImageError(this)">
  <div class="card-content">
    <h5>{$title}</h5>
    <p><b>番号：</b>{$movie['id']}</p>
    <p><b>标签：</b>{$tags}</p>
  </div>
</div>
HTML;
    }

    $result['hasMore'] = $res['pagination']['hasNextPage'] ?? false;
    echo json_encode($result);
    exit;
}

// ✅ 演员搜索
if ($mode === 'star' && $keyword !== '') {
    $res = makeApiRequest('movies', [
        'query' => [
            'page' => $page,
            'magnet' => 'all',
            'filterType' => 'star',
            'filterValue' => $keyword
        ]
    ]);

    if (!$res || !isset($res['movies'])) {
        echo json_encode($result);
        exit;
    }

    foreach ($res['movies'] ?? [] as $movie) {
        $movieId = $movie['id'];
        
        // 直接使用占位图，让前端异步获取高清图片
        $imgSrc = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22120%22%20height%3D%22145%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20120%20145%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3A%20Arial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder%22%3E%3Crect%20width%3D%22120%22%20height%3D%22145%22%20fill%3D%22%23f5f5f5%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20id%3D%22holder_text%22%20x%3D%2260%22%20y%3D%2270%22%20text-anchor%3D%22middle%22%3E%3Ctspan%20x%3D%2260%22%20dy%3D%22-5%22%3E%F0%9F%8E%AC%3C%2Ftspan%3E%3Ctspan%20x%3D%2260%22%20dy%3D%2215%22%3E加载中%3C%2Ftspan%3E%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
        
        // 准备备用URL的JSON数据（用于错误重试）
        $imageUrls = getImageUrlsByMovieId($movieId);
        $alternateUrlsJson = htmlspecialchars(json_encode($imageUrls['alternates']));
        
        $tags = isset($movie['tags']) ? implode(' ', array_map(fn($t) => "<span class='am-badge am-badge-success'>{$t}</span>", $movie['tags'])) : '';
        $title = htmlspecialchars($movie['title']);

        $result['html'] .= <<<HTML
<div class="card" onclick="openCodeSearch('{$movie['id']}')">
  <img src="{$imgSrc}" 
       alt="{$movie['id']}" 
       data-movie-id="{$movie['id']}" 
       data-alternate-urls='{$alternateUrlsJson}'
       onerror="handleImageError(this)">
  <div class="card-content">
    <h5>{$title}</h5>
    <p><b>番号：</b>{$movie['id']}</p>
    <p><b>标签：</b>{$tags}</p>
  </div>
</div>
HTML;
    }

    $result['hasMore'] = $res['pagination']['hasNextPage'] ?? false;
    echo json_encode($result);
    exit;
}

// ✅ 番号搜索
if ($mode === 'code' && $keyword !== '') {
    $info = makeApiRequest('movie_detail', ['id' => $keyword]);
    
    if (!$info || !isset($info['id'])) {
        echo json_encode($result);
        exit;
    }

    $gid = $info['gid'] ?? '';
    $uc = $info['uc'] ?? '';
    $magnets = makeApiRequest('magnets', [
        'id' => $keyword,
        'query' => [
            'gid' => $gid,
            'uc' => $uc,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder
        ]
    ]);

    $actors = implode('', array_map(fn($s) => "<span class='am-badge am-badge-primary am-margin-right-sm'>{$s['name']}</span>", $info['stars'] ?? []));
    $tags = implode('', array_map(fn($s) => "<span class='am-badge am-badge-success am-margin-right-sm'>{$s['name']}</span>", $info['genres'] ?? []));
    
    // 番号搜索的图片处理：直接返回原始URL
    $img = '';
    if (isset($info['samples'][0]['src'])) {
        $img = $info['samples'][0]['src']; // DMM 图源，无防盗链
    } else {
        // 直接使用原始图片URL，不使用代理
        $imageFields = ['img', 'cover', 'poster', 'thumb', 'image', 'pic'];
        foreach ($imageFields as $field) {
            if (isset($info[$field]) && !empty($info[$field])) {
                $img = $info[$field]; // 直接使用原始URL
                break;
            }
        }
        
        if (empty($img)) {
            $img = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22120%22%20height%3D%22145%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20120%20145%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3A%20Arial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder%22%3E%3Crect%20width%3D%22120%22%20height%3D%22145%22%20fill%3D%22%23eee%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20id%3D%22holder_text%22%20x%3D%2236%22%20y%3D%2277.8%22%3E无图片%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
        }
    }

    $result['html'] .= <<<HTML
<div class="am-panel am-panel-default">
  <div class="am-panel-hd">影片信息 - {$info['id']}</div>
  <div class="am-panel-bd">
    <div class="am-g">
      <div class="am-u-sm-4"><img src="{$img}" class="am-img-responsive" /></div>
      <div class="am-u-sm-8">
        <p><b>标题：</b>{$info['title']}</p>
        <p><b>日期：</b>{$info['date']}</p>
        <p><b>演员：</b>{$actors}</p>
        <p><b>标签：</b>{$tags}</p>
      </div>
    </div>
  </div>
</div>
HTML;

    $count = 0;
    foreach (($magnets ?? []) as $magnet) {
        $count++;
        $label = $sortBy === 'date' ? "日期：{$magnet['shareDate']}" : "大小：{$magnet['size']}";
        $inputId = "magnet{$count}";
        $link = htmlspecialchars($magnet['link']);
        $result['html'] .= <<<HTML
<div class="magnet-box">
  <div class="am-g">
    <div class="am-u-sm-9"><input id="{$inputId}" class="am-form-field" value="{$link}" readonly></div>
    <div class="am-u-sm-3">
      <button class="am-btn am-btn-success am-btn-block" onclick="copyText('{$inputId}')">复制磁力</button>
      <div class="am-text-sm am-text-muted">{$label}</div>
    </div>
  </div>
</div>
HTML;
    }

    $result['hasMore'] = ($count >= 10);
    echo json_encode($result);
    exit;
}

// 默认返回空
echo json_encode($result);
