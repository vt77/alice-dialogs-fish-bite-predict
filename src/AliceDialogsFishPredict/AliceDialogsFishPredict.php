<?php
namespace AliceDialogsFishPredict;

use AliceDialogs\ServiceLocator;
use AliceDialogsFishPredict\DataLoader;
use Psr\Log\LoggerInterface;

const SECONDS_IN_HOUR  = 3600;
const SECONDS_IN_DAY  = SECONDS_IN_HOUR * 24;
const SECONDS_IN_PERIOD  = 21600; 

class PredictionPeriod{
    const ANY = 0;
    const NIGHT = 0x10000;
    const MORNING = 0x20000;
    const DAY = 0x30000;
    const EVENING = 0x40000;
}

class PredictionDay{
    const ANY = 0;
    const TODAY = 0x100;
    const TOMORROW = 0x200;
    const PASTTOMORROW = 0x300;
}

class PredictionFish{
    const ANY = 0x0;
    const WHITE = 0x1;
    const PREDATOR = 0x2;
}


class AliceDialogsFishPredictException extends \Exception{
    const PREDICT_NOT_AVALIABLE = 1;
    const GENERAL_ERROR = 2;
}

class DayTime
{
    public static function code_to_day($code)
    {
        if( ( $code & 0xF00 ) == PredictionDay::TODAY)
            return 'today';
        if( ( $code & 0xF00)  == PredictionDay::TOMORROW)
            return 'tomorrow';
        if( ( $code & 0xF00 ) == PredictionDay::PASTTOMORROW)
            return 'pasttomorrow';
        return "any";
    }

    public static function day_to_code($name)
    {
        if($name == 'сегодня' || $name == 'today')
            return PredictionDay::TODAY;

        if($name == 'завтра' || $name == 'tomorrow')
            return PredictionDay::TOMORROW;

        if($name == 'послезавтра' || $name == 'pasttomorrow')
            return PredictionDay::PASTTOMORROW;

        return PredictionDay::ANY;
    }

    public static function daytime_to_code($name)
    {
        if($name == 'morning')
            return PredictionPeriod::MORNING;
        if($name == 'day')
            return PredictionPeriod::DAY;
        if($name == 'evening')
            return PredictionPeriod::EVENING;
        if($name == 'night')
            return PredictionPeriod::NIGHT;
        return PredictionPeriod::ANY;
    } 

    /**
     *  Convert timestamp to period in format PredictionDay | PredictionPeriod
     *  
     *  @param int $timestamp timestamp
     *  @param int $now timestamp of today's start
     * 
     *  @return int period in format PredictionDay | PredictionPeriod
     */

    public static function timestamp_to_period($timestamp, $now=null) : int
    {
        $today_start = $now ?? strtotime("today", time());
        $offset = $timestamp - $today_start;
        if($offset < 0)
            throw new AliceDialogsFishPredictException("Period in the past",AliceDialogsFishPredictException::GENERAL_ERROR);

        if($offset == 0)
            return PredictionDay::TODAY | PredictionPeriod::NIGHT;

        $days = intdiv( $offset,  SECONDS_IN_DAY);
        if($days > 2)
            throw new AliceDialogsFishPredictException("Period too far away",AliceDialogsFishPredictException::GENERAL_ERROR);
 
        $period = intdiv(($offset % SECONDS_IN_DAY),SECONDS_IN_PERIOD);
        return (($days+1)<<8)|(($period+1)<<16);
    }

    /**
     *  Returns array of start and end of perid in milliseconds
     *  
     *   @param int $period Period in format PredictionDay | PredictionPeriod
     *   @param int $now timestamp of today's start
     *   @return array start and end of perid in milliseconds
     * 
     */
    public static function period_to_timestamp($period, $now=null) : array{
        $today_start = $now ?? strtotime("today", time());
        $days = $period >> 8 & 0x3;
        $periods = $period >> 16;
        $period_start = $today_start + ($days > 0 ? ( $days-1 )* SECONDS_IN_DAY : 0); 
        $period_end = $period_start + SECONDS_IN_DAY;
        if($periods > 0)
        {
            $period_start += ( ($periods-1) * SECONDS_IN_PERIOD);
            $period_end = $period_start + SECONDS_IN_PERIOD;        
        }
        return [$period_start,$period_end];
    }
}



