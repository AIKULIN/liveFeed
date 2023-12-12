<?php

namespace App\Services;

use App\Models\FeedData;
use App\Models\IotCatBox;
use Illuminate\Support\Facades\Cache;

class FeedCatService
{

    /**
     * @param $id
     * @return string | null
     */
    public function getFeedData($id): string | null
    {
        $data = IotCatBox::query()->where('iot_mac_id', $id)->first();
        $iotId = $data->id?? null;
        if (Cache::get($iotId)) {
            Cache::decrement($iotId);
            return base64_encode($id . "goFeed");
        }

        return null;
    }

    public function addIotCatBox($id, $params): void
    {
        $create['name'] = $params['name']?? null;
        $create['location'] = $params['location']?? null;
        $create['iot_mac_id'] = $id;

        IotCatBox::query()->firstOrCreate([
            'iot_mac_id' => $id
        ], $create);
    }

    /**
     * 增加餵食
     * 對應IOT 設備運行
     * @param $params
     * @return void
     */
    public function addFeedCount($params): void
    {
        Cache::increment($params['iot_cat_box_id']);
    }

    public function checkAuth($auth)
    {
        $basicService = (new BasicService());
        // 驗證auth token
        $basicService->checkAuthorization($auth);
        // 解碼 token 抓取部份資訊
        return $basicService->dUid($auth);
    }
}
