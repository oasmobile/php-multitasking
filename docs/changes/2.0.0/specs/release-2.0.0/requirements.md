# Requirements Document

`oasis/multitasking` 2.0.0 版本升级的需求规格。

---

## Introduction

`oasis/multitasking` 当前基于 PHP ≥ 5.6.1 和 PHPUnit ^5.5 构建。本次 release 2.0.0 将最低 PHP 版本提升至 ≥ 8.2，测试框架升级至 PHPUnit ^11.0，并对源代码进行 PHP 8 现代化改造，同时引入 `giorgiosironi/eris` 用于 Property-Based Testing。

这是一个 major version bump（1.x → 2.0.0），因为 PHP 最低版本要求构成 breaking change。

### 不涉及的内容（Non-scope）

- 不改变现有公共 API 的行为语义
- 不新增功能特性
- 不迁移到其他 IPC 机制（如 POSIX IPC、Socket 等）
- 不引入 PHP 8.4+ 专属特性（如 property hooks），保持 8.2 兼容
- 不修改项目的模块结构或命名空间

---

## Glossary

- **Composer_Config**: `composer.json` 文件，定义项目依赖和版本约束
- **PHPUnit_Config**: `phpunit.xml` 文件，定义测试框架配置
- **Source_Code**: `src/Multitasking/` 目录下的全部 PHP 源文件
- **Test_Suite**: `ut/` 目录下的全部 PHPUnit 测试文件
- **SSOT_Docs**: `docs/state/` 目录下的架构与 API 文档
- **Build_System**: Composer 依赖管理与 PHPUnit 测试执行的组合
- **IPC_Components**: `Semaphore`、`MessageQueue`、`SharedMemory` 三个 System V IPC 封装类
- **Worker_Components**: `BackgroundWorkerManager`、`WorkerInfo`、`WorkerManagerCompletedEvent` 三个进程管理相关类

---

## Requirements

### Requirement 1: Composer 依赖版本升级

**User Story:** 作为开发者，我希望项目的依赖约束升级到 PHP 8.2+ 和 PHPUnit 11，以便项目能够使用现代 PHP 特性和受支持的测试框架。

#### Acceptance Criteria

1. WHEN the upgrade is applied, THE Composer_Config SHALL declare `php` requirement as `>=8.2`
2. WHEN the upgrade is applied, THE Composer_Config SHALL declare `phpunit/phpunit` in `require-dev` as `^11.0`
3. WHEN the upgrade is applied, THE Composer_Config SHALL declare `giorgiosironi/eris` in `require-dev` with a version constraint compatible with PHP 8.2 and PHPUnit 11
4. THE Composer_Config SHALL retain all existing `require` dependencies (`ext-pcntl`, `oasis/logging`, `oasis/event`) with their current version constraints
5. WHEN `composer install` is executed after the upgrade, THE Build_System SHALL resolve all dependencies without conflict

### Requirement 2: PHPUnit 配置与测试基类适配

**User Story:** 作为开发者，我希望测试配置和测试类适配 PHPUnit 11，以便所有现有测试能在新框架版本下执行。

#### Acceptance Criteria

1. WHEN the upgrade is applied, THE PHPUnit_Config SHALL conform to the PHPUnit 11 XML configuration schema
2. WHEN the upgrade is applied, THE Test_Suite SHALL use `PHPUnit\Framework\TestCase` as the base class instead of `PHPUnit_Framework_TestCase`
3. WHEN the upgrade is applied, THE Test_Suite SHALL use PHPUnit 11 compatible lifecycle method signatures (typed `void` return types on `setUp()` and `tearDown()`)
4. WHEN all tests are executed after the upgrade, THE Test_Suite SHALL pass with zero failures and zero errors

### Requirement 3: 源代码 PHP 8 现代化 — Typed Properties

**User Story:** 作为开发者，我希望类属性使用 PHP 类型声明，以便代码获得编译期类型安全和更好的可读性。

#### Acceptance Criteria

