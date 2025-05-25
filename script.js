/* 磁力链接查询系统脚本文件 v1.1.0 */

let currentMode = 'keyword';
let currentPage = 1;
let currentKeyword = '';
let isSearching = false;

// 搜索历史和缓存管理
const STORAGE_KEYS = {
  SEARCH_HISTORY: 'magnetSearch_history',
  SEARCH_CACHE: 'magnetSearch_cache'
};

// 帮助面板折叠功能
function toggleHelp(element) {
  const content = element.parentNode.querySelector('.help-content');
  const icon = element.querySelector('.toggle-icon');
  
  const isHidden = window.getComputedStyle(content).display === 'none';
  
  if (isHidden) {
    content.style.display = 'block';
    icon.classList.remove('fa-chevron-down');
    icon.classList.add('fa-chevron-up');
  } else {
    content.style.display = 'none';
    icon.classList.remove('fa-chevron-up');
    icon.classList.add('fa-chevron-down');
  }
}

const MAX_HISTORY_ITEMS = 20;
const CACHE_EXPIRE_TIME = 30 * 60 * 1000; // 30分钟

// 获取搜索历史
function getSearchHistory() {
  try {
    const history = localStorage.getItem(STORAGE_KEYS.SEARCH_HISTORY);
    return history ? JSON.parse(history) : [];
  } catch (error) {
    console.error('获取搜索历史失败:', error);
    return [];
  }
}

// 保存搜索历史
function saveSearchHistory(keyword, mode, translationUsed = false) {
  try {
    const history = getSearchHistory();
    const newItem = {
      keyword: keyword,
      mode: mode,
      translationUsed: translationUsed,
      timestamp: Date.now(),
      id: Date.now() + Math.random()
    };

    // 移除重复项
    const filteredHistory = history.filter(item => 
      !(item.keyword === keyword && item.mode === mode)
    );

    // 添加到开头
    filteredHistory.unshift(newItem);

    // 限制历史记录数量
    const limitedHistory = filteredHistory.slice(0, MAX_HISTORY_ITEMS);

    localStorage.setItem(STORAGE_KEYS.SEARCH_HISTORY, JSON.stringify(limitedHistory));
    updateHistoryDisplay();
  } catch (error) {
    console.error('保存搜索历史失败:', error);
  }
}

// 删除单个历史记录
function deleteHistoryItem(id) {
  try {
    const history = getSearchHistory();
    const filteredHistory = history.filter(item => item.id !== id);
    localStorage.setItem(STORAGE_KEYS.SEARCH_HISTORY, JSON.stringify(filteredHistory));
    updateHistoryDisplay();
    showToast('已删除历史记录', 'success');
  } catch (error) {
    console.error('删除历史记录失败:', error);
  }
}

// 清空搜索历史
function clearSearchHistory() {
  try {
    localStorage.removeItem(STORAGE_KEYS.SEARCH_HISTORY);
    updateHistoryDisplay();
    $('#searchHistoryDropdown').hide();
    showToast('已清空搜索历史', 'success');
  } catch (error) {
    console.error('清空搜索历史失败:', error);
  }
}

// 更新历史记录显示
function updateHistoryDisplay() {
  const history = getSearchHistory();
  const $historyList = $('#searchHistoryList');

  if (history.length === 0) {
    $historyList.html(`
      <div class="no-history">
        <i class="fas fa-history"></i><br>
        暂无搜索历史
      </div>
    `);
    return;
  }

  const historyHtml = history.map(item => {
    const modeNames = {
      'keyword': '关键词',
      'code': '番号',
      'star': '演员'
    };
    const modeIcon = {
      'keyword': 'fas fa-key',
      'code': 'fas fa-code',
      'star': 'fas fa-star'
    };
    const timeAgo = getTimeAgo(item.timestamp);

    return `
      <div class="history-item" onclick="selectHistoryItem('${item.keyword}', '${item.mode}')">
        <div class="history-content">
          <i class="${modeIcon[item.mode]}" style="color: var(--primary-color);"></i>
          <span class="history-text">${item.keyword}</span>
          <span class="history-mode">[${modeNames[item.mode]}]</span>
          ${item.translationUsed ? '<i class="fas fa-language" style="color: var(--warning-color); margin-left: 5px;" title="使用了翻译"></i>' : ''}
        </div>
        <div class="history-actions">
          <span style="font-size: 10px; color: var(--info-color); margin-right: 5px;">${timeAgo}</span>
          <button class="history-action-btn" onclick="event.stopPropagation(); deleteHistoryItem(${item.id})" title="删除">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    `;
  }).join('');

  $historyList.html(historyHtml);
}

