<?php
/**
 * 文件：admin/cdn/edgeone/includes/rules-editor.php
 * 作用：规则引擎全屏编辑器 HTML（对齐腾讯云控制台布局）
 */

/**
 * @return string
 */
function vs_edgeone_render_rule_editor_modal()
{
    ob_start();
    ?>
<div class="vs-edgeone-rule-editor" id="edgeoneRuleEditor" hidden aria-hidden="true">
    <div class="vs-edgeone-rule-editor__overlay" data-rule-editor-close></div>
    <div class="vs-edgeone-rule-editor__panel" role="dialog" aria-modal="true" aria-labelledby="edgeoneRuleEditorTitle">
        <div class="vs-edgeone-rule-editor__handle" aria-hidden="true"></div>
        <header class="vs-edgeone-rule-editor__head">
            <div class="vs-edgeone-rule-editor__head-main">
                <h4 id="edgeoneRuleEditorTitle" class="vs-edgeone-rule-editor__title-label">编辑规则</h4>
                <input type="text" class="vs-input vs-edgeone-rule-editor__name" id="edgeoneRuleEditorName" placeholder="未命名规则" maxlength="255" aria-label="规则名称">
                <span class="vs-edgeone-rule-editor__id" id="edgeoneRuleEditorIdHint" hidden></span>
            </div>
            <button type="button" class="vs-edgeone-rule-editor__close" data-rule-editor-close aria-label="关闭">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
        </header>
        <div class="vs-edgeone-rule-editor__body">
            <aside class="vs-edgeone-rule-editor__nav" id="edgeoneRuleEditorNav" aria-label="规则注释导航"></aside>
            <div class="vs-edgeone-rule-editor__main">
                <div class="vs-edgeone-rules-editor-guide">
                    <p class="vs-form-tip">面向新手：先选<strong>匹配条件</strong>（如 HOST、URL 路径），再选<strong>要执行的操作</strong>（如缓存、压缩）。下方每一项都有中文说明，无需理解英文参数名。</p>
                </div>
                <div class="vs-edgeone-rule-editor__meta">
                    <label class="vs-label" for="edgeoneRuleEditorComment">规则注释</label>
                    <input type="text" class="vs-input" id="edgeoneRuleEditorComment" placeholder="可选，便于搜索与导航">
                    <label class="vs-edgeone-rules-switch vs-edgeone-rules-switch--inline vs-edgeone-rule-editor__status">
                        <input type="checkbox" id="edgeoneRuleEditorEnabled" checked>
                        <span class="vs-edgeone-rules-switch__track"></span><span>保存后启用</span>
                    </label>
                </div>
                <div id="edgeoneRuleEditorBranches"></div>
                <button type="button" class="vs-btn vs-btn--rect vs-btn--default vs-edgeone-rule-editor__add-if" id="edgeoneRuleEditorAddIf">+ 添加 IF 条件块</button>
            </div>
        </div>
        <footer class="vs-edgeone-rule-editor__foot">
            <button type="button" class="vs-btn vs-btn--rect vs-btn--default vs-edgeone-rule-editor__foot-cancel" data-rule-editor-close>取消</button>
            <button type="button" class="vs-btn vs-btn--rect vs-btn--default vs-edgeone-rule-editor__foot-save" id="edgeoneRuleEditorSaveOnly">仅保存</button>
            <button type="button" class="vs-btn vs-btn--rect vs-btn--primary vs-edgeone-rule-editor__foot-publish" id="edgeoneRuleEditorSavePublish">保存并发布</button>
        </footer>
    </div>
</div>

<div class="vs-edgeone-rule-action-picker" id="edgeoneRuleActionPicker" hidden aria-hidden="true">
    <div class="vs-edgeone-rule-action-picker__overlay" data-action-picker-close></div>
    <div class="vs-edgeone-rule-action-picker__panel" role="dialog" aria-modal="true">
        <header class="vs-edgeone-rule-action-picker__head">
            <h4>请选择操作</h4>
            <input type="search" class="vs-input" id="edgeoneRuleActionSearch" placeholder="搜索关键字">
            <button type="button" class="vs-edgeone-rule-editor__close" data-action-picker-close aria-label="关闭">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
        </header>
        <div class="vs-edgeone-rule-action-picker__flow" aria-hidden="true">
            <span>客户端</span><span class="vs-edgeone-rule-action-picker__arrow">→</span><span>EdgeOne</span><span class="vs-edgeone-rule-action-picker__arrow">→</span><span>源站</span>
        </div>
        <div class="vs-edgeone-rule-action-picker__body" id="edgeoneRuleActionPickerBody"></div>
    </div>
</div>
    <?php
    return ob_get_clean();
}
