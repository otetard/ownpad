<?php
/** @var OC_L10N $l */
/** @var array $_ */
?>
<div class="section">
  <form id="ownpad_settings">

    <h2><?php p($l->t('Collaborative documents'));?></h2>

    <p><?php p($l->t('This is used to link collaborative documents inside ownCloud.')); ?></p>

    <p>
	<input type="checkbox" name="ownpad_etherpad_enable" id="ownpad_etherpad_enable" class="checkbox"
	       value="1" <?php if ($_['ownpad_etherpad_enable'] === 'yes') print_unescaped('checked="checked"'); ?> />
	<label for="ownpad_etherpad_enable"><?php p($l->t('Enable Etherpad'));?></label><br/>
    </p>

    <div id="ownpad_etherpad_settings" class="indent <?php if ($_['ownpad_etherpad_enable'] !== 'yes') p('hidden'); ?>">
        <p>
            <label for="ownpad_etherpad_host"><?php p($l->t('Etherpad Host')); ?></label>
            <input type="text" name="ownpad_etherpad_host" id="ownpad_etherpad_host"
	           value="<?php p($_['ownpad_etherpad_host']); ?>"
                   placeholder="https://mensuel.framapad.org" />
        </p>

        <p>
	    <input type="checkbox" name="ownpad_etherpad_useapi" id="ownpad_etherpad_useapi" class="checkbox"
	           value="1" <?php if ($_['ownpad_etherpad_useapi'] === 'yes') print_unescaped('checked="checked"'); ?> />
	    <label for="ownpad_etherpad_useapi"><?php p($l->t('Use Etherpad API'));?></label><br/>
        </p>
        <div id="ownpad_etherpad_useapi_settings" class="indent <?php if ($_['ownpad_etherpad_useapi'] !== 'yes') p('hidden'); ?>">
            <p>
                <label for="ownpad_etherpad_apikey"><?php p($l->t('Etherpad Apikey')); ?></label>
                <input type="text" name="ownpad_etherpad_apikey" id="ownpad_etherpad_apikey" value="<?php p($_['ownpad_etherpad_apikey']); ?>" />
            </p>
        </div>
    </div>

    <p>
	<input type="checkbox" name="ownpad_ethercalc_enable" id="ownpad_ethercalc_enable" class="checkbox"
	       value="1" <?php if ($_['ownpad_ethercalc_enable'] === 'yes') print_unescaped('checked="checked"'); ?> />
	<label for="ownpad_ethercalc_enable"><?php p($l->t('Enable Ethercalc'));?></label><br/>
    </p>

    <div id="ownpad_ethercalc_settings" class="indent <?php if ($_['ownpad_ethercalc_enable'] !== 'yes') p('hidden'); ?>">
        <p>
            <label for="ownpad_ethercalc_host"><?php p($l->t('Ethercalc Host')); ?></label>
            <input type="text" name="ownpad_ethercalc_host" id="ownpad_ethercalc_host"
	           value="<?php p($_['ownpad_ethercalc_host']); ?>"
                   placeholder="https://framacalc.org" />
        </p>
    </div>

    <div id="ownpad-saved-message">
      <span class="msg success"><?php p($l->t('Saved')); ?></span>
    </div>
</div>
