<?php
require 'vendor/autoload.php';
#namespace AliceDialogsFishPredict;

use PHPUnit\Framework\TestCase;
use AliceDialogs\ServiceLocator;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use AliceDialogsFishPredict\AliceDialogsFishPredict;
use AliceDialogsFishPredict\DayTime;
use AliceDialogsFishPredict\PredictionPeriod;
use AliceDialogsFishPredict\PredictionDay;
use AliceDialogsFishPredict\PredictionFish;
use const AliceDialogsFishPredict\SECONDS_IN_DAY;
use const AliceDialogsFishPredict\SECONDS_IN_PERIOD;


$logger = new Logger('test-fish-predict');
#$logger->pushHandler(new StreamHandler('logs/test-predication.log', Logger::WARNING));
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

class MockDataLoader
{

    public static $pred = [20,40,50,30,40,60,70,80,60,40,50,80,60,45,67,40,40,34,68.90,32,35,76,56,67];
    public static $data = null;

    private function prepare_data()
    {
        if(self::$data)
            return self::$data;

        $today_start = strtotime("today", time());
        for($i=0;$i<12;$i++)
        {
            $timestamp = $today_start + SECONDS_IN_PERIOD * $i;
            self::$data[] = [
                'timestamp' => $timestamp,
                'predator' => self::$pred[$i*2],
                'friedfish' => self::$pred[$i*2+1]
            ];
        }
        return self::$data;
    }

    public function load($location,$params){
        global $logger;
        $logger->debug("[MOCKLOADER]Load $location", $params);
        list($period_start,$period_end) = $params;
        $data = array_filter($this->prepare_data(),function($e) use($period_start,$period_end){
            return ($e['timestamp'] >= $period_start) && ($e['timestamp'] < $period_end);
        });
        return $data;
    }
}


$mockDataLoader = new MockDataLoader();

ServiceLocator::set('logger',$logger);
ServiceLocator::set('dataloader',$mockDataLoader);


$processor = new AliceDialogsFishPredict();

final class TestFishpredict extends TestCase
{

    public function testParseIntent()
    {   

        global $logger, $processor;

        $intent = [
            "what" =>  ["type" => "FishType", "value" => "any"],
            "daytime"=> ["type" => "DayPeriod","value" => "any"],
            "when" => ["type" => "YANDEX.STRING", "value" => "any"]
        ];

        $logger->info("testParseIntent : start default test ");
        $data = $processor->parse_fish_predict_intent($intent);
        $this->assertSame($data['what'], 'any','Wrong default what');
        $this->assertSame($data['when'], 'today','Wrong default when');
        $this->assertSame($data['daytime'], 'any','Wrong default what');

        $logger->info("testParseIntent : start today test ");
        $intent['when']['value'] = 'today';
        $data = $processor->parse_fish_predict_intent($intent);
        $this->assertSame($data['what'], 'any','Wrong today what');
        $this->assertSame($data['when'], 'today','Wrong today when');
        $this->assertSame($data['daytime'], 'any','Wrong today what');

        $logger->info("testParseIntent : start today night ");
        $intent['when']['value'] = 'today';
        $intent['daytime']['value'] = 'night';
        $data = $processor->parse_fish_predict_intent($intent);
        $this->assertSame($data['what'], 'any','Wrong today night what');
        $this->assertSame($data['when'], 'today','Wrong today night when');
        $this->assertSame($data['daytime'], 'night','Wrong today night what');

        $logger->info("testParseIntent : start today morning ");
        $intent['when']['value'] = 'today';
        $intent['daytime']['value'] = 'morning';
        $data = $processor->parse_fish_predict_intent($intent);
        $this->assertSame($data['what'], 'any','Wrong today morning what');
        $this->assertSame($data['when'], 'today','Wrong today morning when');
        $this->assertSame($data['daytime'], 'morning','Wrong today morning what');

        $logger->info("testParseIntent : start today day ");
        $intent['when']['value'] = 'today';
        $intent['daytime']['value'] = 'day';
        $data = $processor->parse_fish_predict_intent($intent);
        $this->assertSame($data['what'], 'any','Wrong today day what');
        $this->assertSame($data['when'], 'today','Wrong today day when');
        $this->assertSame($data['daytime'], 'day','Wrong today day what');

        $logger->info("testParseIntent : start today evening ");
        $intent['when']['value'] = 'today';
        $intent['daytime']['value'] = 'evening';
        $data = $processor->parse_fish_predict_intent($intent);
        $this->assertSame($data['what'], 'any','Wrong today evening what');
        $this->assertSame($data['when'], 'today','Wrong today evening when');
        $this->assertSame($data['daytime'], 'evening','Wrong today evening what');


        $logger->info("testParseIntent : start tomorrow evening ");
        $intent['when']['value'] = 'завтра';
        $intent['daytime']['value'] = 'evening';
        $data = $processor->parse_fish_predict_intent($intent);
        $this->assertSame($data['what'], 'any','Wrong tomorrow evening what');
        $this->assertSame($data['when'], 'tomorrow','Wrong tomorrow evening when');
        $this->assertSame($data['daytime'], 'evening','Wrong tomorrow evening what');

    }


