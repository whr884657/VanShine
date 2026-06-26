<?php
/**
 * 文件：core/Storage/ILanZou/ILanZouOptions.php
 * 作用：蓝奏云优享版配置键
 * @version 1.0.0
 */

class ILanZouOptions
{
    /** 对外访问域名（用于 buildUrl 拼接；直链需经 VanShine 代理或自行解析） */
    const URL = 'url';

    /** 蓝奏云优享版登录账号 */
    const USERNAME = 'username';

    /** 蓝奏云优享版登录密码 */
    const PASSWORD = 'password';

    /** 上传目标文件夹 ID，根目录为 0 */
    const FOLDER_ID = 'folder_id';

    /** 设备 UUID（留空则首次请求自动获取） */
    const UUID = 'uuid';

    /** 登录令牌（留空则自动登录获取） */
    const TOKEN = 'token';

    /** 可选：X-Forwarded-For 请求头 */
    const IP = 'ip';

    /** 元数据后缀：pathname 旁存储 ILanZou fileId */
    const META_SUFFIX = '.ilzmeta';
}
