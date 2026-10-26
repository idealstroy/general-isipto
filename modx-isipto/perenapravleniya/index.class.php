<?php
$dood = $modx->getService('doodles','Doodles',$modx->getOption('doodles.core_path',null,$modx->getOption('core_path').'components/doodles/').'model/doodles/',$scriptProperties);
if (!($dood instanceof Doodles)) return ''; ?>
?>