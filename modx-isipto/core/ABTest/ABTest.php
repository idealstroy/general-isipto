<?php

/* README

Все тесты должны быть перечислены в файле tests.php, который должен находиться в той же папке, что и данная библиотека.

Пример файла tests.php:
=======================
<?php

return
array(
    array(
        'id'        => 'ab_form_color',
        'variants'  => array(
            array( 'id' => 'dark'),
            array( 'id' => 'blue'),
        ),
    ),
    array(
        'id'        => 'ab_main_title',
        'variants'  => array(
            array( 'id' => 'small'),
            array( 'id' => 'big'),
        ),
    ),
);

=======================

Вначале скрипта, отдающего страницу, пишем
require_once __DIR__ . '/path/to/ABTest.php';
Если поместить этот код не в начало, то возможны ошибки, в случае если выше будет находиться какой-либо php код, выводящий что-либо на страницу


=> Вариант использования 1. Оодностраничный сайт.

Когда нужно определить, какой вариант отдавать пользователю, пишем
if (ABTest::instance()->getTestValue('ab_campaign_name1') === 'variant_1') {
    $title = "Старый заголовок формы";
} else {
    $title = "Новый заголовок формы";
}


=> Вариант использования 2. Когда сайт многостраничный и разные тесты нужно активировать на разных страницах.

Вначале скрипта до подключения библиотеки устанавливаем ручной режим
define('ROISTAT_AB_AUTO_SUBMIT', false);
В tests.php у тестов, которые присутствуют только на некоторых страницах, устанавливаем поле 'active' => false,

Далее активируем нужные тесты вручную
ABTest::instance()->activateTest('ab_campaign_name2');

Теперь тестом можно пользоваться как и в первом случае.

После всех использований надо обязательно вызвать ABTest::instance()->submit();

=> Возможные ошибки

Warning: Cannot modify header information - headers already sent by (output started at /var/www/.../index.php:123)
Такая ошибка означает, что в index.php на 123 строке у вас уже используется библиотека, работающая с cookie или другими заголовками.
перенести использование библиотеки ABTest выше по коду, или наоборот использование из index.php:123 перенесите вниз.

*/

if (!defined('ROISTAT_AB_AUTO_SUBMIT')) {
    define('ROISTAT_AB_AUTO_SUBMIT', true);
}
// @codingStandardsIgnoreStart
class ABTest {
// @codingStandardsIgnoreEnd

    const ROISTAT_AB_COOKIE = "roistat_ab";
    const ROISTAT_AB_SUBMITTED_COOKIE = "roistat_ab_submit";

    /**
     * @var array
     */
    private $_tests;

    /**
     * @var array
     */
    private $_testsCalculatedValues;

    /**
     * @var bool
     */
    private $_isSubmitted;

    private function __construct() {
        $this->_tests = require __DIR__ . "/tests.php";
        if (!is_array($this->_tests)) {
            throw new Exception("Invalid data in tests.php. Expected array. " . var_export($this->_tests, true) . " given");
        }
        $this->_testsCalculatedValues = array();
        $this->_initIsSubmitted();
        if ($this->_userIsSeoBot() || count($this->_tests) === 0) {
            return;
        }
        foreach ($this->_tests as $testData) {
            if (!is_array($testData)) {
                throw new Exception("Test data '" . var_export($testData, true) . "' is not array");
            }
            $this->_initTest($testData);
        }
        if (ROISTAT_AB_AUTO_SUBMIT) {
            $this->submit();
        }
    }

    /**
     * @return self
     */
    public static function instance() {
        static $instance;
        if (is_null($instance)) {
            $instance = new self();
        }
        return $instance;
    }

    public function submit() {
        if ($this->_isSubmitted()) {
            return;
        }
        $this->_setIsSubmitted();
        $this->_setRoistatCookie();
    }

