<?php

class SupportFunctions{
    public function getTime() {  
        $ndate = date("Y-m-d");
        $ntime = date("H:i:s");
        $timestamp =date("YmdHis");
        $dt = $ndate.' '.$ntime;
         return array(
             'date'=>$ndate,
             'time'=>$ntime,
             'ts'=>strtotime($dt),
             'timestamp'=>$timestamp,
         );  
    }
}