1. WHEN the upgrade is applied, THE Source_Code SHALL declare explicit type annotations on all class properties where the type is unambiguous from existing docblock or usage context
2. WHEN a property may hold `null` in addition to its primary type, THE Source_Code SHALL use nullable type syntax (`?Type` or union type with `null`)
3. THE Source_Code SHALL preserve the existing default values of all properties
4. WHEN all tests are executed after the modernization, THE Test_Suite SHALL pass with zero failures and zero errors

### Requirement 4: 源代码 PHP 8 现代化 — Constructor Promotion

**User Story:** 作为开发者，我希望构造函数在适用时使用 PHP 8 构造器提升语法，以便减少样板式的属性声明和赋值代码。

#### Acceptance Criteria

1. WHEN a constructor parameter directly assigns to a same-named property with no additional logic, THE Source_Code SHALL use constructor promotion syntax for that parameter
2. WHEN constructor promotion is applied, THE Source_Code SHALL preserve the original visibility (`public`, `protected`, `private`) of the promoted property
3. WHEN constructor promotion is applied, THE Source_Code SHALL include the type declaration on the promoted parameter
4. WHEN all tests are executed after the modernization, THE Test_Suite SHALL pass with zero failures and zero errors

### Requirement 5: 源代码 PHP 8 现代化 — Union Types 与 Return Types

**User Story:** 作为开发者，我希望方法使用原生 PHP 8 类型声明（union types、return types），以便类型契约在运行时得到强制执行并在代码中自文档化。

#### Acceptance Criteria

1. WHEN the upgrade is applied, THE Source_Code SHALL declare return types on all public and protected methods where the return type is deterministic from existing docblock or implementation
2. WHEN a method may return multiple types, THE Source_Code SHALL use union type syntax (e.g., `int|false`, `mixed`)
3. WHEN a method returns no value, THE Source_Code SHALL declare `void` as the return type
4. THE Source_Code SHALL preserve the existing public API behavior — no method signature changes that would break callers
5. WHEN all tests are executed after the modernization, THE Test_Suite SHALL pass with zero failures and zero errors

### Requirement 6: 源代码 PHP 8 现代化 — Match 表达式与 Named Arguments

**User Story:** 作为开发者，我希望代码在能提升清晰度的地方使用 `match` 表达式和命名参数，以便代码库遵循现代 PHP 惯用写法。

#### Acceptance Criteria

1. WHERE a `switch` statement performs simple value-to-value mapping without side effects, THE Source_Code SHALL replace the `switch` with a `match` expression
2. WHERE a function call has multiple boolean or integer literal arguments whose meaning is unclear from position alone, THE Source_Code SHALL use named arguments to improve readability
3. THE Source_Code SHALL apply `match` and named arguments only where they improve clarity, not as a blanket transformation
4. WHEN all tests are executed after the modernization, THE Test_Suite SHALL pass with zero failures and zero errors

### Requirement 7: 源代码 PHP 8 现代化 — Readonly Properties

**User Story:** 作为开发者，我希望不可变属性声明为 `readonly`，以便在语言层面防止意外修改。

#### Acceptance Criteria

1. WHEN a property is assigned only in the constructor and never modified afterward, THE Source_Code SHALL declare that property as `readonly`
2. WHEN a property is declared `readonly`, THE Source_Code SHALL remove the corresponding setter method if one exists, provided the setter is not part of the public API documented in SSOT_Docs
3. THE Source_Code SHALL not declare a property as `readonly` if it is reassigned after construction
4. WHEN all tests are executed after the modernization, THE Test_Suite SHALL pass with zero failures and zero errors

### Requirement 8: 源代码 PHP 8 现代化 — 其他语法改进

**User Story:** 作为开发者，我希望代码采用其他 PHP 8 语法改进（如 `str_contains`、`str_starts_with`、null-safe 运算符），以便代码库符合 PHP 8.2 的惯用风格。

#### Acceptance Criteria

