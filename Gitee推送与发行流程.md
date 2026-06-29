# VanShine · Gitee 推送与发行流程（固定版）

> **用途：** 以后每一次版本发布，**只按本文档顺序执行**，不要临时换命令、不要换上传方式。  
> 本文档依据 **1.0.10～1.0.84** 实际发版经验整理；**1.0.77～1.0.83** 为当前标准流程范本；**1.0.84 重发** 补充了 ZIP 损坏教训。

速查打勾见 **[发布检查清单.md](发布检查清单.md)**。

---

## 一、固定流程总览（九步，顺序不可乱）

| 步骤 | 做什么 | 完成标志 |
|------|--------|----------|
| **1** | 开发完成，本地自测 | 功能无阻塞 bug |
| **2** | 发版前清理（见第二节） | 无测试垃圾、无敏感文件待提交 |
| **3** | 修改版本相关文件（见第三节） | 四处版本号一致 |
| **4** | `git commit` + `push main` | Gitee main 已更新 |
| **5** | 打标签 `v1.0.NN` 并 push | Gitee 可见对应 tag |
| **6** | 按固定命令打包 ZIP（见第五节） | `release/VanShine1.0.NN.zip` 生成 |
| **7** | 本地校验 ZIP（见第六节） | ZipArchive 能打开 |
| **8** | 创建 Gitee Release + **curl 上传**（见第七节） | 远程 `size` = 本地字节数 |
| **9** | 远程下载再校验 + 可选在线升级测试 | 下载包能解压 |

```
① 自测 → ② 清理 → ③ 改版本文件 → ④ push main
    → ⑤ push tag → ⑥ 打包 → ⑦ 本地验 ZIP
    → ⑧ Release + curl 上传 → ⑨ 远程验 ZIP
```

---

## 二、发版前清理（含「临时测试目录要不要打包」）

### 2.1 原则

| 类型 | 是否打进发行 ZIP | 是否提交 Git | 说明 |
|------|------------------|--------------|------|
| 正式业务代码（admin、core、assets、d、install…） | **是** | **是** | 用户站点需要的文件 |
| `config/database.php` | **否** | **否** | 各站点独立，已在 `.gitignore` |
| `data/`、`upload/` | **否** | **否** | 运行时目录，服务器自动生成 |
| `release/*.zip` | **否** | **否** | 本地打包产物 |
| `.git/` | **否** | — | 打包命令已排除 |
| **临时测试目录**（如 `test/`、`tmp/`、`debug/`、`_test/`） | **否** | **否** | 发版前**删除**或不要创建在仓库内 |
| **临时测试脚本**（如 `release/_zip_test.php`、`verify-zip.php`） | **否** | **否** | 验完即删，勿提交 |
| 根目录临时 ZIP（如 `VanShine1.0.xx.zip`） | **否** | **否** | 只放在 `release/` 下 |
| `参考程序/`、`参考项目/` | **否** | **否** | 已在 `.gitignore`，勿提交 |
| vendor 内单元测试（`vendor/**/tests/`） | 随 vendor **是** | 随 vendor **是** | 第三方依赖自带，一般不动 |
| `发行说明/`、`update.json` 等文档 | **是** | **是** | 随版本一起发 |

**简单记法：**

- 只有「用户部署后网站能跑」的文件才进 ZIP。
- **这次多出来的测试目录 → 默认不打包、不提交；发版前删掉。**
- 若某目录拿不准：问「线上 PHP 运行要不要读它？」——不要就不打包。

### 2.2 每次发版必做清理检查

在 `git status` 之前确认：

```powershell
cd "d:\开发\VanShine"
git status
```

- [ ] 无 `config/database.php` 变更
- [ ] 无 `data/`、`upload/` 下文件
- [ ] 无 `release/*.zip` 被 add
- [ ] 无临时测试目录（有则删除后再继续）
- [ ] 无 `*_test.php`、`*verify*.php` 等临时脚本在待提交区
- [ ] 无根目录 `.zip` 文件

### 2.3 PHP 语法抽查

对**本次改动**的 PHP 文件执行（示例）：

```powershell
php -l core/version.php
php -l admin/cdn/edgeone/index.php
```

---

## 三、版本相关文件（四处必改、版本号一致）

发布 **1.0.NN** 时，以下文件版本号必须全部为 **1.0.NN**：

| # | 文件 | 操作 |
|---|------|------|
| 1 | `core/version.php` | `define('VS_VERSION', '1.0.NN');` |
| 2 | `update.json` | `version`、`release_date`、`title`、`changes`、`db_changes` |
| 3 | `update-log.json` | 在 `versions` **数组最前面**插入新版本对象 |
| 4 | `发行说明/VanShine1.0.NN.md` | 新建，含变更说明与下载链接 |

