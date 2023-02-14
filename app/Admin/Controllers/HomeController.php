<?php

namespace App\Admin\Controllers;

use App\Models\Admin\User;

class HomeController extends \Blocs\Controllers\Base
{
    private $adminTopUserMonth = 5;

    public function __construct()
    {
        define('BLOCS_AUTOINCLUDE_DIR', 'admin');
        define('ROUTE_PREFIX', 'home');

        $this->viewPrefix = ADMIN_VIEW_PREFIX.'.'.ROUTE_PREFIX;
    }

    public function index()
    {
        $this->val = array_merge($this->val, $this->chart());

        $this->setupMenu();

        return view($this->viewPrefix.'.index', $this->val);
    }

    public function clear()
    {
        \Artisan::call('view:clear');

        return redirect()->route('home');
    }

    protected function chart()
    {
        $param = [];

        $dateAtom = date(DATE_ATOM);
        $xaxis = [substr($dateAtom, 0, 7)];
        list($year, $month) = explode('-', $xaxis[0], 2);
        for ($x = 0; $x < $this->adminTopUserMonth - 1; ++$x) {
            --$month;
            if (!$month) {
                $month = 12;
                --$year;
            }
            $xaxis[] = sprintf('%d-%02d', $year, $month);
        }

        $current = User::count();
        $param['current'] = $current;
        $param['update'] = date(DATE_ATOM);

        $insertList = [];
        $recordList = User::where(
            'created_at', '>', $xaxis[count($xaxis) - 1].'-00'
        )->where(
            'created_at', '<', $xaxis[0].'-32'
        )->withTrashed()->get()->toArray();

        foreach ($recordList as $record) {
            $month = substr($record['created_at'], 0, 7);
            isset($insertList[$month]) || $insertList[$month] = 0;
            ++$insertList[$month];
        }

        $deleteList = [];
        $recordList = User::where(
            'deleted_at', '>', $xaxis[count($xaxis) - 1].'-00'
        )->where(
            'deleted_at', '<', $xaxis[0].'-32'
        )->withTrashed()->get()->toArray();

        foreach ($recordList as $record) {
            $month = substr($record['deleted_at'], 0, 7);
            isset($deleteList[$month]) || $deleteList[$month] = 0;
            ++$deleteList[$month];
        }

        $line1 = [];
        $bar1 = [];
        $bar2 = [];
        $yaxisMax = 0;
        $y2axisMax = 0;

        foreach ($xaxis as $xlabel) {
            $line1[] = $current;
            $y2axisMax < $current && $y2axisMax = $current;

            $each = isset($insertList[$xlabel]) ? $insertList[$xlabel] : 0;
            $yaxisMax < $each && $yaxisMax = $each;

            $bar1[] = $each;
            $current -= $each;

            $each = isset($deleteList[$xlabel]) ? $deleteList[$xlabel] : 0;
            $yaxisMax < $each && $yaxisMax = $each;

            $bar2[] = $each;
            $current += $each;
        }

        $param['graphLabels'] = json_encode(array_reverse($xaxis));
        $param['graphDataBar1'] = json_encode(array_reverse($bar1));
        $param['graphDataBar2'] = json_encode(array_reverse($bar2));
        $param['graphDataLine1'] = json_encode(array_reverse($line1));

        $param['graphYaxisMax'] = self::getYaxisMax($yaxisMax);
        $param['graphY2axisMax'] = self::getYaxisMax($y2axisMax);

        return $param;
    }

    public static function getYaxisMax($yaxisMax)
    {
        $scale = pow(10, strlen(floor($yaxisMax * 1.3)) - 1);
        $yaxisMax = intval(ceil($yaxisMax * 1.3 / $scale) * $scale);
        ($yaxisMax < 10) && $yaxisMax = 10;

        return $yaxisMax;
    }
}
