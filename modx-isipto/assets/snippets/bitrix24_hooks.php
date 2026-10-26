<?php
/** Native FormIt hook: the forms, AjaxForm and local FormItSaveForm are unchanged. */
if (!defined('MODX_CORE_PATH') || !isset($hook, $modx)) {
    http_response_code(404);
    return false;
}
require_once MODX_CORE_PATH . 'components/bitrix24/client.php';
require_once MODX_CORE_PATH . 'components/bitrix24/submission.php';

foreach (array('website', 'firstname', 'lastname', 'name') as $trap) {
    if (trim((string) $hook->getValue($trap)) !== '') {
        $hook->addError('website', 'Не удалось отправить форму');
        return false;
    }
}
// Preserve the original speed/cookie check. Never disable native reCAPTCHA or FormIt validation.
if (!isset($_COOKIE['anti_bot_cookie']) && time() - (isset($_SESSION['form_time']) ? $_SESSION['form_time'] : 0) < 3) {
    $hook->addError('website', 'Подождите несколько секунд перед отправкой');
    return false;
}
if (!in_array((string) $hook->getValue('agree'), array('on', '1', 'yes'), true)) {
    $hook->addError('agree', 'Подтвердите согласие на обработку персональных данных');
    // AjaxForm only returns errors for submitted field names; phone is always part of these forms.
    $hook->addError('phone', 'Подтвердите согласие на обработку персональных данных');
    return false;
}
$name = trim((string) $hook->getValue('fio'));
$phone = trim((string) $hook->getValue('phone'));
$email = trim((string) $hook->getValue('email'));
$page = trim((string) $hook->getValue('page'));
$reason = trim((string) $hook->getValue('reason'));
// Preserve the existing phone-only callback form; named forms keep their required name validation.
if ($name === '' && isset($formit->config['form']) && $formit->config['form'] === 'form_phone_tpl') {
    $name = 'Обратный звонок';
}
if ($name === '' || $phone === '' || strlen($name) > 512 || strlen($phone) > 64) {
    $hook->addError('phone', 'Укажите имя и телефон');
    return false;
}
$digits = preg_replace('/\D/', '', $phone);
if (strlen($digits) < 10 || strlen($digits) > 15) {
    $hook->addError('phone', 'Проверьте номер телефона');
    return false;
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $hook->addError('email', 'Проверьте адрес электронной почты');
    return false;
}
// Separate deal receipts from the earlier lead integration and suppress double-clicks.
$routing = require MODX_CORE_PATH . 'components/bitrix24/routing.php';
// Route comes only from a trusted snippet adapter, never from POST.
if (isset($bitrix24_route)) {
    $routes = require MODX_CORE_PATH . 'components/bitrix24/routes.php';
    if (!is_string($bitrix24_route) || !isset($routes[$bitrix24_route])) {
        $hook->addError('phone', 'Маршрут заявки не настроен');
        return false;
    }
    $routing = $routes[$bitrix24_route];
}
$details = array();
foreach (array('razdel'=>'Раздел обучения','question1'=>'Разделы работ','question2'=>'Характеристики объекта','question3'=>'Срок сдачи','question4'=>'Место строительства') as $field=>$label) {
    $value = $hook->getValue($field);
    if (is_scalar($value) && trim((string)$value) !== '') $details[] = $label . ': ' . substr(trim((string)$value), 0, 1500);
}
$fingerprint = hash('sha256', implode('|', array('deal', $routing['categoryId'], $routing['assignedById'], $name, $digits, $email, $page, $reason)));
if ($details) $fingerprint = hash('sha256', $fingerprint . '|' . implode("\n", $details));
$previous = isset($_SESSION['bitrix24_delivery'][$fingerprint]) ? $_SESSION['bitrix24_delivery'][$fingerprint] : null;
if ($previous && time() - $previous['time'] < 300) {
    if (isset($previous['id']) && !empty($previous['routingVerified'])) return true;
}
$input = array('name' => $name, 'phone' => $phone, 'email' => $email, 'page' => $page, 'reason' => $reason);
$input['details'] = implode("\n", $details);
$receipt = null;
$client = null;
try {
    $client = IsiptoBitrix24Client::fromEnvironment();
    $session = session_id();
    if ($session === '') throw new RuntimeException('BITRIX_SESSION_MISSING');
    $receipt = new IsiptoBitrix24Receipt(MODX_CORE_PATH . 'cache/bitrix24-delivery', hash('sha256', $session . '|' . $fingerprint));
    $saved = $receipt->read();
    if (isset($saved['id']) && !empty($saved['routingVerified']) && time() - $saved['time'] < 300) {
        $_SESSION['bitrix24_delivery'][$fingerprint] = $saved;
        return true;
    }
    $submission = new IsiptoBitrix24Submission($client, $routing);
    $id = $submission->deliver($input, $receipt);
    $_SESSION['bitrix24_delivery'][$fingerprint] = $receipt->read();
    $modx->log(modX::LOG_LEVEL_INFO, '[bitrix24] Deal accepted id=' . $id . ' category=' . $routing['categoryId'] . ' responsible=' . $routing['assignedById']);
    return true;
} catch (Exception $e) {
    $code = $e->getMessage();
    if (!in_array($code, array('BITRIX_CONFIG_INVALID', 'BITRIX_CONFIG_MISSING', 'BITRIX_CURL_MISSING', 'BITRIX_SESSION_MISSING', 'BITRIX_RECEIPT_UNAVAILABLE', 'BITRIX_TRANSPORT_UNCERTAIN', 'BITRIX_API_REJECTED', 'BITRIX_RESULT_UNCERTAIN', 'BITRIX_ROUTING_UNCONFIGURED', 'BITRIX_ROUTING_MISMATCH'), true)) $code = 'BITRIX_INTERNAL_ERROR';
    if (in_array($code, array('BITRIX_CONFIG_INVALID', 'BITRIX_CONFIG_MISSING', 'BITRIX_CURL_MISSING', 'BITRIX_API_REJECTED'), true)) {
        unset($_SESSION['bitrix24_delivery'][$fingerprint]);
        // The submission keeps confirmed contact/deal IDs when an API phase fails.
    }
    $apiCode = $client ? $client->getLastApiError() : null;
    $modx->log(modX::LOG_LEVEL_ERROR, '[bitrix24] Delivery not confirmed: ' . $code . ($apiCode ? ' (' . $apiCode . ')' : ''));
    $hook->addError('phone', 'Заявка сохранена на сайте, но доставка в CRM не подтверждена. Свяжитесь с нами по телефону');
    return false;
} finally {
    if ($receipt) $receipt->close();
}