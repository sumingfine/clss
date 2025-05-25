# 🎬 磁力链接查询系统

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6%2B-yellow.svg)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

一个功能强大的磁力链接查询系统，专注于日本AV内容的搜索和磁力链接获取。支持多种搜索模式、智能翻译、图片防盗链处理等高级功能。

## ✨ 功能特点

### 🔍 多模式搜索
- **关键词搜索**：支持中文关键词智能翻译搜索
- **番号搜索**：直接输入番号获取详细信息和磁力链接
- **演员搜索**：按演员名称搜索相关作品

### 🌐 智能翻译系统
- **多翻译引擎**：支持DeepLX、百度、有道、Google翻译
- **演员名词典**：内置500+常见演员中日文对照
- **重试机制**：自动重试失败的翻译请求
- **实时状态**：显示翻译进度和结果

### 🖼️ 图片处理优化
- **防盗链解决**：智能处理javbus等网站的图片防盗链
- **占位符系统**：优雅的加载中效果
- **异步替换**：后台获取高清图片并平滑替换
- **多源备份**：支持多种图片源自动切换

### 💾 缓存与历史
- **搜索缓存**：30分钟本地缓存，提升访问速度
- **搜索历史**：记录最近20次搜索，支持快速重搜
- **智能清理**：自动清理过期缓存

### 🎨 用户体验
- **响应式设计**：完美适配桌面和移动设备
- **现代UI**：采用渐变色彩和流畅动画
- **快捷操作**：一键复制磁力链接
- **进度提示**：实时显示搜索和翻译进度

## 🚀 快速开始

### 环境要求

- PHP 7.4 或更高版本
- Web服务器（Apache/Nginx）
- cURL扩展支持
- 互联网连接

### 安装部署

1. **克隆项目**
```bash
git clone https://github.com/sumingfine/clss.git
cd magnet-search-system
```

2. **配置Web服务器**
```bash
# Apache用户确保启用了mod_rewrite
# Nginx用户配置PHP-FPM

# 设置目录权限
chmod 755 .
chmod 644 *.php *.html *.css *.js
```

3. **配置翻译服务（可选）**
```bash
# 编辑翻译配置文件
vim translate-config.php

# 根据需要配置API密钥
# DeepLX默认可用，其他服务需要申请API密钥
```

4. **访问系统**
```
https://yd.533133.xyz
```

## 📁 项目结构

```
magnet-search-system/
├── index.php              # 主页面
├── api.php                # 搜索API处理
├── script.js              # 前端JavaScript逻辑  
├── styles.css             # 样式文件
├── translate-api.php      # 翻译API处理
├── translate-config.php   # 翻译服务配置
├── README.md             # 项目文档
└── assets/               # 静态资源（如有）
```

## 🔧 配置说明

### 翻译服务配置

编辑 `translate-config.php` 文件：

```php
return [
    // DeepLX翻译（推荐，免费）
    'deeplx' => [
        'apiKey' => 'your-api-key',
        'baseUrl' => 'https://api.deeplx.org',
        'enabled' => true
    ],
    
    // 百度翻译
    'baidu' => [
        'appid' => 'YOUR_BAIDU_APPID',
        'key' => 'YOUR_BAIDU_KEY',
        'enabled' => false
    ],
    
    // 其他翻译服务...
];
```

### API配置

系统使用 `cl.533133.xyz` 作为数据源，认证信息已内置。如需更换：

1. 修改 `api.php` 中的 `$api_base`、`$admin_username`、`$admin_password`
2. 确保新API兼容现有接口格式

## 🎯 使用指南

### 基本搜索

1. **关键词搜索**
   - 输入中文关键词（如"麻美"）
   - 开启智能翻译获得更好效果
   - 系统会自动翻译为日文进行搜索

2. **番号搜索**  
   - 切换到"番号搜索"标签
   - 输入完整番号（如"STAR-433"）
   - 获取详细信息和磁力链接

3. **演员搜索**
   - 切换到"演员搜索"标签
   - 输入演员中文或日文名
   - 浏览该演员的所有作品

### 高级功能

- **搜索历史**：点击搜索框右侧历史图标
- **缓存管理**：系统自动管理，无需手动操作
- **翻译调试**：访问 `translate-api.php?debug=logs` 查看日志

## 🛠️ 技术栈

### 前端技术
- **HTML5 + CSS3**：现代Web标准
- **JavaScript ES6+**：原生JavaScript + jQuery
- **响应式设计**：Flexbox + Grid布局
- **动画效果**：CSS3 Transitions + Animations

### 后端技术  
- **PHP 7.4+**：服务端逻辑处理
- **cURL**：API请求和数据获取
- **JSON**：数据交换格式
- **文件缓存**：本地存储优化

### 第三方服务
- **搜索API**：cl.533133.xyz
- **翻译服务**：DeepLX, 百度, 有道, Google
- **图片服务**：DMM, javbus等多源支持

## 🔍 故障排除

### 常见问题

**Q: 翻译功能不工作？**
A: 检查翻译配置，确保API密钥正确。可访问翻译调试页面进行测试。

**Q: 图片无法显示？**  
A: 这是正常现象，系统会先显示占位图，然后异步加载高清图片。

**Q: 搜索结果为空？**
A: 可能是关键词问题，尝试使用日文关键词或切换到其他搜索模式。

**Q: 网站加载缓慢？**
A: 检查服务器网络连接，系统依赖外部API。

### 调试模式

启用调试功能：
```
# 查看API调试信息
http://your-domain.com/api.php?debug=1

# 查看翻译日志
http://your-domain.com/translate-api.php?debug=logs
```

## 📈 性能优化

### 建议配置

1. **PHP配置优化**
```ini
memory_limit = 128M
max_execution_time = 60
upload_max_filesize = 10M
```

2. **Web服务器缓存**
```apache
# Apache .htaccess
<FilesMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg)$">
    ExpiresActive On
    ExpiresDefault "access plus 1 month"
</FilesMatch>
```

3. **CDN加速**
- 可将静态资源部署到CDN
- 建议使用国内CDN提升访问速度

## 🤝 贡献指南

欢迎提交Issue和Pull Request！

### 开发环境搭建

1. Fork本项目
2. 创建特性分支：`git checkout -b feature/AmazingFeature`
3. 提交更改：`git commit -m 'Add some AmazingFeature'`
4. 推送分支：`git push origin feature/AmazingFeature`
5. 创建Pull Request

### 代码规范

- PHP代码遵循PSR-12标准
- JavaScript使用ES6+语法
- CSS使用BEM命名规范
- 提交信息使用约定式提交格式

## 📝 更新日志

### v1.1.0 (2024-01-XX)
- ✨ 新增智能翻译系统
- 🖼️ 优化图片防盗链处理
- 💾 添加搜索历史和缓存
- 🎨 全新UI设计
- 🐛 修复若干已知问题

### v1.0.0 (2024-01-XX)
- 🎉 初始版本发布
- 🔍 基础搜索功能
- 📱 响应式设计

## ⚖️ 免责声明

本项目仅供学习和研究使用，请遵守当地法律法规。使用本系统搜索和下载的内容，用户需自行承担相应责任。

## 📄 许可证

本项目采用 [MIT License](LICENSE) 开源协议。

## 🙏 致谢

- 感谢 [cl.533133.xyz](https://cl.533133.xyz) 提供的数据API
- 感谢 [DeepLX](https://github.com/OwO-Network/DeepLX) 提供的免费翻译服务
- 感谢所有贡献者的支持

---

<div align="center">

**⭐ 如果这个项目对你有帮助，请给个Star支持一下！ ⭐**

</div> 
