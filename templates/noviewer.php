<?php
/** @var array $_ */
/** @var OCP\IURLGenerator $urlGenerator */
$urlGenerator = $_['urlGenerator'];
$version = \OCP\App::getAppVersion('ownpad');
$url = $_['url'];
$title = $_['title'];
?>
<!DOCTYPE html>
<html style="height: 100%;">
  <head>
    <link rel="stylesheet" href="<?php p($urlGenerator->linkTo('ownpad', 'css/ownpad.css')) ?>?v=<?php p($version) ?>"/>
  </head>
  <body style="margin: 0px; padding: 0px; overflow: hidden; bottom: 37px; top: 0px; left: 0px; right: 0px; position: absolute;">
    <div id="filetopad_bar">
      <span>Title</span><strong><?php p($title); ?></strong><a id="filetopad_close">x</a>
    </div>
    <p><?php p($l->t("The Target-URL of this File does not match the allowed server. The Forwading to Etherpad/Ethercalc has been blocked.")); ?></p>
  </body>
</html>
