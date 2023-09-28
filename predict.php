<?php
require __DIR__ . '/vendor/autoload.php';
use AliceDialogsFishPredict\AliceDialogsFishPredict;
use AliceDialogsFishPredict\PredictionPeriod;
use AliceDialogsFishPredict\PredictionDay;
use AliceDialogsFishPredict\PredictionFish;
use AliceDialogs\AliceDialogs;
use AliceDialogs\ServiceLocator;
use function AliceDialogsFishPredict\Utils\build_answer_vars;
use Monolog;


$logger = new Monolog\Logger('fish-predict');
$logger->pushHandler(new Monolog\Handler\StreamHandler('logs/predication.log', Monolog\Logger::WARNING));
$logger->pushHandler(new Monolog\Handler\StreamHandler('php://stdout', Monolog\Logger::DEBUG));

ServiceLocator::set('logger',$logger);
$processor = new AliceDialogsFishPredict();

const MESSAGE_BEST_FIRST = "MESSAGE_BEST_FIRST";
const MESSAGE_BEST_SECOND = "MESSAGE_BEST_SECOND";
const MESSAGE_FORECAST_FIRST = "MESSAGE_FORECAST_FIRST";
const MESSAGE_FORECAST_SECOND = "MESSAGE_FORECAST_SECOND";
const QUESTION_WANT_KNOW_CONDITIONS = "QUESTION_WANT_KNOW_CONDITIONS";
const QUESTION_WANT_KNOW_BEST_PERIOD = "UESTION_WANT_KNOW_BEST_PERIOD";

$messages_ru = [
    MESSAGE_BEST_FIRST => "Лучшее время для ловли {best_fish_1} {best_period_1}.Прогноз клёва {best_predict_1} процентов.",
    MESSAGE_BEST_SECOND => "Для {best_fish_2} лучшее время {best_period_2}. Прогноз {best_predict_2} процентов.",
    MESSAGE_FORECAST_FIRST => "Прогноз клёва на {best_period_1} для {best_fish_1} {best_predict_1} процентов.", 
    MESSAGE_FORECAST_SECOND => "для  {best_fish_2} {best_predict_2}.",
    QUESTION_WANT_KNOW_CONDITIONS => "Хотите узнать какие факторы будут влиять на клёв {best_period_1} ?",
    QUESTION_WANT_KNOW_BEST_PERIOD => "Хотите узнать когда лучшее время для ловли {best_fish_2} {best_day_1} ?",
];



const MESSAGE_EXACT_FISH_BEST_PERIOD = [MESSAGE_BEST_FIRST, QUESTION_WANT_KNOW_CONDITIONS ];
const MESSAGE_ANY_FISH_BEST_PERIOD = [ [MESSAGE_BEST_FIRST, MESSAGE_BEST_SECOND], QUESTION_WANT_KNOW_CONDITIONS ];
const MESSAGE_EXACT_FISH_EXACT_PERIOD = [MESSAGE_BEST_FIRST, QUESTION_WANT_KNOW_CONDITIONS ];
const MESSAGE_ANY_FISH_EXACT_PERIOD = [ [MESSAGE_BEST_FIRST, MESSAGE_BEST_SECOND], QUESTION_WANT_KNOW_CONDITIONS ];
const MESSAGE_EXACT_FISH_EXACT_DAY = [MESSAGE_BEST_FIRST, QUESTION_WANT_KNOW_BEST_PERIOD ];
const MESSAGE_ANY_FISH_EXACT_DAY = [ [MESSAGE_BEST_FIRST, MESSAGE_BEST_SECOND], QUESTION_WANT_KNOW_BEST_PERIOD ];


