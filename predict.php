<?php
require __DIR__ . '/vendor/autoload.php';
use AliceDialogsFishPredict\AliceDialogsFishPredict;
use AliceDialogsFishPredict\PredictionPeriod;
use AliceDialogsFishPredict\PredictionDay;
use AliceDialogsFishPredict\PredictionFish;
use AliceDialogsFishPredict\AliceDialogsFishPredictException;
use AliceDialogs\AliceDialogs;
use AliceDialogs\ServiceLocator;
use function AliceDialogsFishPredict\Utils\build_answer_vars;
use function AliceDialogsFishPredict\Utils\build_factor_vars;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('fish-predict');
$logger->pushHandler(new StreamHandler('logs/predication.log', Logger::INFO));
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

ServiceLocator::set('logger',$logger);
$processor = new AliceDialogsFishPredict();

const MESSAGE_BEST_FIRST = "MESSAGE_BEST_FIRST";
const MESSAGE_BEST_SECOND = "MESSAGE_BEST_SECOND";
const MESSAGE_FORECAST_FIRST = "MESSAGE_FORECAST_FIRST";
const MESSAGE_FORECAST_SECOND = "MESSAGE_FORECAST_SECOND";
const QUESTION_WANT_KNOW_CONDITIONS = "QUESTION_WANT_KNOW_CONDITIONS";
const QUESTION_WANT_KNOW_BEST_PERIOD = "UESTION_WANT_KNOW_BEST_PERIOD";
const MESSAGE_EMPTY = "MESSAGE_EMPTY";
const MESSAGE_ERROR_GENERAL = "MESSAGE_ERROR_GENERAL";
const MESSAGE_ERROR_REQUEST = "MESSAGE_ERROR_REQUEST";
const MESSAGE_PREDICT_ERROR_PREDICT_NOT_AVALIABLE = "MESSAGE_PREDICT_ERROR_PREDICT_NOT_AVALIABLE";
const MESSAGE_PREDICT_ERROR_FACTORS_NOT_AVALIABLE = "MESSAGE_PREDICT_ERROR_FACTORS_NOT_AVALIABLE";
const FACTOR_TEMPLATE_ALL = "FACTOR_TEMPLATE_ALL";
const FACTOR_TEMPLATE_FISH = "FACTOR_TEMPLATE_FISH";


$messages_ru = [
    MESSAGE_EMPTY => "Я могу рассказать прогноз клёва на ближайшие три дня для хищной или белой рыбы. Рассказать прогноз на сегодня ?",
    MESSAGE_ERROR_REQUEST => "Я не смогла разобрать запрос. Ещё раз !", 
    MESSAGE_ERROR_GENERAL => "Что-то пошло не так. Я уже отправила репорт разработчику. Попробуйте позже !", 
    MESSAGE_BEST_FIRST => "Лучшее время для ловли {best_fish_1} {best_period_1}.Прогноз клёва {best_predict_1} процентов.",
    MESSAGE_BEST_SECOND => "Для {best_fish_2} лучшее время {best_period_2}. Прогноз {best_predict_2} процентов.",
    MESSAGE_FORECAST_FIRST => "Прогноз клёва на {best_period_1} для {best_fish_1} {best_predict_1} процентов.", 
    MESSAGE_FORECAST_SECOND => "для  {best_fish_2} {best_predict_2}.",
    QUESTION_WANT_KNOW_CONDITIONS => "Хотите узнать какие факторы будут влиять на клёв {best_period_1} ?",
    QUESTION_WANT_KNOW_BEST_PERIOD => "Хотите узнать когда лучшее время для ловли {best_fish_2} {best_day_1} ?",
    MESSAGE_PREDICT_ERROR_PREDICT_NOT_AVALIABLE => "Прогноз не доступен для вашего региона. Попробуйте позже.",
    MESSAGE_PREDICT_ERROR_FACTORS_NOT_AVALIABLE => "Я пока не знаю факторов влияющих на клёв. Попробуйте позже.",
    FACTOR_TEMPLATE_ALL => "{best_period_1} для {best_fish_1} {good_factors_1}, {bad_factors_1}. Для {best_fish_2} {good_factors_2}, {bad_factors_2}.",
    FACTOR_TEMPLATE_FISH => "{best_period_1} для {best_fish_1} {good_factors_1}, {bad_factors_1}.",
];

