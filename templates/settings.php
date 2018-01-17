<?php
/** @var \OCP\IL10N $l */
/** @var array $_ */

script('ownpad', 'settings');
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
                   placeholder="https://beta.etherpad.org/" />
        </p>

        <p>
	    <input type="checkbox" name="ownpad_etherpad_useapi" id="ownpad_etherpad_useapi" class="checkbox"
	           value="1" <?php if ($_['ownpad_etherpad_useapi'] === 'yes') print_unescaped('checked="checked"'); ?> />
	    <label for="ownpad_etherpad_useapi"><?php p($l->t('Use Etherpad API (*experimental*)'));?></label><br/>
        </p>
        <div id="ownpad_etherpad_useapi_settings" class="indent <?php if ($_['ownpad_etherpad_useapi'] !== 'yes') p('hidden'); ?>">
            <p>
                <em>
                    <?php p($l->t('You need to enable Etherpad API if you want to create “protected” pads, that will only be accessible through ownCloud. To make this work, you need to host your Etherpad instance in a sub of sibbling domain of the one that is used by ownCloud (due to cookie isolation).')); ?>
                </em>
            </p>

            <p>
                <label for="ownpad_etherpad_apikey"><?php p($l->t('Etherpad Apikey')); ?></label>
                <input type="text" name="ownpad_etherpad_apikey" id="ownpad_etherpad_apikey" value="<?php p($_['ownpad_etherpad_apikey']); ?>" />
            </p>

            <p>
                <label for="ownpad_etherpad_cookie_domain"><?php p($l->t('Etherpad cookie domain')); ?></label>
                <input type="text" name="ownpad_etherpad_cookie_domain" id="ownpad_etherpad_cookie_domain" value="<?php p($_['ownpad_etherpad_cookie_domain']); ?>" />
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
                   placeholder="https://ethercalc.org" />
        </p>
    </div>

    <div id="ownpad-saved-message">
      <span class="msg success"><?php p($l->t('Saved')); ?></span>
    </div>
  </form>
</div>
