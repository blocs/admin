<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\User;

class HomeController extends Controller
{
    private $val = [];
    private $adminTopUserMonth = 5;

    public function __construct()
    {
        define('VIEW_PREFIX', 'admin');
        define('ROUTE_PREFIX', 'home');

        $this->viewPrefix = VIEW_PREFIX.'.'.ROUTE_PREFIX;
    }

    public function index()
    {
        $this->val = array_merge($this->val, \Blocs\Notice::get());
        $this->val = array_merge($this->val, $this->chart());

        list($navigation, $headline, $breadcrumb) = \Blocs\Navigation::get(VIEW_PREFIX);
        $this->val['navigation'] = $navigation;
        $this->val['headline'] = $headline;
        $this->val['breadcrumb'] = $breadcrumb;

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

        $each1 = [];
        $each2 = [];
        $accumulate = [];
        $yaxisMax = 0;
        $y2axisMax = 0;

        foreach ($xaxis as $xlabel) {
            array_unshift($accumulate, [$xlabel, $current]);
            $y2axisMax < $current && $y2axisMax = $current;

            $each = isset($insertList[$xlabel]) ? $insertList[$xlabel] : 0;
            array_unshift($each1, [$xlabel, $each]);
            $current -= $each;
            $yaxisMax < $each && $yaxisMax = $each;

            $each = isset($deleteList[$xlabel]) ? $deleteList[$xlabel] : 0;
            array_unshift($each2, [$xlabel, $each]);
            $current += $each;
            $yaxisMax < $each && $yaxisMax = $each;
        }

        $param['json'] = json_encode([$each1, $each2, $accumulate]);
        $param['update'] = date(DATE_ATOM);

        $yaxisMax = self::getYaxisMax($yaxisMax);
        $y2axisMax = self::getYaxisMax($y2axisMax);

        $param['yaxisMax'] = $yaxisMax;
        $param['y2axisMax'] = $y2axisMax;

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