const MESSAGE_EXACT_FISH_BEST_PERIOD = [MESSAGE_BEST_FIRST, QUESTION_WANT_KNOW_CONDITIONS ];
const MESSAGE_ANY_FISH_BEST_PERIOD = [ [MESSAGE_BEST_FIRST, MESSAGE_BEST_SECOND], QUESTION_WANT_KNOW_CONDITIONS ];
const MESSAGE_EXACT_FISH_EXACT_PERIOD = [MESSAGE_FORECAST_FIRST, QUESTION_WANT_KNOW_CONDITIONS ];
const MESSAGE_ANY_FISH_EXACT_PERIOD = [ [MESSAGE_FORECAST_FIRST, MESSAGE_FORECAST_SECOND], QUESTION_WANT_KNOW_CONDITIONS ];
const MESSAGE_EXACT_FISH_EXACT_DAY = [MESSAGE_BEST_FIRST, QUESTION_WANT_KNOW_BEST_PERIOD ];
const MESSAGE_ANY_FISH_EXACT_DAY = [ [MESSAGE_BEST_FIRST, MESSAGE_BEST_SECOND], QUESTION_WANT_KNOW_CONDITIONS ];

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

$PERDICT_ERRORS = [
    AliceDialogsFishPredictException::PREDICT_NOT_AVALIABLE => MESSAGE_PREDICT_ERROR_PREDICT_NOT_AVALIABLE,
    AliceDialogsFishPredictException::FACTORS_NOT_AVALIABLE => MESSAGE_PREDICT_ERROR_FACTORS_NOT_AVALIABLE,
    AliceDialogsFishPredictException::GENERAL_ERROR => MESSAGE_ERROR_GENERAL,
];


function get_message_template(&$intent,$with_question=false)
{
    global $DIALOGS, $logger, $messages_ru;
    
    $phrases =  $DIALOGS[$intent['code']];
    $logger->debug("Messages : ",$phrases);
    $message = $phrases['message'][0];
    $question = $phrases['message'][1];
    $template = is_array($message) ? 
            implode(' ',array_map(function ($m) use($messages_ru) {return $messages_ru[$m];},$message)) 
            : $messages_ru[$message];    

    if($with_question){ 
        $template .= $messages_ru[$question];
        $intent['cmd'] = $question == QUESTION_WANT_KNOW_CONDITIONS ? 'factor':'predict';
        $logger->debug("Change intent command : ",$intent);
    }

    $logger->debug("Template : ",[$template]);
    return $template;
}


// takes raw data from the request

if(getenv('DEBUG') == 1)
    $json = file_get_contents('test_request.json');
else
    $json = file_get_contents('php://input');

$request = AliceDialogs::parse($json);




$intent =  $request->intenet(['fish_predict']);
$session = false;
$answer = $messages_ru[MESSAGE_ERROR_REQUEST];


try {

    switch($intent){
        
        case null:
            $answer = $messages_ru[MESSAGE_ERROR_REQUEST];
            break;

        case  AliceDialogs::EMPTY:
            $session = ['when' => 'today'];
            $answer = $messages_ru[MESSAGE_EMPTY];    
            break;

        case  AliceDialogs::REJECT:
            $answer = "Рада помочь. Обращайтесь ещё";
            break;

        case AliceDialogs::CONFIRM:
            $intent = $processor->session_to_intent($request->session());
            if($intent['cmd']=='factor'){
                $factor_template = $intent['code'] & 0x3 == 0 ? FACTOR_TEMPLATE_ALL  : FACTOR_TEMPLATE_FISH;
                $factors = $processor->build_fish_predict_factors($intent);
                $vars = build_factor_vars($processor->build_fish_predict_factors($intent),$factor_template);
                $answer = strtr($factor_template,$vars);
                break;
            }
            // Otherwise command == predict, continue to default processor
            $with_question = false;
        default:
            $session = $processor->parse_fish_predict_intent($intent);
            $template = get_message_template($session,isset($with_question) ? $with_question : true);
            $session['where'] = 'netanya';
            $vars = build_answer_vars($processor->build_fish_predict($session), $template );
            $answer = strtr($template,$vars);
    }

}catch(AliceDialogsFishPredictException $exp){
    $logger->error($exp->getMessage());
    $logger->error("Code : " . $exp->getCode());
    $answer =  $messages_ru[$PERDICT_ERRORS[$exp->getCode()]];
}catch(Exception $exp){
    $logger->error($exp->getMessage());
    $answer =  $messages_ru[MESSAGE_ERROR_GENERAL];
}

header("Content-type: application/json; charset=utf-8");
$response = $request->build_response($answer,$session);
print(json_encode($response,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
