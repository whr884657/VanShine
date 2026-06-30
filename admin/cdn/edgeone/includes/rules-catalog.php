<?php
/**
 * 文件：admin/cdn/edgeone/includes/rules-catalog.php
 * 作用：规则引擎匹配类型、运算符、操作目录（对齐腾讯云文档 90438 / L7Acc API）
 *
 * 说明：供 rules-editor 前端读取；操作默认参数来自 CreateL7AccRules / ModifyL7AccRule 示例
 */

/**
 * @return array<int, array<string, mixed>>
 */
function vs_edgeone_rules_match_types()
{
    return array(
        array('id' => 'host', 'label' => 'HOST（域名）', 'group' => 'client', 'var' => '${http.request.host}', 'needsName' => false, 'hint' => '访问的域名，如 api.example.com'),
        array('id' => 'url_path', 'label' => 'URL 路径', 'group' => 'client', 'var' => '${http.request.uri.path}', 'needsName' => false, 'hint' => '网址路径，如 /images/logo.png，可用 /api/* 匹配目录'),
        array('id' => 'url_full', 'label' => '完整 URL', 'group' => 'client', 'var' => '${http.request.full_uri}', 'needsName' => false, 'hint' => '含协议、域名、路径、参数的完整地址'),
        array('id' => 'query_string', 'label' => '查询字符串', 'group' => 'client', 'var' => '${http.request.uri.query}', 'needsName' => false, 'kv' => true, 'hint' => 'URL 中 ? 后面的参数，需填写参数名'),
        array('id' => 'file_extension', 'label' => '文件后缀', 'group' => 'client', 'var' => '${http.request.file_extension}', 'needsName' => false, 'hint' => '如 jpg、css、js，多个用逗号分隔'),
        array('id' => 'file_name', 'label' => '文件名称', 'group' => 'client', 'var' => '${http.request.filename}', 'needsName' => false, 'hint' => '文件名，如 index.html'),
        array('id' => 'request_header', 'label' => 'HTTP 请求头', 'group' => 'client', 'var' => '${http.request.headers["%s"]}', 'needsName' => true, 'hint' => '需填写 Header 名称，如 User-Agent'),
        array('id' => 'client_geo', 'label' => '客户端地理位置', 'group' => 'client', 'var' => '${http.request.ip.country}', 'needsName' => false, 'hint' => '国家/地区代码，如 CN、US'),
        array('id' => 'client_isp', 'label' => '客户端运营商', 'group' => 'client', 'var' => '${http.request.ip.isp}', 'needsName' => false, 'hint' => '如中国联通、中国电信、中国移动'),
        array('id' => 'request_protocol', 'label' => '请求协议', 'group' => 'client', 'var' => '${http.request.scheme}', 'needsName' => false, 'hint' => 'HTTP 或 HTTPS'),
        array('id' => 'client_ip', 'label' => '客户端 IP', 'group' => 'client', 'var' => '${http.request.ip}', 'needsName' => false, 'hint' => '访客 IP 或 IP 段，如 192.168.1.0/24'),
        array('id' => 'request_method', 'label' => '请求方法', 'group' => 'client', 'var' => '${http.request.method}', 'needsName' => false, 'hint' => 'GET、POST、PUT 等，多个用逗号分隔'),
        array('id' => 'cookie', 'label' => 'Cookie', 'group' => 'client', 'var' => '${http.request.uri.args["%s"]}', 'needsName' => true, 'kv' => true, 'hint' => '需填写 Cookie 参数名'),
        array('id' => 'response_header', 'label' => 'HTTP 响应头', 'group' => 'origin', 'var' => '${http.response.headers["%s"]}', 'needsName' => true, 'response' => true, 'hint' => '源站返回的响应头，需填写名称'),
        array('id' => 'response_status', 'label' => 'HTTP 响应状态码', 'group' => 'origin', 'var' => '${http.response.status_code}', 'needsName' => false, 'response' => true, 'hint' => '如 200、404、502'),
        array('id' => 'all', 'label' => '全部（任意请求）', 'group' => 'client', 'var' => '', 'needsName' => false, 'all' => true, 'hint' => '匹配该站点下所有请求'),
    );
}

/**
 * @return array<int, array<string, mixed>>
 */
function vs_edgeone_rules_operators()
{
    return array(
        array('id' => 'equal', 'label' => '等于', 'modes' => array('list', 'single')),
        array('id' => 'not_equal', 'label' => '不等于', 'modes' => array('list', 'single')),
        array('id' => 'exists', 'label' => '存在', 'modes' => array('name_only')),
        array('id' => 'not_exists', 'label' => '不存在', 'modes' => array('name_only')),
        array('id' => 'regex', 'label' => '正则匹配', 'modes' => array('regex')),
        array('id' => 'not_regex', 'label' => '正则不匹配', 'modes' => array('regex')),
    );
}

