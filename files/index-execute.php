# digraph-project-core/files/index-execute.php => web/index.php

# send notifications
foreach ($NOTICES as $message) {
    $cms->helper('notifications')->notice($message);
}
foreach ($WARNINGS as $message) {
    $cms->helper('notifications')->warning($message);
}
foreach ($ERRORS as $message) {
    $cms->helper('notifications')->error($message);
}

# calling CMS::fullMunge() will apply the mungers specified
# in the "fullmunge" config
# by default this means building a response and also rendering it
$cms->fullMunge($package);