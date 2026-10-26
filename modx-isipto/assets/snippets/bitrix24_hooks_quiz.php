<?php
if (!isset($modx, $hook)) return false;
return $modx->runSnippet('bitrix24_hooks', array('hook'=>$hook, 'formit'=>isset($formit)?$formit:null, 'bitrix24_route'=>'quiz'));