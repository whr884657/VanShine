# VanShine 1.4.2 发行说明

**发行日期：** 2026-06-30  
**压缩包名称：** `VanShine1.4.2.zip`  
**版本类型：** 小版本（EdgeOne 域名弹窗修复与 UI 规范）

---

## 变更内容

### Bug 修复

- **添加域名报错**：`OriginInfo.OriginType` 修正为 API 要求的 `IP_DOMAIN`（大写枚举）

### 弹窗 / 抽屉

- **电脑端**：添加/编辑/HTTPS 配置弹窗对齐文件预览——居中、scale 动画、**标题与底部按钮固定**，仅表单内容滚动
- **手机端**：抽屉高度统一 **80vh（4/5 屏）**；滚动时标题与关闭按钮不再跟着动
- 全站 CSS 变量 `--vs-mobile-drawer-height: 80vh`（`common.css`），文件预览/模态框同步

### UI 微调

- 「站点」标签移到选择框**上方**
- IPv6 分段：选中项蓝色底白字，未选中浅蓝底
- 抽屉内与「配置」等小按钮改为 `vs-btn--rect`（8px 圆角）
- 域名卡片 hover / 点击微动效

### 文档

- `README.md` 增加 **UI 规范（弹窗/抽屉）** 章节

---

## 升级说明

- **无数据库变更**：覆盖文件即可

---

## 下载

https://gitee.com/xunjinlu/VanShine/releases/download/v1.4.2/VanShine1.4.2.zip
