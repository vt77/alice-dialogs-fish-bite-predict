<?php 
namespace AliceDialogsFishPredict;

use Monolog;

const DATAPATH = 'data';

$logger = new Monolog\Logger(__NAMESPACE__);


interface DataLoggerInterface{
    public function load($location, $params);
    public function load_factors($intent);
}


class DataLoader{

    function __construct($dsn)
    {
        global $logger;
        parse_str($dsn,$arr);
        $this->_driver = $arr['dsn'];
        $this->_logger = $logger;
    }

    public function load_from_file($location, $params){
        $filename = sprintf("%s/last-%s.json",DATAPATH,$location);
        $this->_logger->debug("[PREDICT][FILELOADER]Loading file " . $filename);
        $data = json_decode(file_get_contents($filename),true);
        $filterd_data = array_filter($data['forecast'],function ($a) use($params){
                list($start_date,$end_date) = $params;
                return intval($a['timestamp']) >= $start_date && intval($a['timestamp']) < $end_date;
        });
        return $filterd_data;
    }

    public function load($location,$params){
        if($this->_driver == 'file')
            return $this->load_from_file($location,$params);
    }


    public function load_factors()
    {
        $filename = sprintf("%s/../factors.yaml",DATAPATH);
        return yaml_parse_file($filename)['factors'];
    }

};
