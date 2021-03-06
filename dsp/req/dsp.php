<?php
// bidRequestを受け取る
$res = file_get_contents('php://input');
$bid_req = json_decode($res, true);

// responseを返す
http_response_code(200);

// 広告リストの取得
$res = file_get_contents('/Users/sakuta/web/fancomi_intern/ready-for-miniDSP-Internship/ads.json');
$ads = json_decode($res, true);

// 広告のフィルタリング
$filtered_ads = array();
for ($i = 0; $i < count($ads); ++$i) {
    $counts = 0;
    $ids = $ads[$i]['blocked_app_ids'];
    for ($j = 0; $j < count($ids); ++$j) {
        if ($bid_req['app_id'] === $ids[$j]) {
            break;
        } else {
            $counts++;
        }
    }
    if ($counts === count($ids)) {
        array_push($filtered_ads,  $ads[$i]);
    }
}

// MLに送るデータの整形
$filtered_ads_ids = array();
for ($i = 0; $i < count($filtered_ads); ++$i) {
    array_push($filtered_ads_ids,  $filtered_ads[$i]['id']);
}
$data = [
    "ads" => $filtered_ads_ids,
    "user_id" => $bid_req['user_id']
];
$data = json_encode($data);

// MLにリクエストを投げる PredictRequest
$url = 'http://localhost:8079/ml/predict';
$ch = curl_init();

curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

$res = curl_exec($ch);
$predict_res = json_decode($res, true);

// 低い入札金額の広告を除外し、入札金額が一番高い広告を見つける
$filtered_predicts = array();
$max_key = "";
$max_value = "0";
foreach ($predict_res as $key => $value) {
    if ($bid_req['bidfloor'] <= $value) {
        array_push($filtered_predicts, array($key => $value));
        if ($max_value < $value) {
            $max_value = $value;
            $max_key = $key;
        }
    }
}

// SSPにbidresponseを送るデータの整形
$ads_url = '';
for ($i = 0; $i < count($filtered_ads); ++$i) {
    if ($filtered_ads[$i]['id'] === (string)$max_key) {
        $ads_url = $filtered_ads[$i]['url'];
    }
}

$data = [
    "request_id" => $bid_req['request_id'],
    "url" => $ads_url,
    "price" => $max_value
];
$data = json_encode($data);

// SSPにbidResponseを送る
header('Content-Type: application/json');
header('Content-Length: ' . strlen($data));
if (empty($filtered_predicts)) {
    http_response_code(204);
} else {
    echo $data;
}