**建议同步：** `README.md` 顶部「当前版本」及版本记录一节。

**若有数据库结构变更：**

1. 新增 `install/migrations/1.0.NN.sql`
2. `update.json` 与 `update-log.json` 中该版本 `db_changes` 设为 `true`

---

## 四、推送代码到 Gitee

### 4.1 提交

```powershell
cd "d:\开发\VanShine"

git add core/version.php update.json update-log.json README.md "发行说明/VanShine1.0.NN.md"
# 以及本次改动的业务文件（不要 add 第二节禁止项）

git commit -m "简短标题（v1.0.NN）" -m "第二段：变更摘要。"
```

### 4.2 推送 main

```powershell
git push https://xunjinlu:YOUR_TOKEN@gitee.com/xunjinlu/VanShine.git main
```

> Token 使用 Gitee 私人令牌，**不要**写入仓库、不要写进本文档、不要提交到 Git。

### 4.3 打标签并推送

标签格式固定：`v` + 版本号（如 `v1.0.84`）。

```powershell
git tag v1.0.NN
git push https://xunjinlu:YOUR_TOKEN@gitee.com/xunjinlu/VanShine.git v1.0.NN
```

### 4.4 需要重发同一版本时（如 ZIP 损坏）

```powershell
git tag -d v1.0.NN
git tag v1.0.NN
git push https://xunjinlu:YOUR_TOKEN@gitee.com/xunjinlu/VanShine.git :refs/tags/v1.0.NN
git push https://xunjinlu:YOUR_TOKEN@gitee.com/xunjinlu/VanShine.git v1.0.NN
```

然后删除 Gitee 上旧 Release，按第五节起重新打包上传。

---

## 五、打包发行 ZIP（固定命令，与 1.0.77～1.0.83 相同）

**输出路径固定：** `release/VanShine1.0.NN.zip`  
**临时目录固定：** 系统 `%TEMP%`，**不要**在仓库内建 `-build` 目录。

将下面命令中的 `1.0.NN` 换成目标版本后**整段执行**：

```powershell
$ver    = "1.0.NN"
$src    = "d:\开发\VanShine"
$tmp    = "$env:TEMP\VanShine$ver`_build"
$zipDir = "$src\release"
$zip    = "$zipDir\VanShine$ver.zip"

if (-not (Test-Path $zipDir)) { New-Item -ItemType Directory -Path $zipDir -Force | Out-Null }
if (Test-Path $tmp) { Remove-Item $tmp -Recurse -Force }
New-Item -ItemType Directory -Path $tmp -Force | Out-Null

robocopy $src $tmp /E /XD .git release /XF *.zip /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null

if (Test-Path $zip) { Remove-Item $zip -Force }
Compress-Archive -Path "$tmp\*" -DestinationPath $zip -Force
Remove-Item $tmp -Recurse -Force

(Get-Item $zip).Length
```

### 5.1 打包排除项（robocopy 已写死 + 发版前人工确认）

| 排除 | 方式 |
|------|------|
| `.git` | `/XD .git` |
| `release/` 目录 | `/XD release` |
| 所有 `.zip` | `/XF *.zip` |
| 临时测试目录 | **发版前删除**；若必须保留在磁盘，打包前手动从 `$tmp` 删掉对应文件夹 |

> **注意：** `data/`、`config/database.php` 若本地存在，会被复制进 ZIP。正常开发机不应有这些文件；若有，打包前先移走或不要放在项目根下。

### 5.2 体积参考

| 版本 | 约略大小 |
|------|----------|
| 1.0.82～1.0.84 | 约 **11.9～12.0 MB** |

若新包比上一版突然大 **40% 以上**（如出现 **17MB**），**停止上传**，先查是否混入了测试目录、多余 ZIP 或打包/upload 损坏。

---

## 六、本地校验 ZIP（上传前必做）

在 `release/` 下**临时**建 `verify-zip.php`（**不要 commit**）：

```php
<?php
$path = __DIR__ . '/VanShine1.0.NN.zip';
$z = new ZipArchive();
$r = $z->open($path);
echo 'size=' . filesize($path) . ' open=' . var_export($r, true);
if ($r === true) {
    echo ' numFiles=' . $z->numFiles . PHP_EOL;
    $z->close();
}
```

```powershell
php "d:\开发\VanShine\release\verify-zip.php"
```

**通过标准：**

- `open=true`
- `numFiles` 约 5500+（随版本略增）
- 文件头为 ZIP 魔数 `PK`（可用 `(Get-Content -Encoding Byte -TotalCount 2 $zip)` 看到 80 75）

验完删除 `verify-zip.php`。

---

## 七、创建 Gitee Release 并上传 ZIP

### 7.1 命名约定（与在线更新绑定，不可改）

| 项目 | 格式 | 示例 |
|------|------|------|
| Git 标签 | `v1.0.NN` | `v1.0.84` |
| ZIP 文件名 | `VanShine1.0.NN.zip` | `VanShine1.0.84.zip` |
| 下载 URL | `…/releases/download/v1.0.NN/VanShine1.0.NN.zip` | 见 `core/Updater.php` |

### 7.2 创建 Release

```powershell
$token = "YOUR_TOKEN"
$ver   = "1.0.NN"
$tag   = "v$ver"

