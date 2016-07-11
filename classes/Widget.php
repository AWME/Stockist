<?php namespace AWME\Stockist\Classes;

use BackendAuth;
use AWME\Stockist\Models\Sale;

class Widget
{

    public function __construct()
    {
        parent::__construct();
        
    }

    public static function getStatuses($by = 'today', $status = 'open')
    {
        $date = 'currentDate'.ucfirst($by);
        $obtainDate = self::$date();

        $count = Sale::where('status', $status)->where('created_at','>', $obtainDate)->count();
        
        return $count;
    }

    public static function getProfit($by = 'today')
    {
        $date = 'currentDate'.ucfirst($by);
        $obtainDate = self::$date();

        $sales = Sale::where('status', 'closed')->where('created_at','>', $obtainDate)->get()->toArray();
        $totals = array_column($sales, 'total');
        $total = array_sum($totals);
        return number_format($total, 2, '.', '');
    }

    /**
     * getTotalSales
     * ===================================
     * Total de ventas
     * @param  string $by [options: today, week, month, year,]
     * @return int
     */
    public static function getTotalSales($by = 'today')
    {   
        $date = 'currentDate'.ucfirst($by);
        $obtainDate = self::$date();

        $count = Sale::where('status', 'closed')->where('created_at','>', $obtainDate)->count();
        return $count;
    }


    public static function currentDateYear()
    {
        $show = date("Y");
        return $show.'-01-01 00:00:00';;
    }

    public static function currentDateMonth()
    {
        $show = date("Y-m");
        return $show.'-01 00:00:00';
    }

    public static function currentDateWeek()
    {
        $show = date("Y-m-d", strtotime('-1 week'));
        return $show.' 00:00:00';
    }
 
    public static function currentDateToday()
    {
        $show = date("Y-m-d");
        return $show.' 00:00:00';
    }

}

?>