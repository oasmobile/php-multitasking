# oasis/multitasking

PHP 多任务（多进程）工具库，基于 `pcntl` / System V IPC 扩展，提供后台 worker 管理、信号量、消息队列和共享内存等原语。

---

## 技术栈

| 项目 | 值 |
|------|----|
| 语言 | PHP ≥ 8.2 |
| 包管理 | Composer |
| 包名 | `oasis/multitasking` |
| 命名空间 | `Oasis\Mlib\` |
| 自动加载 | PSR-4（`src/`） |
| 运行时依赖 | `ext-pcntl`、`oasis/logging ^3.0`、`oasis/event ^2.0`（`oasis/event` 目标为 `^3.0`；3.x 见 Packagist 与 release 说明） |
| 测试框架 | PHPUnit ^11.0 |
| 许可证 | MIT |

---

## 构建 / 测试命令

```bash
# 安装依赖
composer install

# 运行全量测试
vendor/bin/phpunit

# 运行单个测试文件
vendor/bin/phpunit ut/BackgroundWorkerManagerTest.php
```

---

## 目录结构

```
src/Multitasking/       # 源代码
ut/                     # 单元测试
docs/                   # 文档（state / manual / proposals / notes / changes）
issues/                 # 已确认问题
```

---

## 版本号位置

- `composer.json` → `version` 字段（当前未显式声明，由 Packagist / Git tag 决定）

---

## 敏感文件

| 文件 / 目录 | 说明 |
|-------------|------|
| `.env*` | 环境变量（当前不存在，但应始终排除） |
| `vendor/` | 第三方依赖，已在 `.gitignore` |
| `.idea/` | IDE 配置，已在 `.gitignore` |