$body = @"
## 本版摘要

- 变更点 1
- 变更点 2

详见 发行说明/VanShine$ver.md
"@

$release = Invoke-RestMethod -Method Post `
  -Uri "https://gitee.com/api/v5/repos/xunjinlu/VanShine/releases?access_token=$token" `
  -Body @{ tag_name = $tag; name = "VanShine $ver"; body = $body; target_commitish = "main" }

Write-Output "Release ID: $($release.id)"
```

### 7.3 上传 ZIP（必须用 curl，禁止其他方式）

```powershell
$zip = "d:\开发\VanShine\release\VanShine$ver.zip"

curl.exe -s -X POST `
  "https://gitee.com/api/v5/repos/xunjinlu/VanShine/releases/$($release.id)/attach_files?access_token=$token" `
  -F "file=@$zip" `
  -F "name=VanShine$ver.zip"
```

**成功标志：** 返回 JSON 里 `"size"` 数值 = 本地 `(Get-Item $zip).Length`。

### 7.4 禁止的上传方式（1.0.84 教训）

| 禁止 | 后果 |
|------|------|
| PowerShell 手工拼 multipart，ZIP 转 ISO-8859-1 字符串 | ZIP 损坏，在线更新「无法打开 ZIP」 |
| 不校验 size 就认为上传成功 | 服务器拉到坏包 |
| 在 Cursor / IDE 内对比两个完整 ZIP | 编辑器 OOM 崩溃 |

### 7.5 远程下载再校验

```powershell
curl.exe -sL -o "$env:TEMP\VanShine$ver-dl.zip" `
  "https://gitee.com/xunjinlu/VanShine/releases/download/$tag/VanShine$ver.zip"

(Get-Item "$env:TEMP\VanShine$ver-dl.zip").Length
(Get-Item $zip).Length
```

两数必须相等；再用第六节方法对下载文件做 ZipArchive 测试。

### 7.6 替换损坏的 Release

1. `GET https://gitee.com/api/v5/repos/xunjinlu/VanShine/releases/tags/v1.0.NN?access_token=TOKEN` 查 ID  
2. `DELETE …/releases/{id}?access_token=TOKEN`  
3. 从第五节重新打包，重复第七节

---

## 八、在线更新与用户侧说明

- 检测地址：`update.json`（main 分支 raw）  
- 下载地址：Gitee Release 附件直链  
- 逻辑：`core/Updater.php`（校验 PK 头、不覆盖 `config/database.php`、不覆盖 `data/`）

用户报错 `解压失败：无法打开 ZIP（17348108 字节）` → 几乎一定是**坏包**（正常约 12MB）；删 Release 重传，并让用户清空 `data/update/` 后重试。

---

## 九、历史约定摘要

| 版本段 | 要点 |
|--------|------|
| 1.0.22+ | 仅用 Release ZIP 直链，不用仓库快照 |
| 1.0.26+ | 发行包不含运行时 `data/`；更新后清临时目录 |
| 1.0.31+ | 库表变更走 `install/migrations/` |
| 1.0.77～1.0.83 | **确立** 本文 robocopy + Compress-Archive + curl 流程 |
| 1.0.84 | 错误上传导致坏包，已重发并写入本文 |

---

## 十、相关文件索引

| 路径 | 说明 |
|------|------|
| `Gitee推送与发行流程.md` | 本文：完整固定流程 |
| `发布检查清单.md` | 发版打勾速查 |
| `core/Updater.php` | 在线更新 |
| `update.json` / `update-log.json` | 版本清单与历史 |
| `发行说明/` | 各版本说明 |
| `release/` | 本地 ZIP 输出（gitignore） |
| `.gitignore` | 忽略 database.php、data/、release/*.zip、参考目录等 |

---

**最后更新：** 2026-06-29  
**维护要求：** 发版流程若有变更，只改本文档与检查清单，不要另起一套命令。
