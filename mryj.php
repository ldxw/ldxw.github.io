<?php
/**
 * Date: 2020-01-12
 * Copyright: @LOGI
 * Support URL: https://logi.im
 *
 * 金山词霸
 * http://open.iciba.com/dsapi/
 * http://sentence.iciba.com/?c=dailysentence&m=getTodaySentence
 * http://sentence.iciba.com/?c=dailysentence&m=getdetail&title=2019-01-07
 *
 * 扇贝英语
 * https://web.shanbay.com/op/quotes/2019-04-09
 * https://rest.shanbay.com/api/v2/quote/quotes/today/
 * https://apiv3.shanbay.com/weapps/dailyquote/quote/?date=2019-06-29
 *
 * 有道词典
 * http://dict.youdao.com/infoline/style/cardList?style=daily&client=mobile
 *
 * 欧路词典
 * GET /api/v2/appsupport/DictMobileStartupContent HTTP/1.1
 * Authorization: QYN eyJ1c2VyaWQiOiIiLCJ0b2tlbiI6IiIsInZfZGljdCI6ZmFsc2UsInVybHNpZ24iOiJVdzVJbWZaaVZaWVpTTVZhU3Y3cEg1TVhlbTQ9IiwidmYiOjAsInQiOiJBQklNVFU1T1RZME5EUTBNQT09IiwiZmwiOjAsImxjIjowfQ==
 * User-Agent: /eusoft_eudic_en_android/7.5.0/cdb00fcf112cf5a6///
 * EudicUserAgent: /eusoft_eudic_en_android/7.5.0/cdb00fcf112cf5a6///
 * EudicTimezone: 8
 * Host: api.frdic.com
 */
$SUPPORT_URL = 'https://logi.im';
$API = [
    'shanbay' => ['url' => 'https://apiv3.shanbay.com/weapps/dailyquote/quote/'],
    'ciba' => ['url' => 'http://sentence.iciba.com/?c=dailysentence&m=getTodaySentence'],
    'youdao' => ['url' => 'http://dict.youdao.com/infoline/style/cardList?style=daily&client=mobile'],
    'eudic' => [
        'url' => 'https://api.frdic.com/api/v2/appsupport/DictMobileStartupContent',
        'header' => array(
            "Authorization: QYN eyJ1c2VyaWQiOiIiLCJ0b2tlbiI6IiIsInZfZGljdCI6ZmFsc2UsInVybHNpZ24iOiJVdzVJbWZaaVZaWVpTTVZhU3Y3cEg1TVhlbTQ9IiwidmYiOjAsInQiOiJBQklNVFU1T1RZME5EUTBNQT09IiwiZmwiOjAsImxjIjowfQ==",
            "User-Agent: /eusoft_eudic_en_android/7.5.0/cdb00fcf112cf5a6///",
        ),
    ],
];

function getContet($url, $header) {
    if (empty($header)) {
        $header = array(
            'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 5_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Mobile/9B176 MicroMessenger/4.3.2',
        );
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $header,
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function jsonDump($data) {
    return stripslashes(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function response($data) {
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=utf-8');
    exit($data);
}

$api = @$_GET['api'];
if (empty($api)) {
    $index = rand(0, count($API) - 1);
    $api = array_keys($API)[$index];
}

$data = json_decode(getContet($API[$api]['url'], $API[$api]['header']), true);
$failure = jsonDump(['code' => 1, 'message' => '解析失败', 'api' => $api, 'support_url' => $SUPPORT_URL]);
if (empty($data)) {
    response($failure);
}

try {
    switch ($api) {
    case 'ciba':
        $result = [
            'sentence' => $data['content'],
            'translation' => $data['note'],
            'source' => '',
            'pictures' => [
                $data['picture2'],
                $data['picture'],
                $data['picture3'],
            ],
        ];
        break;
    case 'youdao':
        $data = $data[0];
        $result = [
            'sentence' => $data['title'],
            'translation' => $data['summary'],
            'source' => $data['source'],
            'pictures' => $data['image'],
        ];
        break;
    case 'shanbay':
        $result = [
            'sentence' => $data['content'],
            'translation' => $data['translation'],
            'source' => $data['author'],
            'pictures' => $data['poster_img_urls'],
        ];
        break;
    case 'eudic':
        $result = [
            'sentence' => $data['sentence']['line'],
            'translation' => $data['sentence']['linecn'],
            'source' => '',
            'pictures' => [$data['sentence']['img']],
        ];
        break;
    }
    if (empty($result)) {
        response($failure);
    }

    $result['code'] = 0;
    $result['message'] = '解析成功';
    $result['date'] = date('Y-m-d');
    $result['api'] = $api;
    $result['support_url'] = $SUPPORT_URL;
    response(jsonDump($result));
} catch (Exception $e) {
    response($failure);
}