    /**
     * @param string $testId
     */
    public function activateTest($testId) {
        foreach ($this->_tests as $index => $testData) {
            if ($testData['id'] !== $testId) {
                continue;
            }
            $this->_tests[$index]['active'] = true;
        }
    }

    /**
     * @param string $testId
     * @return string
     */
    public function getTestValue($testId) {
        $result = null;
        if (array_key_exists($testId, $this->_testsCalculatedValues)) {
            $result = $this->_testsCalculatedValues[$testId];
        } else {
            $result = $this->_getCookie($testId);
        }
        return $result;
    }

    /**
     * @param array $testData
     */
    private function _initTest(array $testData) {
        $testCookie = $this->_getCookie($testData['id']);
        if (!is_null($testCookie) && $this->_validTestCookie($testCookie, $testData)) {
            return;
        }
        $userDefinedMethod = array_key_exists('method', $testData) && $testData['method'];
        if ($userDefinedMethod) {
            $methodName = $testData['method'];
            $testValue = $this->$methodName($testData);
        } else {
            $testValue = $this->_calcRandomVariant($testData);
        }
        $this->_setValue($testData['id'], $testValue);
    }

    /**
     * @param array $testData
     * @return string
     */
    private function _calcRandomVariant(array $testData) {
        $variantsIds = array();
        foreach ($testData['variants'] as $variantData) {
            $variantsIds[] = $variantData['id'];
        }
        $variantIndex = rand(0, count($variantsIds) - 1);
        return $variantsIds[$variantIndex];
    }

    /**
     * @param string $campaignId
     * @param string $variantId
     */
    private function _setValue($campaignId, $variantId) {
        $this->_testsCalculatedValues[$campaignId] = $variantId;
        $this->_setCookie($campaignId, $variantId);
    }

    /**
     * @param string $value
     * @param array $testData
     * @return bool
     */
    private function _validTestCookie($value, array $testData) {
        $result = false;
        foreach ($testData['variants'] as $variantData) {
            if ($value === $variantData['id']) {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * @return bool
     */
    private function _userIsSeoBot() {
        if (!array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
            return false;
        }
        $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
        return preg_match("#bot|crawl|slurp|spider#", $userAgent);
    }

    private function _setRoistatCookie() {
        $activeTests = $this->_getActiveTests();
        if (count($activeTests) === 0) {
            return;
        }
        $cookies = array();
        foreach ($activeTests as $testData) {
            $testId = $testData['id'];
            $testValue = $this->getTestValue($testId);
            $cookies[] = "{$testId}:{$testValue}";
        }
        $this->_setCookie(self::ROISTAT_AB_COOKIE, implode(',', $cookies));
    }

    /**
     * @return array
     */
    private function _getActiveTests() {
        $result = array();
        foreach ($this->_tests as $testData) {
            if (!array_key_exists('active', $testData) || $testData['active']) {
                $result[] = $testData;
            }
        }
        return $result;
    }

    /**
     * @param string $cookie
     * @return string
     */
    private function _getCookie($cookie) {
        return array_key_exists($cookie, $_COOKIE) ? $_COOKIE[$cookie] : null;
    }

    /**
     * @param string $cookie
     * @param string $value
     */
    private function _setCookie($cookie, $value) {
        setcookie($cookie, $value, time() + 3110400, '/');
    }

    /**
     * @return bool
     */
    private function _isSubmitted() {
        return $this->_isSubmitted;
    }

    private function _setIsSubmitted() {
        $this->_isSubmitted = true;
        if (ROISTAT_AB_AUTO_SUBMIT) {
            $this->_setCookie(self::ROISTAT_AB_SUBMITTED_COOKIE, 1);
        }
    }

    private function _initIsSubmitted() {
        if (ROISTAT_AB_AUTO_SUBMIT) {
            $this->_isSubmitted = intval($this->_getCookie(self::ROISTAT_AB_SUBMITTED_COOKIE)) === 1;
        } else {
            $this->_isSubmitted = false;
        }
    }
}

ABTest::instance();