1. WHERE the code uses `strpos($haystack, $needle) !== false` or equivalent patterns, THE Source_Code SHALL replace them with `str_contains($haystack, $needle)`
2. WHERE the code uses `substr($str, 0, strlen($prefix)) === $prefix` or equivalent patterns, THE Source_Code SHALL replace them with `str_starts_with($str, $prefix)`
3. WHERE the code performs a null check followed by a method call on the same variable, THE Source_Code SHALL use the null-safe operator (`?->`) where it simplifies the expression without changing behavior
4. THE Source_Code SHALL remove legacy PHPDoc `@var` / `@param` / `@return` annotations that are fully superseded by native type declarations
5. WHEN all tests are executed after the modernization, THE Test_Suite SHALL pass with zero failures and zero errors

### Requirement 9: Property-Based Testing 引入

**User Story:** 作为开发者，我希望使用 `giorgiosironi/eris` 为 IPC_Components 编写 Property-Based Tests，以便通过随机输入生成发现边界情况。

#### Acceptance Criteria

1. WHEN the upgrade is applied, THE Build_System SHALL have `giorgiosironi/eris` available as a dev dependency
2. THE Test_Suite SHALL include at least one Property-Based Test for `SharedMemory` that verifies the round-trip property: for all serializable values, `set(key, value)` followed by `get(key)` SHALL return a value equal to the original
3. THE Test_Suite SHALL include at least one Property-Based Test for `MessageQueue` that verifies the round-trip property: for all serializable messages, `send(msg)` followed by `receive()` SHALL return a message equal to the original
4. THE Test_Suite SHALL include at least one Property-Based Test for `Semaphore` that verifies the idempotence property: acquiring and releasing a semaphore multiple times in sequence SHALL leave the semaphore in a consistent, reusable state
5. WHEN all tests (including Property-Based Tests) are executed, THE Test_Suite SHALL pass with zero failures and zero errors

### Requirement 10: SSOT 文档更新

**User Story:** 作为开发者，我希望 `docs/state/` 反映新的技术选型，以便文档保持为唯一事实来源。

#### Acceptance Criteria

1. WHEN the upgrade is applied, THE SSOT_Docs SHALL update `docs/state/architecture.md` to reflect PHP ≥ 8.2 as the language requirement
2. WHEN the upgrade is applied, THE SSOT_Docs SHALL update `docs/state/architecture.md` to reflect PHPUnit ^11.0 as the test framework
3. WHEN the upgrade is applied, THE SSOT_Docs SHALL update `docs/state/architecture.md` to list `giorgiosironi/eris` as a dev dependency for Property-Based Testing
4. WHEN the upgrade is applied, THE SSOT_Docs SHALL update `docs/state/api.md` to reflect any type signature changes resulting from the PHP 8 modernization
5. THE SSOT_Docs SHALL not document any behavioral changes to the public API, as no behavioral changes are introduced in this release

### Requirement 11: 公共 API 行为保持不变

**User Story:** 作为库的使用者，我希望升级后库的公共 API 行为保持不变，以便我的现有代码无需修改即可继续工作（PHP 版本要求除外）。

#### Acceptance Criteria

1. THE Source_Code SHALL not add, remove, or rename any public methods on IPC_Components or Worker_Components
2. THE Source_Code SHALL not change the semantic behavior of any public method — given the same inputs, each method SHALL produce the same outputs and side effects as before the upgrade
3. THE Source_Code SHALL not change the event names (`EVENT_WORKER_FINISHED`, `EVENT_ALL_COMPLETED`) or their dispatch semantics
4. IF a type declaration is added to a public method parameter that previously accepted a wider range of types, THEN THE Source_Code SHALL use a union type or `mixed` to preserve backward compatibility
5. WHEN all existing tests are executed after the upgrade, THE Test_Suite SHALL pass with zero failures and zero errors, confirming behavioral equivalence

---

## Socratic Review

**Q: Requirement 3–8 是否覆盖了 goal.md 中提到的所有 PHP 8 现代化项？**
A: goal.md 提到 typed properties、union types、match 表达式、named arguments、constructor promotion。Requirement 3 覆盖 typed properties，Requirement 4 覆盖 constructor promotion，Requirement 5 覆盖 union types 和 return types，Requirement 6 覆盖 match 和 named arguments，Requirement 7 覆盖 readonly properties（PHP 8.1 特性，在 ≥ 8.2 约束下可用），Requirement 8 覆盖其他语法改进。全部覆盖。