// 选择历史记录项
function selectHistoryItem(keyword, mode) {
  $('#keyword').val(keyword);
  setMode(mode);
  $('#searchHistoryDropdown').hide();
  search();
}

// 切换搜索历史显示
function toggleSearchHistory() {
  const $dropdown = $('#searchHistoryDropdown');
  if ($dropdown.is(':visible')) {
    $dropdown.hide();
  } else {
    updateHistoryDisplay();
    $dropdown.show();
  }
}

// 获取时间差描述
function getTimeAgo(timestamp) {
  const now = Date.now();
  const diff = now - timestamp;
  const minutes = Math.floor(diff / (1000 * 60));
  const hours = Math.floor(diff / (1000 * 60 * 60));
  const days = Math.floor(diff / (1000 * 60 * 60 * 24));

  if (minutes < 1) return '刚刚';
  if (minutes < 60) return `${minutes}分钟前`;
  if (hours < 24) return `${hours}小时前`;
  if (days < 7) return `${days}天前`;
  return new Date(timestamp).toLocaleDateString();
}

// 缓存管理
function getCacheKey(mode, keyword, page, sortBy, sortOrder) {
  return `${mode}_${keyword}_${page}_${sortBy}_${sortOrder}`;
}

// 获取缓存数据
function getCachedData(cacheKey) {
  try {
    const cache = localStorage.getItem(STORAGE_KEYS.SEARCH_CACHE);
    if (!cache) return null;

    const cacheData = JSON.parse(cache);
    const item = cacheData[cacheKey];

    if (!item) return null;

    // 检查是否过期
    if (Date.now() - item.timestamp > CACHE_EXPIRE_TIME) {
      delete cacheData[cacheKey];
      localStorage.setItem(STORAGE_KEYS.SEARCH_CACHE, JSON.stringify(cacheData));
      return null;
    }

    return item.data;
  } catch (error) {
    console.error('获取缓存失败:', error);
    return null;
  }
}

// 保存缓存数据
function setCachedData(cacheKey, data) {
  try {
    const cache = localStorage.getItem(STORAGE_KEYS.SEARCH_CACHE);
    const cacheData = cache ? JSON.parse(cache) : {};

    cacheData[cacheKey] = {
      data: data,
      timestamp: Date.now()
    };

    // 限制缓存大小，只保留最新的50个
    const keys = Object.keys(cacheData);
    if (keys.length > 50) {
      const sortedKeys = keys.sort((a, b) => cacheData[b].timestamp - cacheData[a].timestamp);
      const keysToKeep = sortedKeys.slice(0, 50);
      const newCacheData = {};
      keysToKeep.forEach(key => {
        newCacheData[key] = cacheData[key];
      });
      localStorage.setItem(STORAGE_KEYS.SEARCH_CACHE, JSON.stringify(newCacheData));
    } else {
      localStorage.setItem(STORAGE_KEYS.SEARCH_CACHE, JSON.stringify(cacheData));
    }
  } catch (error) {
    console.error('保存缓存失败:', error);
  }
}

// 显示缓存指示器
function showCacheIndicator(fromCache = false) {
  const $indicator = $(`
    <div class="cache-indicator ${fromCache ? 'from-cache' : ''}">
      <i class="fas fa-${fromCache ? 'clock' : 'save'}"></i>
      ${fromCache ? '来自缓存' : '已缓存'}
    </div>
  `);

  $('body').append($indicator);
  
  setTimeout(() => $indicator.addClass('show'), 100);
  setTimeout(() => {
    $indicator.removeClass('show');
    setTimeout(() => $indicator.remove(), 300);
  }, 2000);
}

