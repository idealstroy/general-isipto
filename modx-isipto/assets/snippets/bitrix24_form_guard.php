<?php
if (!isset($modx) || !defined('MODX_CORE_PATH')) return '';
if (empty($_SESSION['bitrix24_form_csrf'])) $_SESSION['bitrix24_form_csrf'] = bin2hex(random_bytes(32));
return '<input type="hidden" name="bitrix24_csrf" value="' . htmlspecialchars($_SESSION['bitrix24_form_csrf'], ENT_QUOTES, 'UTF-8') . '">';