**Q: Requirement 9 的 PBT 范围是否合理？**
A: 聚焦于三个 IPC 组件的核心属性（round-trip、idempotence），这些是纯逻辑属性，适合 PBT。`BackgroundWorkerManager` 涉及 `pcntl_fork`，属于高成本外部操作，不适合 PBT，用现有集成测试覆盖即可。合理。

**Q: 是否遗漏了 goal.md Clarification 中的决策？**
A: Q1（PHP ≥ 8.2）→ Requirement 1 AC1；Q2（PHPUnit ^11.0）→ Requirement 1 AC2 + Requirement 2；Q3（源代码现代化）→ Requirement 3–8；Q4（引入 eris）→ Requirement 9。全部体现。

**Q: Requirement 11 是否与 Non-Goals 一致？**
A: goal.md Non-Goals 明确"不改变现有公共 API 的行为语义"。Requirement 11 直接对应此约束。一致。

**Q: 是否存在不可测试的 AC？**
A: 所有 AC 均可通过代码审查（静态验证）或测试执行（动态验证）来确认。无不可测试项。

**Q: 各 requirement 之间是否存在矛盾或重叠？**
A: Requirement 3（typed properties）与 Requirement 7（readonly properties）存在交叉——一个属性可能同时需要添加类型声明和 readonly 修饰。但两者关注点不同（类型 vs 可变性），不构成矛盾，design 阶段可在同一次改造中同时处理。Requirement 5 AC4（保持公共 API 行为）与 Requirement 11 有重叠，但 Req 11 是全局约束，Req 5 AC4 是局部提醒，保留两者有助于 design 阶段不遗漏。无实质矛盾。

**Q: 是否有隐含的前置假设没有显式列出？**
A: 有两个隐含假设：(1) 现有测试套件的覆盖率足以验证行为等价性——如果现有测试覆盖不足，即使全部通过也不能完全保证行为不变；(2) `giorgiosironi/eris` 的当前版本确实兼容 PHP 8.2 + PHPUnit 11——goal.md 中已确认兼容性，但 design 阶段应验证具体版本约束。这两个假设合理，不需要在 requirements 中额外约束。

**Q: 是否有遗漏的错误路径或边界条件？**
A: Requirement 3–8 的现代化改造均以"测试全部通过"作为兜底验收条件，这覆盖了已有测试中的错误路径。但如果源代码中存在未被测试覆盖的错误路径（如异常分支），类型声明的添加可能改变其行为（如 `TypeError` 替代了原来的静默类型转换）。Requirement 11 AC4 已要求在此情况下使用 union type 或 `mixed` 保持兼容，足以覆盖此风险。


---

## Gatekeep Log

**校验时间**: 2025-01-20
**校验结果**: ⚠️ 已修正后通过

### 修正项
- [结构] Introduction 缺少 Non-scope 小节，已补充"不涉及的内容"列表（来源：goal.md Non-Goals）
- [语体] 全部 11 条 User Story 从英文改为中文行文（`作为 <角色>，我希望 <能力>，以便 <价值>`）
- [内容] Glossary 中 `IPC_Components` 和 `Worker_Components` 为孤立术语，未在 AC 中使用；已将 Requirement 11 AC1 改为引用这两个术语替代逐一列举类名
- [内容] Requirement 7 AC2 中直接引用文件路径 `docs/state/api.md`，已改为使用 Glossary 术语 `SSOT_Docs`
- [内容] Socratic Review 缺少对 requirement 间矛盾/重叠、隐含假设、遗漏错误路径的审视，已补充三条 Q&A