/**
 * @return array<string, array<string, mixed>>
 */
function vs_edgeone_rules_action_catalog()
{
    $sw = function ($on = 'on') {
        return array('Switch' => $on);
    };

    return array(
        'Cache' => array(
            'label' => '节点缓存 TTL', 'category' => 'cache',
            'defaults' => array('Name' => 'Cache', 'CacheParameters' => array(
                'FollowOrigin' => array('Switch' => 'on', 'DefaultCache' => 'on', 'DefaultCacheStrategy' => 'on', 'DefaultCacheTime' => 0),
                'NoCache' => array('Switch' => 'off'),
                'CustomTime' => array('Switch' => 'off', 'CacheTime' => 600, 'IgnoreCacheControl' => 'off'),
            )),
            'fields' => array(
                array('type' => 'select', 'key' => 'CacheParameters._uiMode', 'label' => '行为', 'options' => array(
                    'follow_origin' => '遵循源站 Cache-Control',
                    'no_cache' => '不缓存',
                    'custom' => '自定义时间',
                )),
                array('type' => 'select', 'key' => 'CacheParameters.FollowOrigin.DefaultCacheStrategy', 'label' => '无 Cache-Control 时', 'options' => array('on' => '默认缓存策略', 'off' => '不缓存')),
                array('type' => 'duration', 'key' => 'CacheParameters.FollowOrigin.DefaultCacheTime', 'label' => '默认缓存时间'),
                array('type' => 'duration', 'key' => 'CacheParameters.CustomTime.CacheTime', 'label' => '自定义缓存时间'),
                array('type' => 'switch', 'key' => 'CacheParameters.CustomTime.IgnoreCacheControl', 'label' => '强制缓存'),
            ),
        ),
        'MaxAge' => array(
            'label' => '浏览器缓存 TTL', 'category' => 'cache',
            'defaults' => array('Name' => 'MaxAge', 'MaxAgeParameters' => array('FollowOrigin' => 'off', 'CacheTime' => 600)),
            'fields' => array(
                array('type' => 'select', 'key' => 'MaxAgeParameters._uiMode', 'label' => '行为', 'options' => array('follow_origin' => '遵循源站 Cache-Control', 'custom' => '自定义时间')),
                array('type' => 'duration', 'key' => 'MaxAgeParameters.CacheTime', 'label' => '浏览器缓存时间'),
            ),
        ),
        'CacheKey' => array(
            'label' => '自定义 Cache Key', 'category' => 'cache',
            'defaults' => array('Name' => 'CacheKey', 'CacheKeyParameters' => array(
                'FullURLCache' => 'on', 'IgnoreCase' => 'off', 'Scheme' => 'off',
                'QueryString' => array('Switch' => 'off', 'Action' => 'includeCustom', 'Values' => array()),
                'Header' => array('Switch' => 'off', 'Values' => array()),
                'Cookie' => array('Switch' => 'off'),
            )),
            'fields' => array(
                array('type' => 'switch', 'key' => 'CacheKeyParameters.FullURLCache', 'label' => '全 URL 缓存'),
                array('type' => 'switch', 'key' => 'CacheKeyParameters.IgnoreCase', 'label' => '忽略大小写'),
                array('type' => 'switch', 'key' => 'CacheKeyParameters.Scheme', 'label' => '区分 HTTP/HTTPS 协议'),
                array('type' => 'switch', 'key' => 'CacheKeyParameters.QueryString.Switch', 'label' => '查询字符串参与 Cache Key'),
                array('type' => 'select', 'key' => 'CacheKeyParameters.QueryString.Action', 'label' => '查询字符串规则', 'options' => array(
                    'includeCustom' => '保留指定参数', 'excludeCustom' => '忽略指定参数', 'full' => '保留全部', 'ignore' => '忽略全部',
                )),
                array('type' => 'taglist', 'key' => 'CacheKeyParameters.QueryString.Values', 'label' => '查询参数名（多个用逗号分隔）'),
                array('type' => 'switch', 'key' => 'CacheKeyParameters.Header.Switch', 'label' => '请求头参与 Cache Key'),
                array('type' => 'taglist', 'key' => 'CacheKeyParameters.Header.Values', 'label' => '请求头名称（多个用逗号分隔）'),
                array('type' => 'switch', 'key' => 'CacheKeyParameters.Cookie.Switch', 'label' => 'Cookie 参与 Cache Key'),
            ),
        ),
        'StatusCodeCache' => array(
            'label' => '状态码缓存 TTL', 'category' => 'cache',
            'defaults' => array('Name' => 'StatusCodeCache', 'StatusCodeCacheParameters' => array('StatusCodeCacheParams' => array(array('StatusCode' => 404, 'CacheTime' => 10)))),
            'fields' => array(array('type' => 'status_rows', 'key' => 'StatusCodeCacheParameters.StatusCodeCacheParams', 'label' => '状态码与缓存时间')),
        ),
        'CachePrefresh' => array(
            'label' => '缓存预刷新', 'category' => 'cache',
            'defaults' => array('Name' => 'CachePrefresh', 'CachePrefreshParameters' => array('Switch' => 'on', 'CacheTimePercent' => 90)),
            'fields' => array(
                array('type' => 'switch', 'key' => 'CachePrefreshParameters.Switch', 'label' => '启用'),
                array('type' => 'number', 'key' => 'CachePrefreshParameters.CacheTimePercent', 'label' => '过期百分比', 'min' => 1, 'max' => 99),
            ),
        ),
        'OfflineCache' => array(
            'label' => '离线缓存', 'category' => 'cache',
            'defaults' => array('Name' => 'OfflineCache', 'OfflineCacheParameters' => $sw()),
            'fields' => array(array('type' => 'switch', 'key' => 'OfflineCacheParameters.Switch', 'label' => '启用')),
        ),
        'HTTP2' => array(
            'label' => 'HTTP/2', 'category' => 'network',
            'defaults' => array('Name' => 'HTTP2', 'HTTP2Parameters' => $sw()),
            'fields' => array(array('type' => 'switch', 'key' => 'HTTP2Parameters.Switch', 'label' => '启用')),
        ),
        'QUIC' => array(
            'label' => 'HTTP/3 (QUIC)', 'category' => 'network', 'paid' => true,
            'defaults' => array('Name' => 'QUIC', 'QUICParameters' => $sw('off')),
            'fields' => array(array('type' => 'switch', 'key' => 'QUICParameters.Switch', 'label' => '启用')),
        ),
        'WebSocket' => array(
            'label' => 'WebSocket', 'category' => 'network',
            'defaults' => array('Name' => 'WebSocket', 'WebSocketParameters' => array('Switch' => 'on', 'Timeout' => 30)),
            'fields' => array(
                array('type' => 'switch', 'key' => 'WebSocketParameters.Switch', 'label' => '启用'),
                array('type' => 'number', 'key' => 'WebSocketParameters.Timeout', 'label' => '超时（秒）', 'min' => 1, 'max' => 300),
            ),
        ),
        'PostMaxSize' => array(
            'label' => '最大上传大小', 'category' => 'network',
            'defaults' => array('Name' => 'PostMaxSize', 'PostMaxSizeParameters' => array('Switch' => 'on', 'MaxSize' => 524288000)),
            'fields' => array(
                array('type' => 'switch', 'key' => 'PostMaxSizeParameters.Switch', 'label' => '启用'),
                array('type' => 'number', 'key' => 'PostMaxSizeParameters.MaxSize', 'label' => '最大字节数'),
            ),
        ),
        'Compression' => array(
            'label' => '智能压缩', 'category' => 'network',
            'defaults' => array('Name' => 'Compression', 'CompressionParameters' => array('Switch' => 'on', 'Algorithms' => array('gzip', 'brotli'))),
            'fields' => array(
                array('type' => 'switch', 'key' => 'CompressionParameters.Switch', 'label' => '启用智能压缩'),
                array('type' => 'checkboxes', 'key' => 'CompressionParameters.Algorithms', 'label' => '压缩算法', 'options' => array('gzip' => 'Gzip（通用）', 'brotli' => 'Brotli（体积更小）')),
            ),
        ),
        'ContentCompression' => array(
            'label' => '内容压缩', 'category' => 'network',
            'defaults' => array('Name' => 'ContentCompression', 'ContentCompressionParameters' => $sw()),
            'fields' => array(array('type' => 'switch', 'key' => 'ContentCompressionParameters.Switch', 'label' => '启用内容压缩（Brotli + Gzip）')),
        ),
        'ResponseSpeedLimit' => array(
            'label' => '单连接下载限速', 'category' => 'network',
            'defaults' => array('Name' => 'ResponseSpeedLimit', 'ResponseSpeedLimitParameters' => array(
                'Mode' => 'LimitUponDownload',
                'MaxSpeed' => '1024KB/s',
                'StartAt' => '0KB',
            )),
            'fields' => array(
                array('type' => 'select', 'key' => 'ResponseSpeedLimitParameters.Mode', 'label' => '限速模式', 'options' => array(
                    'LimitUponDownload' => '全过程下载限速',
                    'LimitAfterSpecificBytesDownloaded' => '全速下载特定字节后限速',
                    'LimitAfterSpecificSecondsDownloaded' => '全速下载特定时间后限速',
                )),
                array('type' => 'text', 'key' => 'ResponseSpeedLimitParameters.MaxSpeed', 'label' => '限速值（如 1024KB/s 或变量）'),
                array('type' => 'text', 'key' => 'ResponseSpeedLimitParameters.StartAt', 'label' => '限速开始值（字节 KB 或秒 s）'),
            ),
        ),
        'SmartRouting' => array(
            'label' => '智能加速', 'category' => 'network', 'paid' => true,
            'defaults' => array('Name' => 'SmartRouting', 'SmartRoutingParameters' => $sw('off')),
            'fields' => array(array('type' => 'switch', 'key' => 'SmartRoutingParameters.Switch', 'label' => '启用')),
        ),
        'UpstreamHTTP2' => array(
            'label' => 'HTTP/2 回源', 'category' => 'network',
            'defaults' => array('Name' => 'UpstreamHTTP2', 'UpstreamHTTP2Parameters' => $sw('off')),
            'fields' => array(array('type' => 'switch', 'key' => 'UpstreamHTTP2Parameters.Switch', 'label' => '启用')),
        ),
        'HTTPUpstreamTimeout' => array(
            'label' => '回源超时时间', 'category' => 'network',
            'defaults' => array('Name' => 'HTTPUpstreamTimeout', 'HTTPUpstreamTimeoutParameters' => array('ResponseTimeout' => 15)),
            'fields' => array(array('type' => 'number', 'key' => 'HTTPUpstreamTimeoutParameters.ResponseTimeout', 'label' => '响应超时（秒）', 'min' => 1, 'max' => 600)),
        ),
        'ForceRedirectHTTPS' => array(
            'label' => '强制 HTTPS', 'category' => 'https',
            'defaults' => array('Name' => 'ForceRedirectHTTPS', 'ForceRedirectHTTPSParameters' => array('Switch' => 'on', 'RedirectStatusCode' => 302)),
            'fields' => array(
                array('type' => 'switch', 'key' => 'ForceRedirectHTTPSParameters.Switch', 'label' => '启用'),
                array('type' => 'select', 'key' => 'ForceRedirectHTTPSParameters.RedirectStatusCode', 'label' => '跳转方式', 'options' => array(301 => '301 永久跳转', 302 => '302 临时跳转')),
            ),
        ),
        'HSTS' => array(
            'label' => 'HSTS 配置', 'category' => 'https',
            'defaults' => array('Name' => 'HSTS', 'HSTSParameters' => array('Switch' => 'off', 'Timeout' => 0, 'IncludeSubDomains' => 'off', 'Preload' => 'off')),
            'fields' => array(
                array('type' => 'switch', 'key' => 'HSTSParameters.Switch', 'label' => '启用 HSTS'),
                array('type' => 'duration', 'key' => 'HSTSParameters.Timeout', 'label' => 'max-age'),
                array('type' => 'switch', 'key' => 'HSTSParameters.IncludeSubDomains', 'label' => '包含子域名'),
                array('type' => 'switch', 'key' => 'HSTSParameters.Preload', 'label' => 'Preload'),
            ),
        ),
        'TLSConfig' => array(
            'label' => 'SSL/TLS 安全配置', 'category' => 'https',
            'defaults' => array('Name' => 'TLSConfig', 'TLSConfigParameters' => array('Version' => array('TLSv1.2', 'TLSv1.3'), 'CipherSuite' => 'loose-v2023')),
            'fields' => array(
                array('type' => 'checkboxes', 'key' => 'TLSConfigParameters.Version', 'label' => 'TLS 版本', 'options' => array('TLSv1' => 'TLS 1.0', 'TLSv1.1' => 'TLS 1.1', 'TLSv1.2' => 'TLS 1.2', 'TLSv1.3' => 'TLS 1.3')),
                array('type' => 'select', 'key' => 'TLSConfigParameters.CipherSuite', 'label' => '加密套件', 'options' => array('loose-v2023' => '宽松模式（兼容旧浏览器）', 'strict-v2023' => '严格模式（更安全）')),
            ),
        ),
        'OCSPStapling' => array(
            'label' => 'OCSP 装订', 'category' => 'https',
            'defaults' => array('Name' => 'OCSPStapling', 'OCSPStaplingParameters' => $sw('off')),
            'fields' => array(array('type' => 'switch', 'key' => 'OCSPStaplingParameters.Switch', 'label' => '启用')),
        ),
        'OriginPullProtocol' => array(
            'label' => '回源 HTTPS', 'category' => 'https',
            'defaults' => array('Name' => 'OriginPullProtocol', 'OriginPullProtocolParameters' => array('Protocol' => 'follow')),
            'fields' => array(array('type' => 'select', 'key' => 'OriginPullProtocolParameters.Protocol', 'label' => '回源协议', 'options' => array('follow' => '跟随访问协议', 'http' => '强制 HTTP 回源', 'https' => '强制 HTTPS 回源'))),
        ),
        'ModifyResponseHeader' => array(
            'label' => '修改 HTTP 节点响应头', 'category' => 'headers',
            'defaults' => array('Name' => 'ModifyResponseHeader', 'ModifyResponseHeaderParameters' => array('HeaderActions' => array())),
            'fields' => array(array('type' => 'header_rows', 'key' => 'ModifyResponseHeaderParameters.HeaderActions', 'label' => '响应头规则', 'withValue' => true)),
        ),
        'ClientIPHeader' => array(
            'label' => '客户端 IP 头部', 'category' => 'headers',
            'defaults' => array('Name' => 'ClientIPHeader', 'ClientIPHeaderParameters' => array('Switch' => 'off', 'HeaderName' => '')),
            'fields' => array(
                array('type' => 'switch', 'key' => 'ClientIPHeaderParameters.Switch', 'label' => '启用'),
                array('type' => 'text', 'key' => 'ClientIPHeaderParameters.HeaderName', 'label' => 'Header 名称'),
            ),
        ),
        'ClientIPCountry' => array(
            'label' => '客户端 IP 地理位置头部', 'category' => 'headers',
            'defaults' => array('Name' => 'ClientIPCountry', 'ClientIPCountryParameters' => array('Switch' => 'off', 'HeaderName' => '')),
            'fields' => array(
                array('type' => 'switch', 'key' => 'ClientIPCountryParameters.Switch', 'label' => '启用'),
                array('type' => 'text', 'key' => 'ClientIPCountryParameters.HeaderName', 'label' => 'Header 名称'),
            ),
        ),
        'ModifyRequestHeader' => array(
            'label' => '修改 HTTP 回源请求头', 'category' => 'headers',
            'defaults' => array('Name' => 'ModifyRequestHeader', 'ModifyRequestHeaderParameters' => array('HeaderActions' => array())),
            'fields' => array(array('type' => 'header_rows', 'key' => 'ModifyRequestHeaderParameters.HeaderActions', 'label' => '回源请求头规则', 'withValue' => false)),
        ),
        'HostHeader' => array(
            'label' => 'Host Header 重写', 'category' => 'headers',
            'defaults' => array('Name' => 'HostHeader', 'HostHeaderParameters' => array('Action' => 'followOrigin', 'ServerName' => '')),
            'fields' => array(
                array('type' => 'select', 'key' => 'HostHeaderParameters.Action', 'label' => '行为', 'options' => array('followOrigin' => '跟随源站', 'custom' => '自定义 Host')),
                array('type' => 'text', 'key' => 'HostHeaderParameters.ServerName', 'label' => '自定义 Host 名称'),
            ),
        ),
        'AccessURLRedirect' => array(
            'label' => '访问 URL 重定向', 'category' => 'advanced',
            'defaults' => array('Name' => 'AccessURLRedirect', 'AccessURLRedirectParameters' => array(
                'StatusCode' => 302,
                'Protocol' => 'follow',
                'HostName' => array('Action' => 'follow', 'Value' => ''),
                'URLPath' => array('Action' => 'follow', 'Value' => '', 'Regex' => ''),
                'QueryString' => array('Action' => 'full'),
            )),
            'fields' => array(
                array('type' => 'select', 'key' => 'AccessURLRedirectParameters.StatusCode', 'label' => '跳转状态码', 'options' => array(
                    301 => '301 永久跳转', 302 => '302 临时跳转', 303 => '303 See Other', 307 => '307 临时重定向', 308 => '308 永久重定向',
                ), 'hint' => 'EdgeOne 直接返回 3xx，无需源站生成跳转'),
                array('type' => 'select', 'key' => 'AccessURLRedirectParameters.Protocol', 'label' => '目标协议', 'options' => array(
                    'follow' => '跟随请求协议', 'http' => '强制 HTTP', 'https' => '强制 HTTPS',
                )),
                array('type' => 'select', 'key' => 'AccessURLRedirectParameters.HostName.Action', 'label' => '目标域名', 'options' => array(
                    'follow' => '跟随请求域名', 'custom' => '自定义域名',
                )),
                array('type' => 'text', 'key' => 'AccessURLRedirectParameters.HostName.Value', 'label' => '自定义域名', 'hint' => '选择「自定义域名」时填写，如 www.example.com'),
                array('type' => 'select', 'key' => 'AccessURLRedirectParameters.URLPath.Action', 'label' => '目标路径', 'options' => array(
                    'follow' => '跟随请求路径', 'custom' => '自定义完整路径', 'regex' => '正则替换路径',
                )),
                array('type' => 'text', 'key' => 'AccessURLRedirectParameters.URLPath.Value', 'label' => '路径/替换结果', 'hint' => '自定义路径或正则替换后的目标路径'),
                array('type' => 'text', 'key' => 'AccessURLRedirectParameters.URLPath.Regex', 'label' => '路径正则表达式', 'hint' => '选择「正则替换」时填写，支持 $1～$9 引用捕获组'),
                array('type' => 'select', 'key' => 'AccessURLRedirectParameters.QueryString.Action', 'label' => '查询参数', 'options' => array(
                    'full' => '保留全部查询参数', 'ignore' => '丢弃全部查询参数',
                )),
            ),
        ),
        'Authentication' => array(
            'label' => 'Token 鉴权', 'category' => 'advanced',
            'defaults' => array('Name' => 'Authentication', 'AuthenticationParameters' => array('AuthType' => 'TypeA', 'Timeout' => 5, 'SecretKey' => '', 'BackupSecretKey' => '', 'AuthParam' => '')),
            'fields' => array(
                array('type' => 'select', 'key' => 'AuthenticationParameters.AuthType', 'label' => '鉴权类型', 'options' => array(
                    'TypeA' => 'TypeA（URL 参数鉴权，常用）',
                    'TypeB' => 'TypeB（时间戳鉴权）',
                    'TypeC' => 'TypeC（Cookie 鉴权）',
                    'TypeD' => 'TypeD（自定义鉴权）',
                )),
                array('type' => 'number', 'key' => 'AuthenticationParameters.Timeout', 'label' => '链接有效时间（分钟）', 'min' => 1, 'max' => 1440, 'hint' => '生成的鉴权链接在多少分钟内有效'),
                array('type' => 'text', 'key' => 'AuthenticationParameters.SecretKey', 'label' => '主密钥'),
                array('type' => 'text', 'key' => 'AuthenticationParameters.BackupSecretKey', 'label' => '备密钥'),
                array('type' => 'text', 'key' => 'AuthenticationParameters.AuthParam', 'label' => '鉴权参数名'),
            ),
        ),
        'ModifyOrigin' => array(
            'label' => '修改源站', 'category' => 'advanced',
            'defaults' => array('Name' => 'ModifyOrigin', 'ModifyOriginParameters' => array('OriginType' => 'IPDomain', 'Origin' => '', 'OriginProtocol' => 'follow', 'HTTPOriginPort' => 80, 'HTTPSOriginPort' => 443)),
            'fields' => array(
                array('type' => 'select', 'key' => 'ModifyOriginParameters.OriginType', 'label' => '源站类型', 'options' => array('IPDomain' => 'IP/域名', 'OriginGroup' => '源站组')),
                array('type' => 'text', 'key' => 'ModifyOriginParameters.Origin', 'label' => '源站地址'),
                array('type' => 'select', 'key' => 'ModifyOriginParameters.OriginProtocol', 'label' => '回源协议', 'options' => array('follow' => '跟随', 'http' => 'HTTP', 'https' => 'HTTPS')),
                array('type' => 'number', 'key' => 'ModifyOriginParameters.HTTPOriginPort', 'label' => 'HTTP 回源端口', 'min' => 1, 'max' => 65535),
                array('type' => 'number', 'key' => 'ModifyOriginParameters.HTTPSOriginPort', 'label' => 'HTTPS 回源端口', 'min' => 1, 'max' => 65535),
            ),
        ),
        'UpstreamURLRewrite' => array(
            'label' => '回源 URL 重写', 'category' => 'advanced',
            'defaults' => array('Name' => 'UpstreamURLRewrite', 'UpstreamURLRewriteParameters' => array('Type' => 'Path', 'Action' => 'addPrefix', 'Value' => '')),
            'fields' => array(
                array('type' => 'select', 'key' => 'UpstreamURLRewriteParameters.Type', 'label' => '重写类型', 'options' => array('Path' => '路径', 'QueryString' => '查询字符串')),
                array('type' => 'select', 'key' => 'UpstreamURLRewriteParameters.Action', 'label' => '重写方式', 'options' => array('addPrefix' => '添加前缀', 'rmPrefix' => '删除前缀', 'replace' => '替换')),
                array('type' => 'text', 'key' => 'UpstreamURLRewriteParameters.Value', 'label' => '重写值'),
            ),
        ),
        'UpstreamRequest' => array(
            'label' => '回源请求参数设置', 'category' => 'advanced',
            'defaults' => array('Name' => 'UpstreamRequest', 'UpstreamRequestParameters' => array('QueryString' => array('Switch' => 'off', 'Action' => 'includeCustom', 'Values' => array()), 'Cookie' => array('Switch' => 'off', 'Action' => 'full'))),
            'fields' => array(
                array('type' => 'switch', 'key' => 'UpstreamRequestParameters.QueryString.Switch', 'label' => '自定义查询字符串'),
                array('type' => 'select', 'key' => 'UpstreamRequestParameters.QueryString.Action', 'label' => '查询字符串规则', 'options' => array('includeCustom' => '保留指定', 'excludeCustom' => '忽略指定', 'full' => '保留全部', 'ignore' => '忽略全部')),
                array('type' => 'taglist', 'key' => 'UpstreamRequestParameters.QueryString.Values', 'label' => '查询参数名'),
                array('type' => 'switch', 'key' => 'UpstreamRequestParameters.Cookie.Switch', 'label' => '自定义 Cookie'),
                array('type' => 'select', 'key' => 'UpstreamRequestParameters.Cookie.Action', 'label' => 'Cookie 规则', 'options' => array('full' => '保留全部', 'ignore' => '忽略全部')),
            ),
        ),
        'UpstreamFollowRedirect' => array(
            'label' => '回源跟随重定向', 'category' => 'advanced',
            'defaults' => array('Name' => 'UpstreamFollowRedirect', 'UpstreamFollowRedirectParameters' => array('Switch' => 'on', 'MaxTimes' => 3)),
            'fields' => array(
                array('type' => 'switch', 'key' => 'UpstreamFollowRedirectParameters.Switch', 'label' => '启用'),
                array('type' => 'number', 'key' => 'UpstreamFollowRedirectParameters.MaxTimes', 'label' => '最大次数', 'min' => 1, 'max' => 5),
            ),
        ),
        'ErrorPage' => array(
            'label' => '自定义错误页面', 'category' => 'advanced',
            'defaults' => array('Name' => 'ErrorPage', 'ErrorPageParameters' => array('ErrorPageParams' => array())),
            'fields' => array(array('type' => 'error_page_rows', 'key' => 'ErrorPageParameters.ErrorPageParams', 'label' => '错误页面规则')),
        ),
        'RangeOriginPull' => array(
            'label' => '分片回源', 'category' => 'advanced',
            'defaults' => array('Name' => 'RangeOriginPull', 'RangeOriginPullParameters' => $sw('off')),
            'fields' => array(array('type' => 'switch', 'key' => 'RangeOriginPullParameters.Switch', 'label' => '启用')),
        ),
        'HttpResponse' => array(
            'label' => 'HTTP 应答', 'category' => 'advanced',
            'defaults' => array('Name' => 'HttpResponse', 'HttpResponseParameters' => array('StatusCode' => 403, 'ResponsePage' => '')),
            'fields' => array(
                array('type' => 'number', 'key' => 'HttpResponseParameters.StatusCode', 'label' => '应答状态码', 'min' => 100, 'max' => 599),
                array('type' => 'text', 'key' => 'HttpResponseParameters.ResponsePage', 'label' => '自定义页面 ID', 'hint' => '在 EdgeOne 控制台创建的自定义错误页 ID'),
            ),
        ),
        'Shield' => array(
            'label' => '源站卸载', 'category' => 'advanced',
            'defaults' => array('Name' => 'Shield', 'ShieldParameters' => array('ShieldSpaceId' => '')),
            'fields' => array(array('type' => 'text', 'key' => 'ShieldParameters.ShieldSpaceId', 'label' => '源站卸载空间 ID')),
        ),
        'SiteFailover' => array(
            'label' => '源站故障转移', 'category' => 'advanced', 'paid' => true,
            'defaults' => array('Name' => 'SiteFailover', 'SiteFailoverParameters' => array(
                'SiteFailoverStatusCodes' => array(502, 503),
                'SiteFailoverParams' => array(array(
                    'Mode' => 'FailoverToHost',
                    'Origin' => '',
                    'OriginProtocol' => 'follow',
                    'HTTPOriginPort' => 80,
                    'HTTPSOriginPort' => 443,
                )),
            )),
            'fields' => array(
                array('type' => 'taglist', 'key' => 'SiteFailoverParameters.SiteFailoverStatusCodes', 'label' => '触发状态码', 'hint' => '源站返回这些状态码时执行转移，如 502,503,504（4xx 或 5xx）'),
                array('type' => 'failover_rows', 'key' => 'SiteFailoverParameters.SiteFailoverParams', 'label' => '故障转移策略', 'hint' => '最多 2 条，按顺序尝试备用源站或跳转'),
            ),
        ),
        'SetContentIdentifier' => array(
            'label' => '设置内容标识符', 'category' => 'advanced',
            'defaults' => array('Name' => 'SetContentIdentifier', 'SetContentIdentifierParameters' => array('ContentIdentifier' => '')),
            'fields' => array(array('type' => 'text', 'key' => 'SetContentIdentifierParameters.ContentIdentifier', 'label' => '内容标识 ID')),
        ),
        'OriginAuthentication' => array(
            'label' => '回源鉴权', 'category' => 'advanced', 'paid' => true,
            'defaults' => array('Name' => 'OriginAuthentication', 'OriginAuthenticationParameters' => array(
                'RequestProperties' => array(array('Type' => 'QueryString', 'Name' => '', 'Value' => '')),
            )),
            'fields' => array(
                array('type' => 'auth_property_rows', 'key' => 'OriginAuthenticationParameters.RequestProperties', 'label' => '回源鉴权参数', 'hint' => '回源时在 URL 参数或请求头中携带鉴权信息（白名单功能）'),
            ),
        ),
        'Vary' => array(
            'label' => 'Vary 特性', 'category' => 'advanced',
            'defaults' => array('Name' => 'Vary', 'VaryParameters' => $sw('off')),
            'fields' => array(array('type' => 'switch', 'key' => 'VaryParameters.Switch', 'label' => '启用')),
        ),
    );
}