// 显示翻译状态
function showTranslationStatus(message, type = 'info') {
  // 移除之前的状态提示
  $('.translation-status').remove();
  
  const statusHtml = `
    <div class="translation-status" style="
      position: fixed;
      top: 80px;
      right: 20px;
      background: ${type === 'success' ? 'var(--success-color)' : type === 'error' ? 'var(--danger-color)' : '#5dade2'};
      color: white;
      padding: 12px 20px;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      z-index: 1001;
      max-width: 300px;
      font-size: 14px;
      animation: slideInRight 0.3s ease;
    ">
      <i class="fas fa-language" style="margin-right: 8px;"></i>
      ${message}
    </div>
  `;
  
  $('body').append(statusHtml);
  
  // 3秒后自动移除
  setTimeout(() => {
    $('.translation-status').fadeOut(300, function() {
      $(this).remove();
    });
  }, 3000);
}

// 翻译函数 - 调用服务端API（增强版本）
async function translateToJapanese(text) {
  const translationService = $('input[name="translationService"]:checked').val() || 'deeplx';
  const maxRetries = 3;
  const baseDelay = 1000; // 1秒基础延迟
  
  // 显示开始翻译的状态
  showTranslationStatus(`开始翻译: "${text.length > 20 ? text.substring(0, 20) + '...' : text}"`);
  
  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      console.log(`翻译尝试 ${attempt}/${maxRetries}: "${text}" (服务: ${translationService})`);
      
      // 更新进度提示
      if (attempt > 1) {
        updateProgress(`翻译重试中 (${attempt}/${maxRetries})...`);
        showTranslationStatus(`重试翻译 (${attempt}/${maxRetries})...`, 'info');
      }
      
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 15000); // 15秒超时
      
      const response = await fetch('translate-api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          text: text,
          service: translationService
        }),
        signal: controller.signal
      });

      clearTimeout(timeoutId);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();
      console.log(`翻译尝试 ${attempt} 响应:`, data);
      
      if (data.success && data.translated && data.translated !== text) {
        console.log(`翻译成功 (尝试 ${attempt}): "${text}" -> "${data.translated}"`);
        showTranslationStatus(`翻译成功: "${data.translated}"`, 'success');
        return data.translated;
      } else if (data.error) {
        throw new Error(`API错误: ${data.error}`);
      } else {
        throw new Error('翻译结果为空或与原文相同');
      }
      
    } catch (error) {
      console.error(`翻译尝试 ${attempt} 失败:`, error);
      
      if (attempt < maxRetries) {
        // 指数退避延迟
        const delay = baseDelay * Math.pow(2, attempt - 1);
        console.log(`将在 ${delay}ms 后重试...`);
        
        // 更新进度提示
        updateProgress(`翻译失败，${delay/1000}秒后重试...`);
        showTranslationStatus(`翻译失败，${delay/1000}秒后重试 (${attempt}/${maxRetries})`, 'error');
        
        await new Promise(resolve => setTimeout(resolve, delay));
      } else {
        // 所有重试都失败
        console.error('所有翻译重试都失败，使用原文');
        
        let errorMsg = '翻译服务暂时不可用';
        if (error.name === 'AbortError') {
          errorMsg = '翻译请求超时';
        } else if (error.message.includes('HTTP 429')) {
          errorMsg = '翻译请求频率过高，请稍后再试';
        } else if (error.message.includes('HTTP 500')) {
          errorMsg = '翻译服务器错误';
        } else if (error.message.includes('Failed to fetch')) {
          errorMsg = '网络连接失败';
        }
        
        showTranslationStatus(`${errorMsg}，使用原文`, 'error');
        showToast(`${errorMsg}，使用原文搜索`, 'warning');
      }
    }
  }
  
  return text;
}