### 合规检查
- [x] 无 TBD / TODO / 占位符
- [x] 无空 section 或不完整列表
- [x] 内部引用一致（术语表术语在 AC 中使用，无孤立术语）
- [x] 无 markdown 格式错误
- [x] 一级标题存在且正确
- [x] Introduction 存在，描述了 feature 范围
- [x] Introduction 明确了不涉及的内容（Non-scope）
- [x] Glossary 存在且非空，格式正确
- [x] Requirements section 存在且包含 11 条 requirement
- [x] 各 section 之间使用 `---` 分隔
- [x] User Story 使用中文行文
- [x] AC 使用 `THE <Subject> SHALL` 语体
- [x] AC Subject 使用 Glossary 中定义的术语
- [x] AC 编号连续，无跳号
- [x] AC 聚焦外部可观察行为，未混入不当实现细节
- [x] Socratic Review 覆盖充分（goal 覆盖、PBT 范围、CR 决策、Non-Goals 一致性、可测试性、矛盾/重叠、隐含假设、遗漏场景）
- [x] Goal CR（Q1-Q4）决策全部体现在 requirements 中
- [x] Non-goal / Scope 边界清晰
- [x] AC 整体构成充分的验收条件
- [x] 文档具备可 design 性

### Clarification Round

**状态**: 已完成

**Q1:** Requirement 3–5 和 7 涉及为属性和方法添加类型声明。对于内部（private）方法和属性，类型推断可能存在歧义（如一个 private 属性在不同方法中被赋予不同类型的值）。当类型不明确时，design 阶段应采取什么策略？
- A) 保守策略：类型不明确时使用 `mixed`，优先保证不引入 TypeError
- B) 推断策略：根据实际使用场景推断最窄类型，必要时重构赋值逻辑以统一类型
- C) 跳过策略：类型不明确的属性/方法不添加类型声明，保留 PHPDoc 注释
- D) 其他（请说明）

**A:** B — 推断策略：根据实际使用场景推断最窄类型，必要时重构赋值逻辑以统一类型

**Q2:** Requirement 7（readonly properties）AC2 规定：如果 readonly 属性存在 setter 且该 setter 不在 SSOT_Docs 公共 API 中，则移除 setter。但当前 SSOT_Docs 中 `setNumberOfConcurrentWorkers` 是公共 API 的一部分。对于这类"公共 API 中有 setter 但属性语义上应为构造后不变"的情况，design 阶段应如何处理？
- A) 严格遵守 API 兼容：只要 setter 在公共 API 中，就不标记 readonly，保留 setter
- B) 标记 readonly 并废弃 setter：添加 `@deprecated` 注解，在 2.x 周期内保留但标记废弃，3.0 移除
- C) 不适用：`setNumberOfConcurrentWorkers` 对应的属性确实需要运行时修改，不应标记 readonly
- D) 其他（请说明）

**A:** C — 不适用：`setNumberOfConcurrentWorkers` 对应的属性确实需要运行时修改，不应标记 readonly

**Q3:** Requirement 8 AC4 要求移除被原生类型声明完全取代的 PHPDoc 注释。但部分 PHPDoc 可能包含额外语义信息（如 `@param string $id The unique identifier for the semaphore`）。移除策略应如何界定？
- A) 仅移除纯类型注释：只移除 `@var`/`@param`/`@return` 中类型信息与原生声明完全一致且无额外描述文本的条目
- B) 全部移除类型相关注释：只要原生声明已覆盖类型信息，即使有描述文本也移除（描述信息应通过参数命名和代码自文档化体现）
- C) 保留所有 PHPDoc：不移除任何注释，仅添加原生类型声明
- D) 其他（请说明）

**A:** A — 仅移除纯类型注释：只移除类型信息与原生声明完全一致且无额外描述文本的条目

**Q4:** Requirement 9 的 PBT 要求为三个 IPC_Components 各编写至少一个 Property-Based Test。这些 IPC 组件依赖 System V 资源（信号量、消息队列、共享内存），测试后需要清理。PBT 的资源管理策略应如何处理？
- A) 每次测试迭代独立创建和销毁资源：使用唯一 ID 确保隔离，测试结束后调用 `remove()`
- B) 复用资源：在 `setUp` 中创建一次，所有迭代共享，`tearDown` 中销毁
- C) 由 design 阶段根据 eris 的 API 特性决定最合适的方式
- D) 其他（请说明）

**A:** C — 由 design 阶段根据 eris 的 API 特性决定最合适的方式
