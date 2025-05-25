<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>磁力链接查询 - 智能翻译搜索</title>
  <link rel="stylesheet" href="https://cdn.staticfile.org/amazeui/2.7.2/css/amazeui.min.css">
  <link rel="stylesheet" href="https://cdn.staticfile.org/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="main-container">
  <div class="header-section">
    <h1 class="main-title" onclick="window.location.href='index.php'" style="cursor: pointer;">
      <i class="fas fa-search"></i>
      磁力链接查询
    </h1>
    <p class="subtitle">智能翻译 · 精准搜索 · 支持番号、关键词、演员搜索</p>
  </div>

  <ul class="am-nav am-nav-pills am-nav-justify search-tabs" id="searchTabs">
    <li class="am-active">
      <a href="#" onclick="setMode('keyword')">
        <i class="fas fa-key"></i> 关键词
      </a>
    </li>
    <li>
      <a href="#" onclick="setMode('code')">
        <i class="fas fa-code"></i> 番号
      </a>
    </li>
    <li>
      <a href="#" onclick="setMode('star')">
        <i class="fas fa-star"></i> 演员
      </a>
    </li>
  </ul>

  <form class="search-form" id="searchForm" onsubmit="search(); return false;">
    <div class="search-input-group">
      <input type="text" id="keyword" class="search-input" placeholder="请输入关键词、番号或演员名称..." autocomplete="off">
      <div class="search-history-dropdown" id="searchHistoryDropdown" style="display: none;">
        <div class="search-history-header">
          <span><i class="fas fa-history"></i> 搜索历史</span>
          <button type="button" class="clear-history-btn" onclick="clearSearchHistory()">
            <i class="fas fa-trash"></i> 清空
          </button>
        </div>
        <div class="search-history-list" id="searchHistoryList">
          <!-- 历史记录将在这里动态生成 -->
        </div>
      </div>
      <div class="search-input-icons">
        <button type="button" class="history-btn" onclick="toggleSearchHistory()" title="搜索历史">
          <i class="fas fa-history"></i>
        </button>
        <i class="fas fa-search search-icon"></i>
      </div>
    </div>

    <div class="options-section" id="translationSection">
      <div class="option-group">
        <div class="checkbox-group">
          <label class="checkbox-item" for="autoTranslate">
            <input type="checkbox" id="autoTranslate" checked>
            <i class="fas fa-language"></i>
            自动翻译中文为日文
          </label>
        </div>
      </div>

      <div class="option-group" id="translationOptions" style="display: none;">
        <div class="option-label">
          <i class="fas fa-cogs"></i>
          翻译服务
        </div>
        <div class="radio-group">
          <label class="radio-item active" for="deeplx">
            <input type="radio" name="translationService" value="deeplx" id="deeplx" checked>
            <i class="fas fa-rocket"></i> DeepLX翻译
          </label>
          <label class="radio-item" for="baidu">
            <input type="radio" name="translationService" value="baidu" id="baidu">
            <i class="fab fa-baidu"></i> 百度翻译
          </label>
          <label class="radio-item" for="youdao">
            <input type="radio" name="translationService" value="youdao" id="youdao">
            <i class="fas fa-book"></i> 有道翻译
          </label>
          <label class="radio-item" for="google">
            <input type="radio" name="translationService" value="google" id="google">
            <i class="fab fa-google"></i> Google翻译
          </label>
          <label class="radio-item" for="auto">
            <input type="radio" name="translationService" value="auto" id="auto">
            <i class="fas fa-magic"></i> 自动选择
          </label>
        </div>
      </div>
    </div>

    <div class="options-section" id="sortOptions">
      <div class="option-group">
        <div class="option-label">
          <i class="fas fa-sort"></i>
          排序方式
        </div>
        <div class="radio-group">
          <label class="radio-item active" for="sortDate">
            <input type="radio" name="sortBy" value="date" id="sortDate" checked>
            <i class="fas fa-calendar"></i> 按日期
          </label>
          <label class="radio-item" for="sortSize">
            <input type="radio" name="sortBy" value="size" id="sortSize">
            <i class="fas fa-hdd"></i> 按大小
          </label>
        </div>
      </div>
      <div class="option-group">
        <div class="option-label">
          <i class="fas fa-sort-amount-down"></i>
          排序顺序
        </div>
        <div class="radio-group">
          <label class="radio-item active" for="sortDesc">
            <input type="radio" name="sortOrder" value="desc" id="sortDesc" checked>
            <i class="fas fa-sort-amount-down"></i> 降序
          </label>
          <label class="radio-item" for="sortAsc">
            <input type="radio" name="sortOrder" value="asc" id="sortAsc">
            <i class="fas fa-sort-amount-up"></i> 升序
          </label>
        </div>
      </div>
    </div>

    <button type="submit" class="search-btn" id="searchButton">
      <i class="fas fa-search"></i>
      开始搜索
    </button>
  </form>

  <div class="progress-container" id="progressBarContainer">
    <div class="progress-label" id="progressLabel">
      <i class="fas fa-spinner"></i>
      正在查询...
    </div>
    <div class="progress-bar-container">
      <div class="progress-bar"></div>
    </div>
  </div>

  <div class="alert-box alert-danger" id="noResult" style="display: none;">
    <h4><i class="fas fa-exclamation-triangle"></i> 没有找到相关结果</h4>
    <p>请尝试以下建议来优化您的搜索：</p>
    <ul>
      <li><strong>番号搜索：</strong>格式请按【STAR-433】或【STARD433】</li>
      <li><strong>演员搜索：</strong>建议使用日文名称，如【みづなれい】</li>
      <li><strong>关键词搜索：</strong>尝试使用更简短的关键词</li>
      <li><strong>翻译功能：</strong>开启自动翻译可提高搜索成功率</li>
    </ul>
  </div>

  <div id="results" class="fade-in"></div>
  
  <button id="loadMoreBtn" class="am-btn am-btn-block load-more-btn" onclick="loadMore()" style="display: none;">
    <i class="fas fa-plus"></i>
    加载更多内容
  </button>

  <p class="am-text-center" style="margin-top: 30px; color: var(--info-color);">
    <i class="fas fa-code"></i>
    Mod By: 宿命 v1.1.0 © 2025 
    <a href="#" style="color: var(--primary-color); text-decoration: none;">
      <i class="fas fa-external-link-alt"></i>
      版权声明
    </a>
  </p>
</div>

<script src="https://cdn.staticfile.org/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.staticfile.org/crypto-js/4.1.1/crypto-js.min.js"></script>
<script src="script.js"></script>
</body>
</html>
