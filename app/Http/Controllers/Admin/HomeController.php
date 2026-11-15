<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\User;
use Blocs\Controllers\Base;

class HomeController extends Base
{
    private $adminTopUserMonth = 5;

    public function __construct()
    {
        $this->viewPrefix = 'admin.home';
    }

    public function index()
    {
        $this->chart();

        docs('# ホーム画面の表示準備');

        return $this->outputView('index', '画面のテンプレートを読み込み、ホーム画面のHTMLを生成する');
    }

    protected function chart()
    {
        docs('# ホーム画面の数値づくり');

        $monthLabels = $this->generateMonthLabels();
        docs('画面に並べる月の一覧をつくり、最近の流れを追いやすくする');

        // 現在のユーザー総数を取得して、ビューに渡す変数を設定
        $currentUserCount = User::count();
        $this->val['current'] = $currentUserCount;
        $this->val['update'] = date(DATE_ATOM);
        docs('今の利用者数と更新した時刻を調べ、画面上部に見せる値として保存する');

        // 月ごとのユーザー作成数と削除数を集計
        [$createCountsByMonth, $deleteCountsByMonth] = $this->aggregateUserCountsByMonth($monthLabels);
        docs('データベースから作成と削除の履歴を取り出し、月ごとの数を数える');

        // グラフ描画用のデータ系列（棒グラフ×2、折れ線グラフ×1）を生成
        $graphSeriesData = $this->generateGraphSeriesData($monthLabels, $currentUserCount, $createCountsByMonth, $deleteCountsByMonth);
        docs('月ごとの数を棒と線のグラフに使いやすい形に並べ替える');

        // グラフデータを配列の逆順にしてJSON形式でビューに渡す
        $this->val['graphLabels'] = json_encode(array_reverse($monthLabels));
        $this->val['graphDataBar1'] = json_encode(array_reverse($graphSeriesData['bar1']));
        $this->val['graphDataBar2'] = json_encode(array_reverse($graphSeriesData['bar2']));
        $this->val['graphDataLine1'] = json_encode(array_reverse($graphSeriesData['line1']));
        docs('並べ替えた数値を文字列に変えて、画面のグラフ部品へ渡せる形にする');

        // Y軸の最大値を適切なスケールに調整してビューに渡す
        $this->val['graphYaxisMax'] = self::calculateYaxisMax($graphSeriesData['yaxisMax']);
        $this->val['graphY2axisMax'] = self::calculateYaxisMax($graphSeriesData['y2axisMax']);
        docs('最大値から縦軸の上限を決めて、数の変化が見やすいように整える');
    }

    /**
     * グラフX軸用の月ラベルを生成（現在月から過去N-1ヶ月分、新しい順）
     *
     * @return array<int, string>
     */
    private function generateMonthLabels(): array
    {
        $monthLabels = [];
        for ($monthsAgo = 0; $monthsAgo < $this->adminTopUserMonth; $monthsAgo++) {
            $monthLabels[] = now()->startOfMonth()->subMonths($monthsAgo)->format('Y-m');
        }

        return $monthLabels;
    }

    /**
     * 月ごとのユーザー作成数と削除数を集計
     *
     * @param  array<int, string>  $monthLabels
     * @return array{0: array<string, int>, 1: array<string, int>}
     */
    private function aggregateUserCountsByMonth(array $monthLabels): array
    {
        $createCountsByMonth = [];
        $deleteCountsByMonth = [];

        // 集計対象期間の最古の月を基準にデータを取得
        $oldestMonthStart = end($monthLabels).'-01 00:00:00';
        $userRecords = User::where(
            'created_at', '>=', $oldestMonthStart
        )->orWhere(
            'deleted_at', '>=', $oldestMonthStart
        )->withTrashed()->select('created_at', 'deleted_at')->get();

        // 各ユーザーレコードの作成日・削除日を月単位で集計
        // 配列化するとUTCの文字列になり月がずれるため、アプリのタイムゾーンのまま月を取り出す
        foreach ($userRecords as $record) {
            if ($record->created_at !== null) {
                $createdMonth = $record->created_at->format('Y-m');
                $createCountsByMonth[$createdMonth] = ($createCountsByMonth[$createdMonth] ?? 0) + 1;
            }

            if ($record->deleted_at !== null) {
                $deletedMonth = $record->deleted_at->format('Y-m');
                $deleteCountsByMonth[$deletedMonth] = ($deleteCountsByMonth[$deletedMonth] ?? 0) + 1;
            }
        }

        return [$createCountsByMonth, $deleteCountsByMonth];
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
            $createCountAtMonth = $createCountsByMonth[$monthLabel] ?? 0;
            $yaxisMaxValue < $createCountAtMonth && $yaxisMaxValue = $createCountAtMonth;
            $bar1SeriesData[] = $createCountAtMonth;
            $userCountAtMonth -= $createCountAtMonth;

            // 棒グラフ2用：その月の削除数
            $deleteCountAtMonth = $deleteCountsByMonth[$monthLabel] ?? 0;
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
