# 手工测试与 stabilize 复验（3.0.0）

> Alpha tag: **`v3.0.0-alpha.1`**（打标时间以 `git show v3.0.0-alpha.1` 为准）

---

## 环境

- **OS**：以执行机为准（stabilize 在本地/CI 复跑时可在本表补一行环境说明）
- **PHP**：`php -v` 在跑测时输出（示例次轮 CI：`PHP 8.5.4`）
- **依赖**：`composer install --no-interaction` 使用仓库内 **`composer.lock`**

---

## 已执行步骤

| 步骤 | 命令 | 结果 |
|------|------|------|
| 干净安装 | `composer install --no-interaction` | 成功；lock 可复现、无依赖冲突 |
| 全量测试 | `vendor/bin/phpunit` | **25 tests，2806 assertions，全部通过**；输出无 deprecation / 异常堆栈（以当次运行为准） |

---

## 范围与说明

- 本库依赖 **`ext-pcntl`、System V IPC**（`sysvmsg` / `sysvsem` / `sysvshm` 等），未满足扩展时相关用例会失败，不计入本仓库 stabilize「逻辑回归」的缺陷，而视为环境前提。
- **Monolog 3** 经 **`oasis/logging` 3.x** 传递引入；如下游固定旧版 `monolog/monolog`，需自行解析约束。

---

## 遗留与风险

- 无已知的 3.0.0 阻塞问题（若后续发现请在 `issues/` 记录并回链至本 tag）。
