# Flarum Scout 扩展 - Flarum 2.x 适配迁移记录

## 已完成的修改

### 1. composer.json — 依赖版本升级

- `flarum/core`: `^1.2` → `^2.0`
- `laravel/scout`: `^9.4` → `^10.0`
- `teamtnt/laravel-scout-tntsearch-driver`: `^11.6` → `^13.0`

### 2. extend.php — Extender API 适配

- 移除了 `Flarum\Discussion\Search\DiscussionSearcher` 和 `Flarum\User\Search\UserSearcher` 的 use 语句
- 添加了 `Flarum\Database\DatabaseSearcher` 的 use 语句
- `Extend\SimpleFlarumSearch` → `Extend\SearchDriver` + `setFulltext()` 方法

```php
// Flarum 1.x
(new Extend\SimpleFlarumSearch(DiscussionSearcher::class))
    ->setFullTextGambit(Search\DiscussionGambit::class)

// Flarum 2.x
(new Extend\SearchDriver(DatabaseSearcher::class))
    ->addSearcher(Discussion::class, \Flarum\Discussion\Search\DiscussionSearcher::class)
    ->addSearcher(User::class, \Flarum\User\Search\UserSearcher::class)
    ->setFulltext(\Flarum\Discussion\Search\DiscussionSearcher::class, Search\DiscussionGambit::class)
    ->setFulltext(\Flarum\User\Search\UserSearcher::class, Search\UserGambit::class)
```

### 3. src/Extend/Scout.php — ExtenderInterface 签名适配

- `extend(Container $container, Extension $extension = null)` → `extend(Container $container, ?Extension $extension = null)`
- 添加了返回类型 `: void`

### 4. src/ScoutServiceProvider.php — 简化

- 移除了 FilterManager/GambitManager 覆盖逻辑（Flarum 2.x 由 SearchDriver Extender 处理）
- 移除了 `Search\ImprovedGambitManager` 的 use 语句
- 添加了 `: void` 返回类型注解

### 5. src/Search/ImprovedGambitManager.php — 已删除

- Flarum 2.x 的 `FilterManager` 不再使用 `explode()` 方法处理查询字符串分割
- 精确匹配引号功能现在由 Scout 引擎自己处理，不再需要自定义管理器

### 6. src/Search/DiscussionGambit.php — 完全重构

- 实现 `AbstractFulltextFilter` 替代 `GambitInterface`
- 方法从 `apply(SearchState $search, $bit)` 改为 `search(SearchState $state, string $value)`
- 使用 `DatabaseSearchState` 类型提示获取 `getQuery()`
- 添加了 `@extends AbstractFulltextFilter<DatabaseSearchState>` 泛型标记

### 7. src/Search/UserGambit.php — 完全重构

- 实现 `AbstractFulltextFilter` 替代 `GambitInterface`
- 方法从 `apply(SearchState $search, $bit)` 改为 `search(SearchState $state, string $value)`
- 使用 `DatabaseSearchState` 类型提示获取 `getQuery()`
- 添加了 `@extends AbstractFulltextFilter<DatabaseSearchState>` 泛型标记

### 8. js/package.json — 前端构建依赖升级

- `flarum-tsconfig`: `^1.0` → `^2.0`
- `flarum-webpack-config`: `^2.0` → `^3.0`

### 9. src/FlarumEngineManager.php — Scout 版本适配

- UserAgent 版本号更新为 `10.0.0`

### 10. src/ScoutStatic.php — Meilisearch 引擎命名空间适配

- `Laravel\Scout\Engines\MeiliSearchEngine`（Scout 10+ 修正拼写）

### 11. src/Job/MakeSearchable.php — 队列任务重构

- 不再继承 Scout 的 MakeSearchable，改为独立实现 `ShouldQueue` 接口
- 显式使用 `Queueable` 和 `SerializesModels` trait
- 使用 trait 冲突解决机制集成 `SerializesAndRestoresWrappedModelIdentifiers`

### 12. src/Job/RemoveFromSearch.php — 队列任务重构

- 不再继承 Scout 的 RemoveFromSearch，改为独立实现 `ShouldQueue` 接口
- 同样使用 trait 冲突解决机制

### 13. src/Console/ModifiedImportTrait.php — 命令 trait 简化

- 简化为直接调用 `ScoutStatic::makeAllSearchable()`

### 14. src/Console/ImportCommand.php — 命令独立实现

- 不再继承 Scout 的 ImportCommand，改为独立实现

### 15. src/Console/FlushCommand.php — 命令独立实现

- 不再继承 Scout 的 FlushCommand，改为独立实现

### 16. src/Console/ImportAllCommand.php — 命令适配

- 移除 `Dispatcher` 依赖注入

### 17. src/FlarumSearchableScope.php — 保持兼容

- Scope 接口和宏定义在 Laravel/Scout 10+ 中保持兼容

---

## 未修改的文件（无需修改或低风险）

- src/helpers.php — config() 辅助函数替换
- src/MakeSearchableDisable.php — 禁用类
- src/Listener/DeletingDiscussion.php — 事件监听器，接口稳定
- src/ScoutModelWrapper.php — Scout API 基本兼容
- js/admin.ts — 入口文件
- js/webpack.config.js — 构建配置
- js/tsconfig.json — 类型定义路径
- js/src/admin/index.ts — 前端管理面板代码
- resources/locale/en.yml — 语言文件

---

## Flarum 2.x 搜索系统架构变更摘要

### Flarum 1.x

- `Extend\SimpleFlarumSearch` — 注册 Searcher 的全文搜索 Gambit
- `GambitInterface` — `apply(SearchState $search, $bit)` 方法
- `GambitManager` — 管理所有 Gambit，包含 `explode()` 处理查询分割
- 容器绑定：`flarum.simple_search.fulltext_gambits`, `flarum.simple_search.gambits`

### Flarum 2.x

- `Extend\SearchDriver` — 注册 Searcher、Filter 和全文搜索
- `FilterInterface` — `getFilterKey()`, `filter()` 方法（用于参数过滤）
- `AbstractFulltextFilter` — `search(SearchState $state, string $value)` 方法（用于全文搜索）
- `FilterManager` — 管理 Filter，不处理查询分割
- 容器绑定：`flarum.search.drivers`, `flarum.search.fulltext`, `flarum.search.filters`, `flarum.search.mutators`

---

## 测试建议

1. 安装 Flarum 2.x 环境和依赖：`composer update`
2. 构建前端资源：`cd js && yarn install && yarn build`
3. 运行数据库迁移（如有）
4. 测试管理面板设置页面
5. 测试 Scout 命令：`php flarum scout:import-all`
6. 测试搜索功能（Discussion 和 User）
7. 测试 Algolia/Meilisearch/TNTSearch 引擎（如已配置）
