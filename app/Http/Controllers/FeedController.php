<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BasicService;
use App\Services\FeedCatService;
use App\Services\WebSocketService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedController extends Controller
{
    public FeedCatService $feedCatService;
    public BasicService $basicService;
    public WebSocketService $webSocketService;

    public function __construct(
        FeedCatService $feedCatService,
        BasicService   $basicService,
        WebSocketService $webSocketService,
    )
    {
        $this->feedCatService = $feedCatService;
        $this->basicService = $basicService;
        $this->webSocketService = $webSocketService;
    }

    public function auth(Request $request): array
    {
        return $this->webSocketService->auth($request->all());
    }

    /**
     * 檢查是否需餵食記錄
     *
     * @param $id
     * @param Request $request
     * @return string|null
     */
    public function iotGetData($id, Request $request)
    {
        return $this->feedCatService->getFeedData($id);
    }

    /**
     * 增加IOT 設備
     *
     * @param $id
     * @param Request $request
     * @return array|void
     */
    public function addIot($id, Request $request)
    {
        $data = $request->all();
        DB::beginTransaction();

        try {
            $this->feedCatService->addIotCatBox($id, $data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'meg' => $e->getMessage()
            ];
        }
    }

    /**
     * 使用者投食給貓咪
     *
     * @param Request $request
     * @return array
     */
    public function feedToCat(Request $request)
    {
        $params['iot_cat_box_id'] = $request->get('iot_cat_box_id');
        DB::beginTransaction();

        try {
            $this->feedCatService->addFeedCount($params);
            DB::commit();
            return ['status' => false, 'data' => []];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => false,
                'meg' => $e->getMessage()
            ];
        }

    }

    /**
     * Iot 預設資訊
     */
    public function iotSetting(Request $request)
    {
        $iotKey = $request->get('iotKey');
        if ($iotKey !== env("IOT_KEY", null)) {
            abort(401);
        }

        // 設備若未註冊，將自動新增
        $chipId = $request->get('chipId');
        $this->feedCatService->addIotCatBox($chipId, []);

        return [
            'timer_0_time' => 15000000, // 15秒 修改前端畫面『系統 : 15秒後方可再餵食』 說明也要手動改
        ];
    }

    /**
     * 直播互動頁
     *
     * @param Request $request
     * @return Application|Factory|View|\Illuminate\Foundation\Application
     */
    public function view(Request $request)
    {
        $auth = $request->get('auth');
        // 這裡認證資料有結合WIX
        $data = $this->feedCatService->checkAuth($auth);
        return view('feed_live', [
            'authToken' => $auth,
            'username' => $data['nickname']?? '神秘人',
            'iotId' => $data['iotId']?? 1,
            'chatId' => $data['chatId']?? 1,
        ]);
    }
}
