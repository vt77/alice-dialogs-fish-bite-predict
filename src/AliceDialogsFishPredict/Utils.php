<?php
namespace AliceDialogsFishPredict\Utils;

use Monolog\Logger;
use AliceDialogsFishPredict\PredictionPeriod;
use AliceDialogsFishPredict\PredictionDay;
use AliceDialogsFishPredict\PredictionFish;

$logger = new Logger(__NAMESPACE__);

function get_translated_value($predict,$key,$index)
{

    $translations = [
        'fish' => [
            PredictionFish::PREDATOR => 'хищника',
            PredictionFish::WHITE => 'белой рыбы'
        ],
        'day' => [
            PredictionDay::TODAY => 'сегодня',
            PredictionDay::TOMORROW => 'завтра',
            PredictionDay::PASTTOMORROW => 'послезавтра',
        ],
        'period' => [
            PredictionPeriod::MORNING => 'утром',
            PredictionPeriod::DAY => 'днём',
            PredictionPeriod::EVENING => 'вечером',
            PredictionPeriod::NIGHT => 'ночью',
        ]
    ];

    if(!key_exists($key,$translations))
        return $predict[$index][$key];

    if($key == 'day')
        return $translations[$key][$predict[$index]['period']&0x300];

    if($key == 'period')
        return $translations['day'][$predict[$index][$key]&0x300] . ' ' . $translations[$key][$predict[$index][$key]&0x70000];

    return $translations[$key][$predict[$index][$key]];
}


function extract_template_vars($template)
{
    preg_match_all('!{([a-z0-9_]+)?}!', $template, $m);
    return $m[1];
}

/**
 *   Parses template and builds variables from predict
 */
function build_answer_vars($predict, $template) : array{

    global $logger;

    $logger->debug("[BUILDVARS]Predict",$predict);
    $data = [];
    foreach(extract_template_vars($template) as $i => $var)
    {
        list($var_type,$key,$index) = explode('_',$var);
        if($var_type != 'best')
        {
            $logger->error("[BUILDVARS] got wrong var type : $var_type");
            continue;
        }
        $data['{'.$var.'}'] = get_translated_value( $predict, $key, intval($index)-1 );
    }
    $logger->debug("[BUILDVARS]Parsed vars",$data);
    return $data;
};


function build_factor_vars($predict, $template)
{
    global $logger;
    $data = [];

    foreach(extract_template_vars($template) as $i => $var)
    {
        list($var_type,$key,$index) = explode('_',$var);
        if(!in_array($var_type,['positive','negative','best']))
        {
            $logger->error("[BUILDFACTORVARS] got wrong var type : $var_type");
            continue;
        }

        $logger->error("[BUILDFACTORVARS] Process var : $key $index");

        if($key == 'factors' )
        {
            $data['{'.$var.'}'] = implode(',', $predict[$index-1]['factors'][$var_type]);
            continue;
        }

        $data['{'.$var.'}'] = get_translated_value( $predict, $key, intval($index)-1 );
    }

    return $data;
}
