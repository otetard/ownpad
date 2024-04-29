<!--
   - Nextcloud - Ownpad
   -
   - This file is licensed under the Affero General Public License
   - version 3 or later. See the COPYING file.
   -
   - @author Olivier Tétard <olivier.tetard@miskin.fr>
   - @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2023
-->

<template>
	<NcSettingsSection :name="t('ownpad', 'Ownpad (collaborative documents)')"
		:description="t('ownpad', 'This is used to link collaborative documents inside Nextcloud.')">
		<NcNoteCard v-if="!settings.mimetypeEpConfigured || !settings.mimetypeEcConfigured" type="warning">
			{{ t('ownpad', 'Ownpad is not correctly configured, you should update your configuration. Please refer to the documentation for more information.') }}
		</NcNoteCard>

		<form class="sharing">
			<div class="ownpad__section">
				<NcCheckboxRadioSwitch type="switch"
					:checked.sync="settings.etherpadEnable">
					{{ t('ownpad', 'Enable Etherpad') }}
				</NcCheckboxRadioSwitch>

				<fieldset v-show="settings.etherpadEnable" id="ownpad-settings-etherpad" class="ownpad__sub-section">
					<NcTextField :label="t('ownpad', 'Etherpad Host')"
						placeholder="http://beta.etherpad.org"
						:value.sync="settings.etherpadHost" />

					<NcNoteCard type="info">
						{{ t('ownpad', 'You need to enable Etherpad API if you want to create “protected” pads, that will only be accessible through Nextcloud.') }}
						<br>
						{{ t('ownpad', 'You have to host your Etherpad instance in a subdomain or sibbling domain of the one that is used by Nextcloud (due to cookie isolation).') }}
					</NcNoteCard>

					<NcCheckboxRadioSwitch type="switch"
						:checked.sync="settings.etherpadUseApi">
						{{ t('ownpad', 'Use Etherpad API (*experimental*)') }}
					</NcCheckboxRadioSwitch>

					<fieldset v-show="settings.etherpadUseApi" id="ownpad-settings-etherpad-api" class="ownpad__sub-section">
						<NcCheckboxRadioSwitch type="switch"
							:checked.sync="settings.etherpadPublicEnable">
							{{ t('ownpad', 'Allow “public” pads') }}
						</NcCheckboxRadioSwitch>

						<NcPasswordField :label="t('ownpad', 'Etherpad Apikey')"
							:value.sync="settings.etherpadApiKey" />

						<NcNoteCard type="info">
							{{ t('ownpad', 'For example, if you host your Etherpad instance on `pad.example.org` and your Nextcloud instance on `cloud.example.org` you need to configure your cookie to `example.org` domain.') }}
						</NcNoteCard>

						<NcTextField :label="t('ownpad', 'Etherpad cookie domain')"
							placeholder="example.org"
							:value.sync="settings.etherpadCookieDomain" />
					</fieldset>
				</fieldset>
			</div>

			<div class="ownpad__section">
				<NcCheckboxRadioSwitch type="switch"
					:checked.sync="settings.ethercalcEnable">
					{{ t('ownpad', 'Enable Ethercalc') }}
				</NcCheckboxRadioSwitch>

				<fieldset v-show="settings.ethercalcEnable" id="ownpad-ethercalc-settings" class="ownpad__sub-section">
					<NcTextField :label="t('ownpad', 'Ethercalc Host')"
						placeholder="https://ethercalc.org"
						:value.sync="settings.ethercalcHost" />
				</fieldset>
			</div>
		</form>
	</NcSettingsSection>
</template>

<script>
import {
	NcNoteCard,
	NcSettingsSection,
	NcCheckboxRadioSwitch,
	NcTextField,
	NcPasswordField,
} from '@nextcloud/vue'
import { loadState } from '@nextcloud/initial-state'
import { defineComponent } from 'vue'
import { snakeCase } from 'lodash'

export default defineComponent({
	name: 'OwnpadSettingsForm',
	components: {
	 NcNoteCard,
	 NcSettingsSection,
		NcCheckboxRadioSwitch,
		NcTextField,
		NcPasswordField,
	},
	data() {
	 return {
	     settingsData: loadState('ownpad', 'settings'),
	 }
	},
	computed: {
		settings() {
	     return new Proxy(this.settingsData, {
		 get(target, property) {
		         return target[property]
		 },
		 set(target, property, newValue) {
		     const configName = `ownpad_${snakeCase(property)}`
		     const value = typeof newValue === 'boolean' ? (newValue ? 'yes' : 'no') : (typeof newValue === 'string' ? newValue : JSON.stringify(newValue))
		     window.OCP.AppConfig.setValue('ownpad', configName, value)
		     target[property] = newValue
		     return true
		 },
	     })
	 },
	},
	methods: {
	 t,
	},
})
</script>

<style lang="scss" scoped>
.ownpad {
	display: flex;
	flex-direction: column;
	gap: 12px;

	&__labeled-entry {
		display: flex;
		flex: 1 0;
		flex-direction: column;
		gap: 4px;
	}

	&__section {
		display: flex;
		flex-direction: column;
		gap: 4px;
		margin-block-end: 12px
	}

	&__sub-section {
		display: flex;
		flex-direction: column;
		gap: 4px;

		margin-inline-start: 44px;
		margin-block-end: 12px
	}

	&__input {
		max-width: 500px;
		// align with checkboxes
		margin-inline-start: 14px;

		:deep(.v-select.select) {
			width: 100%;
		}
	}
}
</style>
