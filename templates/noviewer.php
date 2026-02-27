<?php
/** @var array $_ */
/** @var OCP\IURLGenerator $urlGenerator */
$urlGenerator = $_['urlGenerator'];
$version = $_['ownpad_version'];
$url = $_['url'] ?? '';
$title = $_['title'];
$error = $_['error'];
?>
<!DOCTYPE html>
<html style="height: 100%;">
  <head>
    <link rel="stylesheet" href="<?php p($urlGenerator->linkTo('ownpad', 'css/ownpad.css')) ?>?v=<?php p($version) ?>"/>
  </head>
  <body style="margin: 0px; padding: 0px; overflow: hidden; bottom: 37px; top: 0px; left: 0px; right: 0px; position: absolute;">
    <div id="ownpad_bar">
      <span>Title</span><strong><?php p($title); ?></strong>
    </div>
    <div style="background-color: white; height: 100%; width: 100%;">
     <p><?php p($l->t("Your Etherpad/Ethercalc document could not be opened, the following error was reported: “%s”.", [$error])); ?></p>
    </div>
  </body>
</html>
