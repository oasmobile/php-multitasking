# Changelog v3.0.2

本文件记录 v3.0.2 hotfix 的变更内容。

---

## 工程变更

- PHP 最低版本从 `>=8.2` 提升至 `>=8.5`
- PHPUnit 从 `^11.0` 升级至 `^13.0`
- eris（PBT 库）从 `^1.0` 升级至 `^1.1`
- 移除 PBT 测试中 `getTestCaseAnnotations()` workaround（eris ^1.1 已原生兼容 PHPUnit 13）
- 清理 `Semaphore` 构造函数冗余 PHPDoc

---

## 测试覆盖

- 全量测试通过：25 tests, 3034 assertions