    public function testDateTimeToPeriod()
    {   

        global $logger;

        $logger->info("testDateTime : timestamp_to_period ");
        /* Periods is in hours 0-6 -night , 6-12 - morning ,12-18 - day, 18-00 - evening */
        $today_start = strtotime("today", time());
        $seconds_per_hour = 3600;

        // From 00:00 to 06:00 is a night
        $period = DayTime::timestamp_to_period($today_start + $seconds_per_hour);
        $this->assertSame($period, PredictionDay::TODAY | PredictionPeriod::NIGHT ,'Wrong period value today night');

        // From 06:00 to 12:00 is a morning
        $period = DayTime::timestamp_to_period($today_start + ($seconds_per_hour * 7));
        $this->assertSame($period, PredictionDay::TODAY | PredictionPeriod::MORNING ,'Wrong period value today morning');

        // From 12:00 to 18:00 is a day
        $period = DayTime::timestamp_to_period($today_start + ($seconds_per_hour * 13));
        $this->assertSame($period, PredictionDay::TODAY | PredictionPeriod::DAY ,'Wrong period value today day');

       // From 18:00 to 24:00 is a evening        
        $period = DayTime::timestamp_to_period($today_start + ($seconds_per_hour * 19));
        $this->assertSame($period, PredictionDay::TODAY | PredictionPeriod::EVENING ,'Wrong period value today evening');

       // Next day        
       $period = DayTime::timestamp_to_period($today_start + ($seconds_per_hour * 25));
       $this->assertSame($period, PredictionDay::TOMORROW | PredictionPeriod::NIGHT ,'Wrong period value tomorrow night');

    }


    public function testDateTimeToTimeStamp()
    {
        $today_start = strtotime("today", time());
        list($period_start,$period_end) = DayTime::period_to_timestamp(PredictionDay::TODAY|PredictionPeriod::ANY);
        $this->assertSame($period_start, $today_start, 'Wrong timestamp today start');
        $this->assertSame($period_end, $today_start + SECONDS_IN_DAY, 'Wrong timestamp today end');

        list($period_start,$period_end) = DayTime::period_to_timestamp(PredictionDay::TODAY|PredictionPeriod::MORNING);
        $this->assertSame($period_start, $today_start + SECONDS_IN_PERIOD, 'Wrong timestamp today morning start');
        $this->assertSame($period_end, $today_start + SECONDS_IN_PERIOD * 2, 'Wrong timestamp today morning end');

        list($period_start,$period_end) = DayTime::period_to_timestamp(PredictionDay::TOMORROW|PredictionPeriod::ANY);
        $this->assertSame($period_start, $today_start + SECONDS_IN_DAY, 'Wrong timestamp tomorrow start');
        $this->assertSame($period_end, $today_start + SECONDS_IN_DAY * 2, 'Wrong timestamp tomorrow end');
    }

    public function testBuildFishPredict()
    {   
        $processor = new AliceDialogsFishPredict();

        # Find best for today and best fish
        $data = $processor->build_fish_predict([
            'where' => 'netanya',
            'code' => PredictionDay::TODAY
        ]);
        
        $this->assertSame($data[0]['predict'],80);
        $this->assertSame($data[0]['fish'],PredictionFish::WHITE);
        $this->assertSame($data[0]['period'],PredictionDay::TODAY|PredictionPeriod::EVENING);

        $this->assertSame($data[1]['predict'],70);
        $this->assertSame($data[1]['fish'],PredictionFish::PREDATOR);
        $this->assertSame($data[1]['period'],PredictionDay::TODAY|PredictionPeriod::EVENING);


        # Find best for today and fish defined
        $data = $processor->build_fish_predict([
            'where' => 'netanya',
            'code' => PredictionDay::TODAY | PredictionFish::PREDATOR
        ]);
        
        $this->assertSame($data[0]['predict'],70);
        $this->assertSame($data[0]['fish'],PredictionFish::PREDATOR);
        $this->assertSame($data[0]['period'],PredictionDay::TODAY|PredictionPeriod::EVENING);

        $this->assertSame($data[1]['predict'],80);
        $this->assertSame($data[1]['fish'],PredictionFish::WHITE);
        $this->assertSame($data[1]['period'],PredictionDay::TODAY|PredictionPeriod::EVENING);


        # Find best for tomorrow night
        $data = $processor->build_fish_predict([
            'where' => 'netanya',
            'code' => PredictionDay::TOMORROW | PredictionPeriod::EVENING
        ]);
        
        $this->assertSame($data[0]['predict'],67);
        $this->assertSame($data[0]['fish'],PredictionFish::PREDATOR);
        $this->assertSame($data[0]['period'],PredictionDay::TOMORROW|PredictionPeriod::EVENING);

        $this->assertSame($data[1]['predict'],40);
        $this->assertSame($data[1]['fish'],PredictionFish::WHITE);
        $this->assertSame($data[1]['period'],PredictionDay::TOMORROW|PredictionPeriod::EVENING);

    }
}