class AliceDialogsFishPredict{


    /** @var LoggerInterface */
    private $_logger = null ;

    /** @var DataLoggerInterface */
    private $_dataLoader = null;

    function __construct()
    {
        $this->_logger = ServiceLocator::get('logger');
        if(ServiceLocator::has('dataloader'))
            $this->_dataLoader = ServiceLocator::get('dataloader');
        
        if(!$this->_dataLoader)
        # Create default dataloader
            $this->_dataLoader = new DataLoader("dsn=file");            
    }

    public function parse_fish_predict_intent($intent)
    {

        $this->_logger->debug("[PARSEINTENT]",$intent);

        # Defaults
        $vars = [
            'code' => PredictionDay::TODAY,
            "what" => "any",
            "when" => "today",
            "daytime" => "any",
        ];

        $when =  key_exists('when',$intent) ? $intent['when']['value'] : null;
        if($when && $when != 'any'){
            $this->_logger->debug("[PARSEINTENT]When",[$when]);
            $vars['code'] = DayTime::day_to_code($when);
            $vars['when'] = DayTime::code_to_day($vars['code']);
        }

        $what =  key_exists('what',$intent) ? $intent['what']['value'] : null;
        if( $what=='white' || $what=='predator' ){ 
            $vars['what'] = $what;
            $vars['code'] |= ($what=='white') ? PredictionFish::WHITE : PredictionFish::PREDATOR; 
        }

        $daytime =  key_exists('daytime',$intent) ? $intent['daytime']['value'] : null;
        if($daytime){
            $vars["daytime"] = $daytime;
            $vars['code'] |= DayTime::daytime_to_code($daytime);
        }
        
        $this->_logger->info("[PREDICT]Parsed intent",$vars);
        return $vars;
    }

    public function build_fish_predict($intent) : array
    {
        $data = [
            'pred'=>[
                'fish' => PredictionFish::PREDATOR, 
                'predict'=>0,
            ],
            'nonpred'=>[
                'fish' => PredictionFish::WHITE,
                'predict'=>0,
            ]
        ];

        list($period_start,$period_end) = DayTime::period_to_timestamp($intent['code']);
        $this->_logger->debug("Loading dates : ",[gmdate("Y-m-d H:i", $period_start),gmdate("Y-m-d  H:i", $period_end)]);
        $found = false;
        foreach( $this->_dataLoader->load($intent['where'],[$period_start,$period_end]) as $pr )
        {
                if(!key_exists('predator',$pr))
                    continue;

                if( $pr['predator'] > $data['pred']['predict'])
                {
                    $data['pred']['period'] = DayTime::timestamp_to_period($pr['timestamp']);
                    $data['pred']['predict'] = intval($pr['predator']);
                }

                if( $pr['friedfish'] > $data['nonpred']['predict'])
                {
                    $data['nonpred']['period'] = DayTime::timestamp_to_period($pr['timestamp']);
                    $data['nonpred']['predict'] = intval($pr['friedfish']);
                }
                $found = true;
        }

        if(!$found)
            throw new AliceDialogsFishPredictException("Predication not found for selected date",AliceDialogsFishPredictException::PREDICT_NOT_AVALIABLE);

        # Search by fish . Return wanted first
        if ($intent['code'] & PredictionFish::WHITE)
            return [$data['nonpred'],$data['pred']];
        if ($intent['code'] & PredictionFish::PREDATOR)
            return [$data['pred'],$data['nonpred']];

        # otherwise return best first
        if($data['nonpred']['predict'] > $data['pred']['predict'])
            return [$data['nonpred'],$data['pred']];
        return [$data['pred'],$data['nonpred']];
    }
}