// 显示提示消息
function showToast(message, type = 'success') {
  const toast = $(`
    <div class="success-toast" style="background: ${type === 'success' ? 'var(--success-color)' : 'var(--warning-color)'}">
      <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
      ${message}
    </div>
  `);
  
  $('body').append(toast);
  
  setTimeout(() => {
    toast.fadeOut(300, () => toast.remove());
  }, 3000);
}

// 设置搜索模式
function setMode(mode) {
  if (isSearching) return;
  
  currentMode = mode;
  currentPage = 1;
  
  // 更新标签页状态
  $('#searchTabs li').removeClass('am-active');
  $(`#searchTabs li:has(a[onclick*="${mode}"])`).addClass('am-active');
  
  // 清空结果
  $('#results').empty();
  $('#loadMoreBtn').hide();
  $('#noResult').hide();
  
  // 显示/隐藏排序选项
  if (mode === 'code') {
    $('#sortOptions').slideDown(300);
  } else {
    $('#sortOptions').slideUp(300);
  }
  
  // 更新输入框占位符
  const placeholders = {
    'keyword': '请输入关键词...',
    'code': '请输入番号，如：STAR-433',
    'star': '请输入演员名称...'
  };
  $('#keyword').attr('placeholder', placeholders[mode]);
}

// 更新进度显示
function updateProgress(text, show = true) {
  if (show) {
    $('#progressLabel').html(`<i class="fas fa-spinner"></i> ${text}`);
    $('#progressBarContainer').slideDown(300);
  } else {
    $('#progressBarContainer').slideUp(300);
  }
}

// 初始化图片懒加载
function initLazyLoading() {
  if (!('IntersectionObserver' in window)) {
    // 如果浏览器不支持IntersectionObserver，则直接加载所有图片
    document.querySelectorAll('img[data-src]').forEach(img => {
      img.src = img.dataset.src;
    });
    return;
  }

  const imgObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        img.src = img.dataset.src;
        img.onload = () => img.classList.add('fade-in');
        img.onerror = () => handleImageError(img);
        observer.unobserve(img);
      }
    });
  }, {
    rootMargin: '50px 0px',
    threshold: 0.01
  });

  document.querySelectorAll('img[data-src]').forEach(img => {
    imgObserver.observe(img);
  });
}

// 图片URL缓存系统 - 记住哪种URL格式对哪类番号有效
const IMAGE_URL_CACHE = {
  // 格式: {'番号前缀': '成功URL模板'}
};

// 处理图片加载错误
function handleImageError(img) {
  const $img = $(img);
  const movieId = $img.data('movieId');
  const alternateUrls = $img.data('alternateUrls');
  const currentSrc = $img.attr('src');
  
  console.log(`图片加载失败: ${currentSrc}`);
  
  // 尝试提取番号前缀
  const prefixMatch = movieId.match(/^([a-zA-Z]+)/);
  const prefix = prefixMatch ? prefixMatch[1].toLowerCase() : '';
  
  // 检查是否有该前缀的缓存成功URL
  if (prefix && IMAGE_URL_CACHE[prefix]) {
    const cachedTemplate = IMAGE_URL_CACHE[prefix];
    const newUrl = cachedTemplate.replace('{id}', movieId);
    console.log(`使用缓存的URL模板: ${newUrl}`);
    $img.attr('src', newUrl);
    return;
  }
  
  // 如果有备用URL，尝试下一个
  if (alternateUrls && Array.isArray(alternateUrls)) {
    const currentIndex = alternateUrls.indexOf(currentSrc);
    if (currentIndex >= 0 && currentIndex < alternateUrls.length - 1) {
      const nextUrl = alternateUrls[currentIndex + 1];
      console.log(`尝试下一个备用URL: ${nextUrl}`);
      $img.attr('src', nextUrl);
      return;
    }
  }
  
  // 所有备用URL都失败，尝试通过番号API获取图片
  console.log(`所有备用URL都失败，尝试通过番号搜索获取图片: ${movieId}`);
  fetchImageByCode(movieId, $img);
}