$DIALOGS = [
    # Best day
    PredictionPeriod::ANY | PredictionDay::ANY | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_BEST_PERIOD],
    PredictionPeriod::ANY | PredictionDay::ANY | PredictionFish::PREDATOR => ['message' => MESSAGE_EXACT_FISH_BEST_PERIOD],
    PredictionPeriod::ANY | PredictionDay::ANY | PredictionFish::WHITE => ['message' => MESSAGE_EXACT_FISH_BEST_PERIOD],

    #Today
    PredictionPeriod::ANY | PredictionDay::TODAY | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_EXACT_DAY],
    PredictionPeriod::NIGHT | PredictionDay::TODAY | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::MORNING | PredictionDay::TODAY | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::DAY | PredictionDay::TODAY | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::EVENING | PredictionDay::TODAY | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::ANY | PredictionDay::TODAY | PredictionFish::PREDATOR => ['message' => MESSAGE_EXACT_FISH_EXACT_DAY],
    PredictionPeriod::NIGHT | PredictionDay::TODAY | PredictionFish::PREDATOR => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::MORNING | PredictionDay::TODAY | PredictionFish::PREDATOR => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::DAY | PredictionDay::TODAY | PredictionFish::PREDATOR => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::EVENING | PredictionDay::TODAY | PredictionFish::PREDATOR => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::ANY | PredictionDay::TODAY | PredictionFish::WHITE => ['message' => MESSAGE_EXACT_FISH_EXACT_DAY],
    PredictionPeriod::NIGHT | PredictionDay::TODAY | PredictionFish::WHITE => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::MORNING | PredictionDay::TODAY | PredictionFish::WHITE => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::DAY | PredictionDay::TODAY | PredictionFish::WHITE => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::EVENING | PredictionDay::TODAY | PredictionFish::WHITE => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
  
    #TOMORROW
    PredictionPeriod::ANY | PredictionDay::TOMORROW | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_EXACT_DAY],
    PredictionPeriod::NIGHT | PredictionDay::TOMORROW | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::MORNING | PredictionDay::TOMORROW | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::DAY | PredictionDay::TOMORROW | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::EVENING | PredictionDay::TOMORROW | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::ANY | PredictionDay::TOMORROW | PredictionFish::PREDATOR => ['message' => MESSAGE_EXACT_FISH_EXACT_DAY],
    PredictionPeriod::NIGHT | PredictionDay::TOMORROW | PredictionFish::PREDATOR => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::MORNING | PredictionDay::TOMORROW | PredictionFish::PREDATOR => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::DAY | PredictionDay::TOMORROW | PredictionFish::PREDATOR => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::EVENING | PredictionDay::TOMORROW | PredictionFish::PREDATOR => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::ANY | PredictionDay::TOMORROW | PredictionFish::WHITE => ['message' => MESSAGE_EXACT_FISH_EXACT_DAY],
    PredictionPeriod::NIGHT | PredictionDay::TOMORROW | PredictionFish::WHITE => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::MORNING | PredictionDay::TOMORROW | PredictionFish::WHITE => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::DAY | PredictionDay::TOMORROW | PredictionFish::WHITE => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::EVENING | PredictionDay::TOMORROW | PredictionFish::WHITE => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],

    #Past TOMORROW
    PredictionPeriod::ANY | PredictionDay::PASTTOMORROW | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_EXACT_DAY],
    PredictionPeriod::NIGHT | PredictionDay::PASTTOMORROW | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::MORNING | PredictionDay::PASTTOMORROW | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::DAY | PredictionDay::PASTTOMORROW | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::EVENING | PredictionDay::PASTTOMORROW | PredictionFish::ANY => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::ANY | PredictionDay::PASTTOMORROW | PredictionFish::PREDATOR => ['message' => MESSAGE_EXACT_FISH_EXACT_DAY],
    PredictionPeriod::NIGHT | PredictionDay::PASTTOMORROW | PredictionFish::PREDATOR => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::MORNING | PredictionDay::PASTTOMORROW | PredictionFish::PREDATOR => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::DAY | PredictionDay::PASTTOMORROW | PredictionFish::PREDATOR => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::EVENING | PredictionDay::PASTTOMORROW | PredictionFish::PREDATOR => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::ANY | PredictionDay::PASTTOMORROW | PredictionFish::WHITE => ['message' => MESSAGE_EXACT_FISH_EXACT_DAY],
    PredictionPeriod::NIGHT | PredictionDay::PASTTOMORROW | PredictionFish::WHITE => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::MORNING | PredictionDay::PASTTOMORROW | PredictionFish::WHITE => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::DAY | PredictionDay::PASTTOMORROW | PredictionFish::WHITE => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
    PredictionPeriod::EVENING | PredictionDay::PASTTOMORROW | PredictionFish::WHITE => ['message' => MESSAGE_ANY_FISH_EXACT_PERIOD],
];

// takes raw data from the request

if(getenv('DEBUG') == 1)
    $json = file_get_contents('test_request.json');
else
    $json = file_get_contents('php://input');

$request = AliceDialogs::parse($json);

$intent =  $request->intenet('fish_predict');
$session = false;
if (is_null($intent))
{
    $answer = "Я не смогла разобрать запрос. Попробуйте позже !";
}else if($intent == AliceDialogs::REJECT)
{
    $answer = "Рада помочь. Обращайтесь ещё";
}else if($intent == AliceDialogs::CONFIRM){
    $prev_session = $request->session();
    if($prev_session['cmd']=='factor'){
        $answer = $processor.build_fish_predict_factors($prev_session);
    }else {
        $answer = $processor.build_fish_predict($prev_session);
    }

}else{
    $session = $processor->parse_fish_predict_intent($intent);
    $phrases =  $DIALOGS[$session['code']];
    $message = $phrases['message'][0];
    $question = $phrases['message'][1];
    $template = is_array($message) ? 
            implode('.',array_map(function ($m) use($messages_ru) {$messages_ru[$m];},$message)) 
            : $messages_ru[$message];
    $template .= $messages_ru[$question];
    $session['cmd'] = $question == QUESTION_WANT_KNOW_CONDITIONS ? 'factor':'predict';
    $session['where'] = 'netanya';
    $vars = build_answer_vars($processor->build_fish_predict($session), $template );
    $answer = strtr($template,$vars);
}

header("Content-type: application/json; charset=utf-8");
$response = $request->build_response($answer,$session);
print(json_encode($response,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));

