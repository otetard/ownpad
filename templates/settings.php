<?php
/** @var OC_L10N $l */
/** @var array $_ */
?>
<div class="section">
  <form id="ownpad_settings">

    <h2><?php p($l->t('Collaborative documents'));?></h2>

    <p><?php p($l->t('This is used to link collaborative documents inside ownCloud.')); ?></p>

    <p>
      <label for="ownpad_etherpad_host"><?php p($l->t('Etherpad Host')); ?></label>
      <input type="text" name="ownpad_etherpad_host" id="ownpad_etherpad_host"
	     value="<?php p($_['ownpad_etherpad_host']); ?>"
             placeholder="https://mensuel.framapad.org" />
    </p>

    <p>
      <label for="ownpad_ethercalc_host"><?php p($l->t('Ethercalc Host')); ?></label>
      <input type="text" name="ownpad_ethercalc_host" id="ownpad_ethercalc_host"
	     value="<?php p($_['ownpad_ethercalc_host']); ?>"
             placeholder="https://framacalc.org" />
    </p>

    <div id="ownpad-saved-message">
      <span class="msg success"><?php p($l->t('Saved')); ?></span>
    </div>
</div>
