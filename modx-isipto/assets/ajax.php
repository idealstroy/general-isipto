<?php
if ($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest' || empty($_REQUEST['action'])) {exit();}

define('MODX_API_MODE', true);
require_once dirname(dirname(__FILE__)).'/index.php';

$output1 = null;

switch ($_REQUEST['action']) {
    case 'getcontent1':
        /* получить ТВ */
        $tv = $modx->getObject('modResource', 1);
        /* получить сырое содержимое ТВ */
        $team = $tv->getTVvalue('our_team');
        $rows = json_decode($team, true);
        foreach ($rows as $i=>$row) {
            //if ($i >= 6) {
                $image = $modx->runSnippet('phpthumbon', array(
                    'input' => '/assets/images/team/'.$row[image],
                    'options' => 'w=176&h=176',
                ));

                echo '<div class="our_team">';
                echo '<div class="img_our_team"><img src="'.$image.'" alt=""></div>';
                echo '<div class="name_team">'.$row[title].'</div>';
                echo '<div class="profess">'.$row[text].'</div>';
                echo '</div>';

                /*$last = count($rows)-1;
                if($i != $last) {
                    echo '<div class="develop-info__time">'.$row[develop].'</div>';
                }*/
            }
        //}
}
 
@session_write_close();
exit($output);