<?php
// bidRequestを受け取る
$res = file_get_contents('php://input');
$bidResponse = json_decode($res, true);

// responseを返す
echo "HTTP/1.1 200 OK"."\n";

// 広告リストの取得
$res = file_get_contents('/Users/sakuta/web/fancomi_intern/ready-for-miniDSP-Internship/ads.json');
$ads = json_decode($res, true);

// 広告のフィルタリング
$filtered_ads = array();
for($i = 0; $i < count($ads); ++$i) {
    $counts = 0;
    $ids = $ads[$i]['blocked_app_ids'];
    for($j = 0; $j < count($ids); ++$j) {
        if ($bidResponse['app_id'] === $ids[$j]) {
            break;
        } else {
            $counts++;
        }
    }
    if ($counts === count($ids)) {
        array_push($filtered_ads,  $ads[$i]);
    }
}
