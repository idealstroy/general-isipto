<?php
/** Adapter for the existing non-Ajax BIM/quiz forms. Uses native AjaxForm/FormIt internally. */
if (!isset($bitrix24EndpointRoute) || !in_array($bitrix24EndpointRoute, array('bim','quiz'), true)) {http_response_code(404);exit;}
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {http_response_code(405);header('Allow: POST');exit;}
require_once dirname(__DIR__, 3) . '/config.core.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
$modx = new modX();$modx->initialize('web');
$token = isset($_POST['bitrix24_csrf']) && is_string($_POST['bitrix24_csrf']) ? $_POST['bitrix24_csrf'] : '';
if (empty($_SESSION['bitrix24_form_csrf']) || $token === '' || !hash_equals($_SESSION['bitrix24_form_csrf'], $token)) {
    http_response_code(403);header('Content-Type: text/plain; charset=UTF-8');exit('Обновите страницу и повторите отправку формы');
}
foreach ($_POST as $value) if (!is_scalar($value)) {http_response_code(400);exit('Некорректные данные формы');}
$modx->resource = $modx->getObject('modResource', (int)$modx->getOption('site_start'));
$modx->loadClass('ajaxform', MODX_CORE_PATH . 'components/ajaxform/model/ajaxform/', false, true);
$af = new AjaxForm($modx, array('json_response'=>false));$key = 'direct-' . bin2hex(random_bytes(16));
$_SESSION['AjaxForm'][$key] = array('snippet'=>'FormIt','form'=>$bitrix24EndpointRoute==='bim'?'form_bim':'form_quiz',
    'hooks'=>'FormItSaveForm,bitrix24_hooks_' . $bitrix24EndpointRoute,'validate'=>'fio:required,phone:required',
    'validationErrorMessage'=>'Проверьте данные и согласие на обработку персональных данных');
try {$result = $af->process($key, $_POST);} finally {unset($_SESSION['AjaxForm'][$key]);}
if (is_array($result) && !empty($result['success'])) {header('Location: /spasibo/', true, 303);exit;}
http_response_code(422);header('Content-Type: text/plain; charset=UTF-8');
echo 'Не удалось подтвердить доставку заявки. Проверьте данные и галочку согласия. Вернитесь к форме или свяжитесь с нами по телефону';
