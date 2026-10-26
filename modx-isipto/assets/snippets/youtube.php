<?php
$domains = array('www.youtube.com', 'youtube.com');
$parse = parse_url($input);
if (isset($parse['host']) && in_array($parse['host'], $domains) &&
    isset($parse['query']) && strpos($parse['query'], 'v=') !== false) {
    $string = explode('v=', $input);
    $clean = explode('&', $string[1]);
    if (!empty($clean[0])) {
        return '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' . $clean[0] . '?rel=0&controls=0&showinfo=0" frameborder="0" allowfullscreen></iframe>';
    } else {
        return false;
    }
}
return false;