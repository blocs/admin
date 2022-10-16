<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\User;

class HomeController extends Controller
{
    private $val = [];

    public function index()
    {
        $this->val = array_merge($this->val, \Blocs\Notice::get());

        return view('admin.home', $this->val);
    }

    public function dashboard()
    {
        defined('ADMIN_TOP_USER_MONTH') || define('ADMIN_TOP_USER_MONTH', 5);
        defined('ADMIN_TOP_MESSAGE_NUM') || define('ADMIN_TOP_MESSAGE_NUM', 10);

        return view('admin.dashboard.user', $this->chart());
    }

    public function clear()
    {
        \Artisan::call('view:clear');

        return redirect()->route('home');
    }

    protected function chart()
    {
        $param = [];

        $date_atom = date(DATE_ATOM);
        $xaxis = [substr($date_atom, 0, 7)];
        list($year, $month) = explode('-', $xaxis[0], 2);
        for ($x = 0; $x < ADMIN_TOP_USER_MONTH - 1; ++$x) {
            --$month;
            if (!$month) {
                $month = 12;
                --$year;
            }
            $xaxis[] = sprintf('%d-%02d', $year, $month);
        }

        $current = User::count();
        $param['current'] = $current;

        $inserts = [];
        $records = User::where(
            'create_at', '>', $xaxis[count($xaxis) - 1].'-00'
        )->where(
            'create_at', '<', $xaxis[0].'-32'
        )->get()->toArray();

        foreach ($records as $record) {
            $month = substr($record['create_at'], 0, 7);
            isset($inserts[$month]) || $inserts[$month] = 0;
            ++$inserts[$month];
        }

        $deletes = [];
        $records = User::where(
            'delete_at', '>', $xaxis[count($xaxis) - 1].'-00'
        )->where(
            'delete_at', '<', $xaxis[0].'-32'
        )->get()->toArray();

        foreach ($records as $record) {
            $month = substr($record['delete_at'], 0, 7);
            isset($deletes[$month]) || $deletes[$month] = 0;
            ++$deletes[$month];
        }

        $each_1 = [];
        $each_2 = [];
        $accumulate = [];
        $yaxis_max = 0;
        $y2axis_max = 0;

        foreach ($xaxis as $xlabel) {
            array_unshift($accumulate, [$xlabel, $current]);
            $y2axis_max < $current && $y2axis_max = $current;

            $each = isset($inserts[$xlabel]) ? $inserts[$xlabel] : 0;
            array_unshift($each_1, [$xlabel, $each]);
            $current -= $each;
            $yaxis_max < $each && $yaxis_max = $each;

            $each = isset($deletes[$xlabel]) ? $deletes[$xlabel] : 0;
            array_unshift($each_2, [$xlabel, $each]);
            $current += $each;
            $yaxis_max < $each && $yaxis_max = $each;
        }

        $param['json'] = json_encode([$each_1, $each_2, $accumulate]);
        $param['update'] = date(DATE_ATOM);

        $yaxis_max = self::get_yaxis_max($yaxis_max);
        $y2axis_max = self::get_yaxis_max($y2axis_max);

        $param['yaxis_max'] = $yaxis_max;
        $param['y2axis_max'] = $y2axis_max;

        return $param;
    }

    public static function get_yaxis_max($yaxis_max)
    {
        $scale = pow(10, strlen(floor($yaxis_max * 1.3)) - 1);
        $yaxis_max = intval(ceil($yaxis_max * 1.3 / $scale) * $scale);
        ($yaxis_max < 10) && $yaxis_max = 10;

        return $yaxis_max;
    }
}
