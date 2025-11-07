<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\User;

class HomeController extends \Blocs\Controllers\Base
{
    private $adminTopUserMonth = 5;

    public function __construct()
    {
        $this->viewPrefix = 'admin.home';
    }

    public function index()
    {
        $this->chart();

        docs('# 画面表示');
        $this->setupMenu();

        $view = view($this->viewPrefix.'.index', $this->val);
        unset($this->val, $this->request, $this->tableData);
        docs('テンプレートを読み込んで、HTMLを生成');

        return $view;
    }

    protected function chart()
    {
        // グラフX軸用の月ラベルを生成（現在月から過去N-1ヶ月分の配列）
        $monthLabels = $this->generateMonthLabels();

        // 現在のユーザー総数を取得して、ビューに渡す変数を設定
        $currentUserCount = User::count();
        $this->val['current'] = $currentUserCount;
        $this->val['update'] = date(DATE_ATOM);

        // 月ごとのユーザー作成数と削除数を集計
        $createCountsByMonth = [];
        $deleteCountsByMonth = [];
        $this->aggregateUserCountsByMonth($monthLabels, $createCountsByMonth, $deleteCountsByMonth);

        // グラフ描画用のデータ系列（棒グラフ×2、折れ線グラフ×1）を生成
        $graphSeriesData = $this->generateGraphSeriesData($monthLabels, $currentUserCount, $createCountsByMonth, $deleteCountsByMonth);

        // グラフデータを配列の逆順にしてJSON形式でビューに渡す
        $this->val['graphLabels'] = json_encode(array_reverse($monthLabels));
        $this->val['graphDataBar1'] = json_encode(array_reverse($graphSeriesData['bar1']));
        $this->val['graphDataBar2'] = json_encode(array_reverse($graphSeriesData['bar2']));
        $this->val['graphDataLine1'] = json_encode(array_reverse($graphSeriesData['line1']));

        // Y軸の最大値を適切なスケールに調整してビューに渡す
        $this->val['graphYaxisMax'] = self::calculateYaxisMax($graphSeriesData['yaxisMax']);
        $this->val['graphY2axisMax'] = self::calculateYaxisMax($graphSeriesData['y2axisMax']);
    }

    // グラフX軸用の月ラベルを生成（現在月から過去N-1ヶ月分の配列）
    private function generateMonthLabels()
    {
        $currentDate = date(DATE_ATOM);
        $monthLabels = [substr($currentDate, 0, 7)];
        [$year, $month] = explode('-', $monthLabels[0], 2);

        for ($i = 0; $i < $this->adminTopUserMonth - 1; $i++) {
            $month--;
            if (! $month) {
                $month = 12;
                $year--;
            }
            $monthLabels[] = sprintf('%d-%02d', $year, $month);
        }

        return $monthLabels;
    }

    // 月ごとのユーザー作成数と削除数を集計
    private function aggregateUserCountsByMonth($monthLabels, &$createCountsByMonth, &$deleteCountsByMonth)
    {
        $createCountsByMonth = [];
        $deleteCountsByMonth = [];

        // 集計対象期間の最古の月を基準にデータを取得
        $oldestMonth = $monthLabels[count($monthLabels) - 1];
        $userRecords = User::where(
            'created_at', '>', $oldestMonth.'-00'
        )->orWhere(
            'deleted_at', '>', $oldestMonth.'-00'
        )->withTrashed()->select('created_at', 'deleted_at')->get()->toArray();

        // 各ユーザーレコードの作成日・削除日を月単位で集計
        foreach ($userRecords as $record) {
            $createdMonth = substr($record['created_at'], 0, 7);
            isset($createCountsByMonth[$createdMonth]) || $createCountsByMonth[$createdMonth] = 0;
            $createCountsByMonth[$createdMonth]++;

            if (empty($record['deleted_at'])) {
                continue;
            }

            $deletedMonth = substr($record['deleted_at'], 0, 7);
            isset($deleteCountsByMonth[$deletedMonth]) || $deleteCountsByMonth[$deletedMonth] = 0;
            $deleteCountsByMonth[$deletedMonth]++;
        }
    }

    // グラフ描画用のデータ系列（棒グラフ×2、折れ線グラフ×1）を生成
    private function generateGraphSeriesData($monthLabels, $currentUserCount, $createCountsByMonth, $deleteCountsByMonth)
    {
        $lineSeriesData = [];
        $bar1SeriesData = [];
        $bar2SeriesData = [];
        $yaxisMaxValue = 0;
        $y2axisMaxValue = 0;
        $userCountAtMonth = $currentUserCount;

        // 各月のデータを逆算しながら系列データを作成
        foreach ($monthLabels as $monthLabel) {
            // 折れ線グラフ用：その月のユーザー数
            $lineSeriesData[] = $userCountAtMonth;
            $y2axisMaxValue < $userCountAtMonth && $y2axisMaxValue = $userCountAtMonth;

            // 棒グラフ1用：その月の作成数
            $createCountAtMonth = isset($createCountsByMonth[$monthLabel]) ? $createCountsByMonth[$monthLabel] : 0;
            $yaxisMaxValue < $createCountAtMonth && $yaxisMaxValue = $createCountAtMonth;
            $bar1SeriesData[] = $createCountAtMonth;
            $userCountAtMonth -= $createCountAtMonth;

            // 棒グラフ2用：その月の削除数
            $deleteCountAtMonth = isset($deleteCountsByMonth[$monthLabel]) ? $deleteCountsByMonth[$monthLabel] : 0;
            $yaxisMaxValue < $deleteCountAtMonth && $yaxisMaxValue = $deleteCountAtMonth;
            $bar2SeriesData[] = $deleteCountAtMonth;
            $userCountAtMonth += $deleteCountAtMonth;
        }

        return [
            'line1' => $lineSeriesData,
            'bar1' => $bar1SeriesData,
            'bar2' => $bar2SeriesData,
            'yaxisMax' => $yaxisMaxValue,
            'y2axisMax' => $y2axisMaxValue,
        ];
    }

    // Y軸の最大値を適切なスケールに調整（見やすいグラフにするため）
    private static function calculateYaxisMax($maxValue)
    {
        $scale = pow(10, strlen(floor($maxValue * 1.3)) - 1);
        $adjustedMaxValue = intval(ceil($maxValue * 1.3 / $scale) * $scale);
        ($adjustedMaxValue < 10) && $adjustedMaxValue = 10;

        return $adjustedMaxValue;
    }
}
