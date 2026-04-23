# Spec Goal: PHP 8 升级

## 来源

- 分支: `release/2.0.0`
- 需求文档: `docs/notes/php8-upgrade.md`

## 背景摘要

`oasis/multitasking` 当前基于 PHP ≥ 5.6.1 和 PHPUnit ^5.5 构建。随着 PHP 8.x 生态成熟，项目需要升级到现代 PHP 版本以获得语言新特性、性能改进和长期安全支持。

本次 release 2.0.0 是一个 breaking change 版本，将最低 PHP 版本从 5.6.1 提升至 8.2，同时将测试框架从 PHPUnit 5 升级至 PHPUnit 11。此外，源代码将进行 PHP 8 现代化改造，并引入 Property-Based Testing 库 `giorgiosironi/eris` 增强测试能力。

## 目标

- 将 `composer.json` 中 PHP 最低版本要求从 `>=5.6.1` 提升至 `>=8.2`
- 将 PHPUnit 从 `^5.5` 升级至 `^11.0`
- 适配 PHPUnit 11 的 API 变更（测试基类、断言方法、配置格式等）
- 对源代码进行 PHP 8 现代化改造（typed properties、union types、match 表达式、named arguments、constructor promotion 等）
- 引入 `giorgiosironi/eris`（dev 依赖）用于 Property-Based Testing
- 更新 `docs/state/` 反映新的技术选型
- 确保所有现有测试在新版本下通过

## 不做的事情（Non-Goals）

- 不改变现有公共 API 的行为语义
- 不新增功能特性
- 不迁移到其他 IPC 机制
- 不引入 PHP 8.4+ 专属特性（保持 8.2 兼容）

## Clarification 记录

### Q1: composer.json 中 PHP 最低版本要求的范围？

- 选项: A) `>=8.2`（支持 8.2 ~ 8.5+）/ B) `>=8.5`（只支持目标版本及以上）/ C) 补充说明
- 回答: A — `>=8.2`

### Q2: PHPUnit 升级的目标版本范围？

- 选项: A) `^11.0`（锁定 11.x）/ B) `^11.0 || ^12.0`（允许未来大版本）/ C) 补充说明
- 回答: A — `^11.0`

### Q3: 代码层面的 PHP 8 现代化范围？

- 选项: A) 仅升级依赖版本约束，不改动源代码语法 / B) 升级依赖 + 对源代码进行 PHP 8 现代化改造 / C) 升级依赖 + 仅修复兼容性问题 / D) 补充说明
- 回答: B — 升级依赖 + 对源代码进行 PHP 8 现代化改造

### Q4: Property-Based Testing

- 用户主动提出引入 `giorgiosironi/eris` 做 Property Testing
- Eris 兼容 PHP 8.1+ 和 PHPUnit 10.x/11.x/12.x/13.x，与本次升级目标完全匹配

## 约束与决策

- PHP 最低版本 `>=8.2`，确保可使用 readonly properties、enum、fibers、intersection types 等 8.1/8.2 特性
- 不使用 PHP 8.4+ 专属特性（如 property hooks），以保持 8.2 兼容
- PHPUnit 锁定 `^11.0`，需适配其与 PHPUnit 5 之间的 breaking changes（如 `TestCase` 命名空间、`@test` annotation vs attribute、配置 XML schema 等）
- `giorgiosironi/eris` 作为 `require-dev` 引入，用于增强 IPC 组件的测试覆盖
- 这是 major version bump（1.x → 2.0.0），因为 PHP 最低版本要求是 breaking change