/**
 * @return array<string, mixed>
 */
function vs_edgeone_rules_catalog_export()
{
    $categories = array(
        'cache' => '缓存配置',
        'network' => '网络优化',
        'https' => 'HTTPS 优化',
        'headers' => '修改 HTTP 头',
        'advanced' => '高级配置',
    );

    return array(
        'matchTypes' => vs_edgeone_rules_match_types(),
        'operators'  => vs_edgeone_rules_operators(),
        'actions'    => vs_edgeone_rules_action_catalog(),
        'categories' => $categories,
        'matchGroups' => array(
            'client' => '客户端请求',
            'origin' => '源站响应',
        ),
        'timeUnits' => array(
            array('id' => 's', 'label' => '秒', 'mult' => 1),
            array('id' => 'm', 'label' => '分', 'mult' => 60),
            array('id' => 'h', 'label' => '时', 'mult' => 3600),
            array('id' => 'd', 'label' => '天', 'mult' => 86400),
        ),
        'paramLabels' => array(
            'CacheParameters' => '节点缓存配置',
            'CacheKeyParameters' => 'Cache Key 配置',
            'MaxAgeParameters' => '浏览器缓存配置',
            'ModifyOriginParameters' => '修改源站配置',
            'HeaderActions' => 'HTTP 头规则',
            'AccessURLRedirectParameters' => 'URL 重定向配置',
            'SiteFailoverParameters' => '源站故障转移配置',
            'OriginAuthenticationParameters' => '回源鉴权配置',
            'RequestProperties' => '鉴权参数列表',
            'HostName' => '目标域名',
            'URLPath' => '目标路径',
            'QueryString' => '查询参数',
            'Switch' => '开关',
            'StatusCode' => '状态码',
            'Protocol' => '协议',
            'Action' => '行为',
            'Value' => '值',
            'Regex' => '正则表达式',
        ),
    );
}

/**
 * @param array<string, mixed> $post
 * @return array<string, mixed>
 */
function vs_edgeone_build_rule_item_from_json(array $post)
{
    $raw = trim(isset($post['rule_json']) ? (string) $post['rule_json'] : '');
    if ($raw === '') {
        throw new Exception('规则内容为空');
    }
    $item = json_decode($raw, true);
    if (!is_array($item)) {
        throw new Exception('规则 JSON 格式无效');
    }
    $name = trim(isset($item['RuleName']) ? (string) $item['RuleName'] : '');
    if ($name === '') {
        throw new Exception('请填写规则名称');
    }
    if (!isset($item['Branches']) || !is_array($item['Branches']) || count($item['Branches']) === 0) {
        throw new Exception('请至少配置一个 IF 条件分支');
    }
    $status = isset($item['Status']) ? strtolower((string) $item['Status']) : 'disable';
    if (!in_array($status, array('enable', 'disable'), true)) {
        $status = 'disable';
    }
    $item['RuleName'] = $name;
    $item['Status'] = $status;
    if (isset($item['RuleId']) && trim((string) $item['RuleId']) === '') {
        unset($item['RuleId']);
    }
    unset($item['RulePriority']);

    return $item;
}
