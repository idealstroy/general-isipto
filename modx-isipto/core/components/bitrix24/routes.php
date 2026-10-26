<?php
/** Server-selected routes only. Never accept a category/owner/profile from submitted fields. */
return array(
    'pto' => require __DIR__ . '/routing.php',
    'bim' => array('categoryId'=>99, 'stageId'=>'C99:NEW', 'assignedById'=>715, 'title'=>'Обучение BIM моделированию'),
    'training' => array('categoryId'=>99, 'stageId'=>'C99:NEW', 'assignedById'=>193, 'title'=>'Обучение'),
    'quiz' => array('categoryId'=>31, 'stageId'=>'C31:NEW', 'assignedById'=>25, 'title'=>'Квиз'),
);