// 通过番号搜索API获取图片
function fetchImageByCode(code, $img) {
  // 显示加载占位图
  $img.attr('src', 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22120%22%20height%3D%22145%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20120%20145%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3A%20Arial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder%22%3E%3Crect%20width%3D%22120%22%20height%3D%22145%22%20fill%3D%22%23eee%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20id%3D%22holder_text%22%20x%3D%2236%22%20y%3D%2277.8%22%3E加载中%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E');
  
  // 请求番号信息
  fetch(`api.php?mode=code&keyword=${encodeURIComponent(code)}`)
    .then(r => r.json())
    .then(d => {
      const htmlContent = $(d.html);
      const newImgSrc = htmlContent.find('img').attr('src');
      
      if (newImgSrc) {
        // 成功获取图片URL
        console.log(`从番号搜索获取到图片: ${newImgSrc}`);
        $img.attr('src', newImgSrc);
        
        // 保存成功的URL模板到缓存
        const prefixMatch = code.match(/^([a-zA-Z]+)/);
        if (prefixMatch) {
          const prefix = prefixMatch[1].toLowerCase();
          const urlTemplate = createUrlTemplate(newImgSrc, code);
          IMAGE_URL_CACHE[prefix] = urlTemplate;
          console.log(`缓存URL模板 [${prefix}]: ${urlTemplate}`);
        }
      } else {
        // 最终失败，显示默认占位图
        setDefaultErrorImage($img);
      }
    })
    .catch(error => {
      console.error(`番号搜索请求失败: ${error}`);
      setDefaultErrorImage($img);
    });
}

// 创建URL模板，用于缓存
function createUrlTemplate(url, movieId) {
  return url.replace(movieId, '{id}');
}

// 设置默认错误占位图
function setDefaultErrorImage(img) {
  const $img = $(img);
  $img.attr('src', 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22120%22%20height%3D%22145%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20120%20145%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3A%20Arial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder%22%3E%3Crect%20width%3D%22120%22%20height%3D%22145%22%20fill%3D%22%23eee%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20id%3D%22holder_text%22%20x%3D%2236%22%20y%3D%2277.8%22%3E无图片%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E');
  $img.addClass('image-error');
}

// 图片代理函数 - 修复防盗链问题
function proxyImageUrl(url) {
  if (!url) return '';
  
  // 如果已经是数据URI或占位图，直接返回
  if (url.startsWith('data:')) {
    return url;
  }
  
  // 直接返回原始URL，不使用代理
  return url;
}

// 主搜索函数
async function search(isLoadMore = false) {
  if (isSearching && !isLoadMore) return;
  
  if (!isLoadMore) {
    currentPage = 1;
    $('#results').empty();
    $('#loadMoreBtn').hide();
    $('#noResult').hide();
    isSearching = true;
    
    // 更新按钮状态
    const $searchBtn = $('#searchButton');
    $searchBtn.addClass('loading').prop('disabled', true);
    $searchBtn.html('<i class="fas fa-spinner fa-spin"></i> 搜索中...');
  }

  let keyword = $('#keyword').val().trim();
  if (!keyword) {
    if (!isLoadMore) {
      isSearching = false;
      resetSearchButton();
    }
    return;
  }

  try {
    let translationUsed = false;
    let originalKeyword = keyword;

    // 检查是否需要翻译
    if ($('#autoTranslate').is(':checked')) {
      updateProgress('正在智能翻译...');
      const translatedKeyword = await translateToJapanese(keyword);
      if (translatedKeyword !== keyword) {
        updateProgress(`翻译完成: "${keyword}" → "${translatedKeyword}"`);
        keyword = translatedKeyword;
        $('#keyword').val(keyword);
        translationUsed = true;
        await new Promise(resolve => setTimeout(resolve, 1000));
      }
    }

    currentKeyword = keyword;
    const sortBy = $('input[name=sortBy]:checked').val();
    const sortOrder = $('input[name=sortOrder]:checked').val();

    // 生成缓存键
    const cacheKey = getCacheKey(currentMode, keyword, currentPage, sortBy, sortOrder);

    // 检查缓存
    if (!isLoadMore) {
      const cachedData = getCachedData(cacheKey);
      if (cachedData) {
        updateProgress('正在加载缓存数据...');
        
        // 使用缓存数据
        if (cachedData.html) {
          const htmlDom = $('<div>' + cachedData.html + '</div>');
          $('#results').html(htmlDom.html()).addClass('fade-in');

          if (cachedData.hasMore) {
            $('#loadMoreBtn').slideDown(300);
            currentPage++;
          } else {
            $('#loadMoreBtn').slideUp(300);
          }

          showCacheIndicator(true);
          showToast('已从缓存加载结果', 'success');
        } else {
          $('#noResult').slideDown(300);
        }

        updateProgress("", false);
        
        // 保存搜索历史
        saveSearchHistory(originalKeyword, currentMode, translationUsed);
        
        // 重置搜索状态
        isSearching = false;
        resetSearchButton();
        
        return;
      }
    }

    updateProgress('正在获取影片信息...');

    const res = await fetch(`api.php?mode=${currentMode}&keyword=${encodeURIComponent(keyword)}&page=${currentPage}&sortBy=${sortBy}&sortOrder=${sortOrder}`);
    const data = await res.json();

    // 保存到缓存
    if (!isLoadMore) {
      setCachedData(cacheKey, data);
      if (data.html) {
        showCacheIndicator(false);
      }
    }

    if (!data.html) {
      if (!isLoadMore) {
        $('#noResult').slideDown(300);
        showToast('未找到相关结果，请尝试其他关键词', 'warning');
        // 保存搜索历史（即使没有结果）
        saveSearchHistory(originalKeyword, currentMode, translationUsed);
        // 重置搜索状态
        isSearching = false;
        resetSearchButton();
      }
      updateProgress("", false);
      return;
    }

    // 修改此部分来使用直接加载而非懒加载
    const htmlDom = $('<div>' + data.html + '</div>');

    // 处理图片防盗链
    htmlDom.find('.card img').each(function() {
      const $img = $(this);
      const originalSrc = $img.attr('src');
      // 直接设置src，不使用懒加载
      $img.attr('src', proxyImageUrl(originalSrc));
      $img.on('error', function() {
        handleImageError(this);
      });
      $img.on('load', function() {
        $(this).addClass('fade-in');
      });
    });
    
    if (isLoadMore) {
      $('#results').append(htmlDom.children().addClass('slide-up'));
    } else {
      $('#results').html(htmlDom.html()).addClass('fade-in');
    }

    if (data.hasMore) {
      $('#loadMoreBtn').slideDown(300);
      currentPage++;
    } else {
      $('#loadMoreBtn').slideUp(300);
    }

    updateProgress('正在优化封面图片...');

    // 异步获取高清图
    const cards = htmlDom.find('.card');
    const promises = [];
    
    console.log(`开始异步获取 ${cards.length} 个高清图片`);
    
    cards.each(function () {
      const card = $(this);
      const idMatch = card.attr('onclick')?.match(/'(.*?)'/);
      if (!idMatch) return;
      const id = idMatch[1];
      
      console.log(`准备获取番号 ${id} 的高清图`);
      
      promises.push(
        fetch(`api.php?mode=code&keyword=${id}`)
          .then(r => {
            console.log(`番号 ${id} 请求响应:`, r.status);
            return r.json();
          })
          .then(d => {
            console.log(`番号 ${id} 返回数据:`, d);
            const newImg = $(d.html).find('img').attr('src');
            console.log(`番号 ${id} 提取的图片URL:`, newImg);
            
            if (newImg) {
              // 通过番号在实际DOM中查找对应的图片元素
              const $realImg = $(`#results .card img[data-movie-id="${id}"]`);
              
              if ($realImg.length > 0) {
                const currentSrc = $realImg.attr('src');
                
                console.log(`番号 ${id} 当前图片:`, currentSrc);
                console.log(`番号 ${id} 新图片:`, newImg);
                
                // 只有当前是占位图时才替换
                if (currentSrc && currentSrc.startsWith('data:image/svg+xml')) {
                  console.log(`番号 ${id} 开始替换图片`);
                  
                  // 添加淡入效果
                  $realImg.addClass('loading');
                  
                  // 预加载新图片
                  const tempImg = new Image();
                  tempImg.onload = function() {
                    console.log(`番号 ${id} 图片预加载成功，开始替换`);
                    $realImg.attr('src', newImg);
                    $realImg.removeClass('loading').addClass('fade-in');
                  };
                  tempImg.onerror = function() {
                    console.log(`番号 ${id} 图片预加载失败`);
                    // 如果高清图加载失败，显示错误占位图
                    const errorPlaceholder = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22120%22%20height%3D%22145%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20120%20145%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_text%20%7B%20fill%3A%23999%3Bfont-weight%3Anormal%3Bfont-family%3A%20Arial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder%22%3E%3Crect%20width%3D%22120%22%20height%3D%22145%22%20fill%3D%22%23f0f0f0%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20id%3D%22holder_text%22%20x%3D%2260%22%20y%3D%2270%22%20text-anchor%3D%22middle%22%3E%3Ctspan%20x%3D%2260%22%20dy%3D%22-5%22%3E%E2%9A%A0%EF%B8%8F%3C%2Ftspan%3E%3Ctspan%20x%3D%2260%22%20dy%3D%2215%22%3E无图片%3C%2Ftspan%3E%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
                    $realImg.attr('src', errorPlaceholder);
                    $realImg.removeClass('loading').addClass('image-error');
                  };
                  tempImg.src = newImg;
                } else {
                  console.log(`番号 ${id} 当前不是占位图，跳过替换`);
                }
              } else {
                console.log(`番号 ${id} 在DOM中未找到对应的图片元素`);
              }
            } else {
              console.log(`番号 ${id} 没有找到图片URL`);
            }
          })
          .catch((error) => {
            console.log(`获取${id}高清图失败:`, error);
            // 失败时保持占位图不变
          })
      );
    });

    await Promise.allSettled(promises);
    
    if (!isLoadMore) {
      showToast(`搜索完成，找到 ${cards.length} 个结果`, 'success');
      // 保存搜索历史
      saveSearchHistory(originalKeyword, currentMode, translationUsed);
    }
    
    updateProgress("", false);

  } catch (error) {
    console.error('搜索失败:', error);
    showToast('搜索请求失败，请检查网络连接', 'warning');
    updateProgress("", false);
  } finally {
    if (!isLoadMore) {
      isSearching = false;
      resetSearchButton();
    }
  }
}

// 重置搜索按钮状态
function resetSearchButton() {
  const $searchBtn = $('#searchButton');
  $searchBtn.removeClass('loading').prop('disabled', false);
  $searchBtn.html('<i class="fas fa-search"></i> 开始搜索');
}

// 加载更多
function loadMore() {
  if (isSearching) return;
  
  const $loadBtn = $('#loadMoreBtn');
  $loadBtn.html('<i class="fas fa-spinner fa-spin"></i> 加载中...');
  
  search(true).finally(() => {
    $loadBtn.html('<i class="fas fa-plus"></i> 加载更多内容');
  });
}

// 打开番号搜索
function openCodeSearch(code) {
  $('#keyword').val(code);
  setMode('code');
  search();
}

// 复制文本
function copyText(inputId) {
  const input = document.getElementById(inputId);
  input.select();
  input.setSelectionRange(0, 99999);
  try {
    document.execCommand('copy');
    showToast('已复制到剪贴板', 'success');
  } catch (err) {
    showToast('复制失败，请手动复制', 'warning');
  }
}

// 判断元素是否在可视区域内
function isElementInViewport(el) {
  if (!el) return false;
  const rect = el.getBoundingClientRect();
  return (
    rect.top >= 0 &&
    rect.left >= 0 &&
    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
  );
}

// 页面初始化
$(document).ready(function() {
  // 翻译选项控制
  $('#autoTranslate').change(function() {
    if ($(this).is(':checked')) {
      $(this).closest('.checkbox-item').addClass('active');
      $('#translationOptions').slideDown(300);
    } else {
      $(this).closest('.checkbox-item').removeClass('active');
      $('#translationOptions').slideUp(300);
    }
  });

  // 默认激活翻译选项 - 手动触发change事件
  if ($('#autoTranslate').is(':checked')) {
    $('#autoTranslate').closest('.checkbox-item').addClass('active');
    $('#translationOptions').show();
  }

  // 单选框样式控制
  $('input[type="radio"]').change(function() {
    const name = $(this).attr('name');
    $(`input[name="${name}"]`).closest('.radio-item').removeClass('active');
    $(this).closest('.radio-item').addClass('active');
  });

  // 复选框样式控制
  $('input[type="checkbox"]').change(function() {
    if ($(this).is(':checked')) {
      $(this).closest('.checkbox-item').addClass('active');
    } else {
      $(this).closest('.checkbox-item').removeClass('active');
    }
  });

  // 搜索框回车事件
  $('#keyword').on('keypress', function(e) {
    if (e.which === 13) {
      search();
    }
  });

  // 搜索框输入事件
  $('#keyword').on('input', function() {
    const value = $(this).val();
    if (value.length > 0) {
      $(this).addClass('has-content');
    } else {
      $(this).removeClass('has-content');
    }
  });

  // 搜索历史相关事件
  // 点击外部关闭历史下拉框
  $(document).on('click', function(e) {
    if (!$(e.target).closest('.search-input-group').length) {
      $('#searchHistoryDropdown').hide();
    }
  });

  // 键盘导航支持
  $('#keyword').on('keydown', function(e) {
    const $dropdown = $('#searchHistoryDropdown');
    const $items = $dropdown.find('.history-item');
    
    if (!$dropdown.is(':visible')) return;
    
    let currentIndex = $items.index($items.filter('.selected'));
    
    switch(e.which) {
      case 38: // 上箭头
        e.preventDefault();
        if (currentIndex > 0) {
          $items.removeClass('selected');
          $items.eq(currentIndex - 1).addClass('selected');
        }
        break;
      case 40: // 下箭头
        e.preventDefault();
        if (currentIndex < $items.length - 1) {
          $items.removeClass('selected');
          $items.eq(currentIndex + 1).addClass('selected');
        }
        break;
      case 13: // 回车
        const $selected = $items.filter('.selected');
        if ($selected.length > 0) {
          e.preventDefault();
          $selected.click();
        }
        break;
      case 27: // ESC
        e.preventDefault();
        $dropdown.hide();
        break;
    }
  });

  // 历史记录项悬停效果
  $(document).on('mouseenter', '.history-item', function() {
    $('.history-item').removeClass('selected');
    $(this).addClass('selected');
  });

  // 初始化排序选项显示状态
  if (currentMode !== 'code') {
    $('#sortOptions').hide();
  }

  // 初始化搜索历史显示
  updateHistoryDisplay();

  // 添加页面加载动画
  $('.main-container').addClass('fade-in');
  
  // 清理过期缓存
  cleanExpiredCache();
  
  console.log('磁力链接查询系统已加载完成 v1.1.0 - 支持搜索历史和缓存');
});

// 清理过期缓存
function cleanExpiredCache() {
  try {
    const cache = localStorage.getItem(STORAGE_KEYS.SEARCH_CACHE);
    if (!cache) return;

    const cacheData = JSON.parse(cache);
    const now = Date.now();
    let hasExpired = false;

    Object.keys(cacheData).forEach(key => {
      if (now - cacheData[key].timestamp > CACHE_EXPIRE_TIME) {
        delete cacheData[key];
        hasExpired = true;
      }
    });

    if (hasExpired) {
      localStorage.setItem(STORAGE_KEYS.SEARCH_CACHE, JSON.stringify(cacheData));
      console.log('已清理过期缓存');
    }
  } catch (error) {
    console.error('清理缓存失败:', error);
  }
} 