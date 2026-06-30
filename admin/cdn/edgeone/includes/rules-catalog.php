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
        array('id' => 'host', 'label' => 'HOST', 'group' => 'client', 'var' => '${http.request.host}', 'needsName' => false),
        array('id' => 'url_path', 'label' => 'URL Path', 'group' => 'client', 'var' => '${http.request.uri.path}', 'needsName' => false),
        array('id' => 'url_full', 'label' => 'URL Full', 'group' => 'client', 'var' => '${http.request.full_uri}', 'needsName' => false),
        array('id' => 'query_string', 'label' => '查询字符串', 'group' => 'client', 'var' => '${http.request.uri.query}', 'needsName' => false, 'kv' => true),
        array('id' => 'file_extension', 'label' => '文件后缀', 'group' => 'client', 'var' => '${http.request.file_extension}', 'needsName' => false),
        array('id' => 'file_name', 'label' => '文件名称', 'group' => 'client', 'var' => '${http.request.filename}', 'needsName' => false),
        array('id' => 'request_header', 'label' => 'HTTP 请求头', 'group' => 'client', 'var' => '${http.request.headers["%s"]}', 'needsName' => true),
        array('id' => 'client_geo', 'label' => '客户端地理位置', 'group' => 'client', 'var' => '${http.request.ip.country}', 'needsName' => false),
        array('id' => 'client_isp', 'label' => '客户端运营商', 'group' => 'client', 'var' => '${http.request.ip.isp}', 'needsName' => false),
        array('id' => 'request_protocol', 'label' => '请求协议', 'group' => 'client', 'var' => '${http.request.scheme}', 'needsName' => false),
        array('id' => 'client_ip', 'label' => '客户端 IP', 'group' => 'client', 'var' => '${http.request.ip}', 'needsName' => false),
        array('id' => 'request_method', 'label' => '请求方法', 'group' => 'client', 'var' => '${http.request.method}', 'needsName' => false),
        array('id' => 'cookie', 'label' => 'Cookie', 'group' => 'client', 'var' => '${http.request.uri.args["%s"]}', 'needsName' => true, 'kv' => true),
        array('id' => 'response_header', 'label' => 'HTTP 响应头', 'group' => 'origin', 'var' => '${http.response.headers["%s"]}', 'needsName' => true, 'response' => true),
        array('id' => 'response_status', 'label' => 'HTTP 响应状态码', 'group' => 'origin', 'var' => '${http.response.status_code}', 'needsName' => false, 'response' => true),
        array('id' => 'all', 'label' => '全部（站点任意请求）', 'group' => 'client', 'var' => '', 'needsName' => false, 'all' => true),
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
            'defaults' => array('Name' => 'Cache', 'CacheParameters' => array('FollowOrigin' => array('Switch' => 'on', 'DefaultCache' => 'on', 'DefaultCacheStrategy' => 'on', 'DefaultCacheTime' => 0))),
            'fields' => array(
                array('type' => 'select', 'key' => 'CacheParameters._uiMode', 'label' => '缓存行为', 'options' => array('follow_origin' => '跟随源站', 'no_cache' => '不缓存', 'custom' => '自定义 TTL')),
                array('type' => 'number', 'key' => 'CacheParameters.CustomTime.CacheTime', 'label' => '自定义缓存时间（秒）', 'min' => 0, 'max' => 31536000),
                array('type' => 'switch', 'key' => 'CacheParameters.CustomTime.IgnoreCacheControl', 'label' => '忽略 Cache-Control'),
            ),
        ),
        'MaxAge' => array(
            'label' => '浏览器缓存 TTL', 'category' => 'cache',
            'defaults' => array('Name' => 'MaxAge', 'MaxAgeParameters' => array('FollowOrigin' => 'off', 'CacheTime' => 600)),
            'fields' => array(
                array('type' => 'select', 'key' => 'MaxAgeParameters.FollowOrigin', 'label' => '跟随源站 Cache-Control', 'options' => array('on' => '开启', 'off' => '关闭')),
                array('type' => 'number', 'key' => 'MaxAgeParameters.CacheTime', 'label' => '浏览器缓存时间（秒）', 'min' => 0, 'max' => 31536000),
                array('type' => 'switch', 'key' => 'MaxAgeParameters.CustomTime.Switch', 'label' => '启用自定义时间'),
                array('type' => 'number', 'key' => 'MaxAgeParameters.CustomTime.CacheTime', 'label' => '自定义缓存（秒）', 'min' => 0, 'max' => 31536000),
            ),
        ),
        'CacheKey' => array(
            'label' => '自定义 Cache Key', 'category' => 'cache',
            'defaults' => array('Name' => 'CacheKey', 'CacheKeyParameters' => array('FullURLCache' => 'on', 'IgnoreCase' => 'off', 'QueryString' => array('Switch' => 'off', 'Action' => 'includeCustom', 'Values' => array()))),
            'fields' => array(array('type' => 'json', 'key' => 'CacheKeyParameters', 'label' => 'CacheKeyParameters（JSON）')),
        ),
        'StatusCodeCache' => array(
            'label' => '状态码缓存 TTL', 'category' => 'cache',
            'defaults' => array('Name' => 'StatusCodeCache', 'StatusCodeCacheParameters' => array('StatusCodeCacheParams' => array(array('StatusCode' => 404, 'CacheTime' => 10)))),
            'fields' => array(array('type' => 'json', 'key' => 'StatusCodeCacheParameters', 'label' => 'StatusCodeCacheParameters（JSON）')),
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
                array('type' => 'select', 'key' => 'ForceRedirectHTTPSParameters.RedirectStatusCode', 'label' => '状态码', 'options' => array(301, 302)),
            ),
        ),
        'HSTS' => array(
            'label' => 'HSTS 配置', 'category' => 'https',
            'defaults' => array('Name' => 'HSTS', 'HSTSParameters' => array('Switch' => 'off', 'Timeout' => 0, 'IncludeSubDomains' => 'off', 'Preload' => 'off')),
            'fields' => array(
                array('type' => 'switch', 'key' => 'HSTSParameters.Switch', 'label' => '启用 HSTS'),
                array('type' => 'number', 'key' => 'HSTSParameters.Timeout', 'label' => 'max-age（秒）', 'min' => 0, 'max' => 31536000),
                array('type' => 'switch', 'key' => 'HSTSParameters.IncludeSubDomains', 'label' => '包含子域名'),
                array('type' => 'switch', 'key' => 'HSTSParameters.Preload', 'label' => 'Preload'),
            ),
        ),
        'TLSConfig' => array(
            'label' => 'SSL/TLS 安全配置', 'category' => 'https',
            'defaults' => array('Name' => 'TLSConfig', 'TLSConfigParameters' => array('Version' => array('TLSv1.2', 'TLSv1.3'), 'CipherSuite' => 'loose-v2023')),
            'fields' => array(array('type' => 'json', 'key' => 'TLSConfigParameters', 'label' => 'TLSConfigParameters（JSON）')),
        ),
        'OCSPStapling' => array(
            'label' => 'OCSP 装订', 'category' => 'https',
            'defaults' => array('Name' => 'OCSPStapling', 'OCSPStaplingParameters' => $sw('off')),
            'fields' => array(array('type' => 'switch', 'key' => 'OCSPStaplingParameters.Switch', 'label' => '启用')),
        ),
        'OriginPullProtocol' => array(
            'label' => '回源 HTTPS', 'category' => 'https',
            'defaults' => array('Name' => 'OriginPullProtocol', 'OriginPullProtocolParameters' => array('Protocol' => 'follow')),
            'fields' => array(array('type' => 'select', 'key' => 'OriginPullProtocolParameters.Protocol', 'label' => '协议', 'options' => array('follow', 'http', 'https'))),
        ),
        'ModifyResponseHeader' => array(
            'label' => '修改 HTTP 节点响应头', 'category' => 'headers',
            'defaults' => array('Name' => 'ModifyResponseHeader', 'ModifyResponseHeaderParameters' => array('HeaderActions' => array())),
            'fields' => array(array('type' => 'json', 'key' => 'ModifyResponseHeaderParameters', 'label' => 'ModifyResponseHeaderParameters（JSON）')),
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
            'fields' => array(array('type' => 'json', 'key' => 'ModifyRequestHeaderParameters', 'label' => 'ModifyRequestHeaderParameters（JSON）')),
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
            'defaults' => array('Name' => 'AccessURLRedirect', 'AccessURLRedirectParameters' => array('StatusCode' => 302, 'Protocol' => 'follow', 'HostName' => array('Action' => 'follow'), 'URLPath' => array('Action' => 'follow'), 'QueryString' => array('Action' => 'full'))),
            'fields' => array(array('type' => 'json', 'key' => 'AccessURLRedirectParameters', 'label' => 'AccessURLRedirectParameters（JSON）')),
        ),
        'Authentication' => array(
            'label' => 'Token 鉴权', 'category' => 'advanced',
            'defaults' => array('Name' => 'Authentication', 'AuthenticationParameters' => array('AuthType' => 'TypeA', 'Timeout' => 5, 'SecretKey' => '', 'BackupSecretKey' => '', 'AuthParam' => '')),
            'fields' => array(array('type' => 'json', 'key' => 'AuthenticationParameters', 'label' => 'AuthenticationParameters（JSON）')),
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
            'fields' => array(array('type' => 'json', 'key' => 'UpstreamURLRewriteParameters', 'label' => 'UpstreamURLRewriteParameters（JSON）')),
        ),
        'UpstreamRequest' => array(
            'label' => '回源请求参数设置', 'category' => 'advanced',
            'defaults' => array('Name' => 'UpstreamRequest', 'UpstreamRequestParameters' => array('QueryString' => array('Switch' => 'off', 'Action' => 'includeCustom', 'Values' => array()), 'Cookie' => array('Switch' => 'off', 'Action' => 'full'))),
            'fields' => array(array('type' => 'json', 'key' => 'UpstreamRequestParameters', 'label' => 'UpstreamRequestParameters（JSON）')),
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
            'fields' => array(array('type' => 'json', 'key' => 'ErrorPageParameters', 'label' => 'ErrorPageParameters（JSON）')),
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
                array('type' => 'text', 'key' => 'HttpResponseParameters.ResponsePage', 'label' => '自定义页面 ID 或内容'),
            ),
        ),
        'Shield' => array(
            'label' => '源站卸载', 'category' => 'advanced',
            'defaults' => array('Name' => 'Shield', 'ShieldParameters' => array('ShieldSpaceId' => '')),
            'fields' => array(array('type' => 'text', 'key' => 'ShieldParameters.ShieldSpaceId', 'label' => '源站卸载空间 ID')),
        ),
        'SiteFailover' => array(
            'label' => '源站故障转移', 'category' => 'advanced',
            'defaults' => array('Name' => 'SiteFailover', 'SiteFailoverParameters' => array('SiteFailoverStatusCodes' => array(502, 503), 'SiteFailoverParams' => array())),
            'fields' => array(array('type' => 'json', 'key' => 'SiteFailoverParameters', 'label' => '故障转移配置')),
        ),
        'SetContentIdentifier' => array(
            'label' => '设置内容标识符', 'category' => 'advanced',
            'defaults' => array('Name' => 'SetContentIdentifier', 'SetContentIdentifierParameters' => array('ContentIdentifier' => '')),
            'fields' => array(array('type' => 'text', 'key' => 'SetContentIdentifierParameters.ContentIdentifier', 'label' => '内容标识 ID')),
        ),
        'OriginAuthentication' => array(
            'label' => '回源鉴权', 'category' => 'advanced', 'paid' => true,
            'defaults' => array('Name' => 'OriginAuthentication', 'OriginAuthenticationParameters' => array('Switch' => 'off')),
            'fields' => array(array('type' => 'json', 'key' => 'OriginAuthenticationParameters', 'label' => '回源鉴权配置')),
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
