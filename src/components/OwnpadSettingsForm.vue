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
						{{ t('ownpad', 'Use Etherpad API') }}
					</NcCheckboxRadioSwitch>

					<fieldset v-show="settings.etherpadUseApi" id="ownpad-settings-etherpad-api" class="ownpad__sub-section">
						<NcCheckboxRadioSwitch type="switch"
							:checked.sync="settings.etherpadEnableOauth">
							{{ t('ownpad', 'Enable OAuth2 authentication to communicate with Etherpad (introduced in Etherpad 2)') }}
						</NcCheckboxRadioSwitch>

						<NcPasswordField v-if="!settings.etherpadEnableOauth"
							:label="t('ownpad', 'Etherpad Apikey')"
							:value.sync="settings.etherpadApiKey" />

						<NcNoteCard v-if="settings.etherpadEnableOauth" type="info">
							{{ t('ownpad', 'In order to enable OAuth2 authentication in Etherpad, you need to configure a dedicated service account. Please refer to the Etherpad documentation to proceed.') }}
						</NcNoteCard>

						<NcTextField v-if="settings.etherpadEnableOauth"
							:label="t('ownpad', 'Etherpad authentication Client ID')"
							:value.sync="settings.etherpadClientId" />

						<NcPasswordField v-if="settings.etherpadEnableOauth"
							:label="t('ownpad', 'Etherpad authentication Client Secret')"
							:value.sync="settings.etherpadClientSecret" />

						<NcButton :aria-label="t('ownpad', 'Test Etherpad authentication')"
							@click="testEtherpadAuthentication">
							{{ t('ownpad', 'Test Etherpad authentication') }}
						</NcButton>

						<NcNoteCard v-if="testTokenResult.status == 'error'"
							type="error">
							{{ t('ownpad', 'The following error occurred while trying to authenticate to Etherpad: {message}', {message: testTokenResult.message}) }}
						</NcNoteCard>
						<NcNoteCard v-else-if="testTokenResult.status == 'success'"
							type="success">
							{{ t('ownpad', 'Authentication to Etherpad successful!') }}
						</NcNoteCard>

						<NcCheckboxRadioSwitch type="switch"
							:checked.sync="settings.etherpadPublicEnable">
							{{ t('ownpad', 'Allow “public” pads') }}
						</NcCheckboxRadioSwitch>

						<NcNoteCard type="info">
							{{ t('ownpad', 'For example, if you host your Etherpad instance on `pad.example.org` and your Nextcloud instance on `cloud.example.org` you need to configure your cookie to `example.org` domain.') }}
						</NcNoteCard>

						<NcTextField :label="t('ownpad', 'Etherpad cookie domain')"
							placeholder="example.org"
							:value.sync="settings.etherpadCookieDomain" />
					</fieldset>

					<div class="ownpad__sub-section ownpad__legacy-token-mode">
						<div class="ownpad__legacy-token-label">
							{{ t('ownpad', 'Legacy pad handling without token') }}
						</div>
						<NcCheckboxRadioSwitch type="radio"
							name="ownpad-legacy-token-mode"
							:checked="settings.legacyTokenMode === 'none'"
							@update:checked="onLegacyModeChange('none', $event)">
							{{ t('ownpad', 'Deny all legacy pads (strict)') }}
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch type="radio"
							name="ownpad-legacy-token-mode"
							:checked="settings.legacyTokenMode === 'unprotected'"
							@update:checked="onLegacyModeChange('unprotected', $event)">
							{{ t('ownpad', 'Allow only unprotected legacy pads (recommended)') }}
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch type="radio"
							name="ownpad-legacy-token-mode"
							:checked="settings.legacyTokenMode === 'all'"
							@update:checked="onLegacyModeChange('all', $event)">
							{{ t('ownpad', 'Allow all legacy pads (temporary migration mode)') }}
						</NcCheckboxRadioSwitch>
					</div>
					<NcNoteCard type="warning">
						{{ t('ownpad', 'Legacy mode should only be used during migration. Protected pads without token are blocked unless you explicitly allow all legacy pads.') }}
					</NcNoteCard>

					<div class="ownpad__sub-section ownpad__backfill">
						<div class="ownpad__legacy-token-label">
							{{ t('ownpad', 'Backfill legacy pad bindings') }}
						</div>
						<NcNoteCard type="info">
							{{ t('ownpad', 'Existing .pad files are not imported automatically. Use this action to scan and backfill mappings into the database.') }}
						</NcNoteCard>
						<div class="ownpad__actions">
							<NcButton :disabled="backfillRunning"
								@click="runBackfill(true)">
								{{ t('ownpad', 'Dry run backfill') }}
							</NcButton>
							<NcButton :disabled="backfillRunning"
								type="primary"
								@click="runBackfill(false)">
								{{ t('ownpad', 'Run backfill now') }}
							</NcButton>
						</div>
						<NcNoteCard v-if="backfillResult.status === 'success'" type="success">
							{{ t('ownpad', 'Backfill finished. Scanned: {scanned}, Created: {created}, Already bound: {alreadyBound}, Skipped: {skipped}, Conflicts: {conflicts}, Errors: {errors}', {
								scanned: backfillResult.summary.scanned,
								created: backfillResult.summary.created,
								alreadyBound: backfillResult.summary.already_bound,
								skipped: backfillResult.summary.skipped,
								conflicts: backfillResult.summary.conflicts,
								errors: backfillResult.summary.errors,
							}) }}
						</NcNoteCard>
						<NcNoteCard v-else-if="backfillResult.status === 'error'" type="error">
							{{ t('ownpad', 'Backfill failed: {message}', { message: backfillResult.message }) }}
						</NcNoteCard>
						<NcNoteCard v-if="backfillResult.status === 'success' && backfillResult.summary.conflict_details && backfillResult.summary.conflict_details.length > 0" type="warning">
							<strong>{{ t('ownpad', 'Detected conflicts') }}</strong>
							<ul class="ownpad__conflict-list">
								<li v-for="(conflict, idx) in backfillResult.summary.conflict_details"
									:key="`${conflict.file_id}-${idx}`">
									<div>
										{{ t('ownpad', 'File {fileId} ({path}) conflicts on pad {padId} ({reason}){conflictFile}', {
										fileId: conflict.file_id,
										path: conflict.path,
										padId: conflict.pad_id,
										reason: conflict.reason,
										conflictFile: conflict.conflict_file_id ? ` with file ${conflict.conflict_file_id}` : '',
										}) }}
									</div>
									<div class="ownpad__conflict-actions">
										<a class="button-vue button-vue--size-normal button-vue--text-only"
											:href="absoluteFileLink(conflict.file_link)"
											target="_blank"
											rel="noopener noreferrer">
											{{ t('ownpad', 'Show in files') }}
										</a>
										<NcButton :disabled="backfillRunning || backfillActionFileId === conflict.file_id"
											@click="trashConflictFile(conflict)">
											{{ t('ownpad', 'Move .pad to trash') }}
										</NcButton>
									</div>
								</li>
							</ul>
						</NcNoteCard>
					</div>
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
	NcButton,
} from '@nextcloud/vue'
import { loadState } from '@nextcloud/initial-state'
import { defineComponent } from 'vue'
import { snakeCase } from 'lodash'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export default defineComponent({
	name: 'OwnpadSettingsForm',
	components: {
	 NcNoteCard,
	 NcSettingsSection,
		NcCheckboxRadioSwitch,
		NcTextField,
		NcPasswordField,
		NcButton,
	},
	data() {
	    return {
			settingsData: loadState('ownpad', 'settings'),
			testTokenResult: {},
			backfillResult: {},
			backfillRunning: false,
			backfillActionFileId: null,
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
		onLegacyModeChange(mode, checked) {
			if (checked) {
				this.settings.legacyTokenMode = mode
			}
		},
		async testEtherpadAuthentication() {
			try {
				await axios.get(
					generateUrl('/apps/ownpad/ajax/v1.0/testetherpadtoken'),
				)
				this.testTokenResult = { status: 'success' }
			} catch (error) {
				this.testTokenResult = {
					status: 'error',
					message: error.response.data.data.message,
				}
			}
		},
		async runBackfill(dryRun) {
			this.backfillRunning = true
			this.backfillResult = {}
			try {
				const response = await axios.post(
					generateUrl('/apps/ownpad/ajax/v1.0/backfillbindings'),
					{ dryRun },
				)
				this.backfillResult = {
					status: 'success',
					summary: response.data.data.summary,
				}
			} catch (error) {
				this.backfillResult = {
					status: 'error',
					message: error?.response?.data?.data?.message || this.t('ownpad', 'Unexpected error'),
				}
			} finally {
				this.backfillRunning = false
			}
		},
		absoluteFileLink(path) {
			if (!path || typeof path !== 'string') {
				return '#'
			}
			const base = window.location.origin + generateUrl('/')
			return new URL(path.replace(/^\//, ''), base).toString()
		},
		async trashConflictFile(conflict) {
			if (!conflict || !conflict.file_id) {
				return
			}
			this.backfillActionFileId = conflict.file_id
			try {
				await axios.post(
					generateUrl('/apps/ownpad/ajax/v1.0/backfilltrashfile'),
					{ fileId: conflict.file_id },
				)
				await this.runBackfill(true)
			} catch (error) {
				this.backfillResult = {
					status: 'error',
					message: error?.response?.data?.data?.message || this.t('ownpad', 'Unexpected error'),
				}
			} finally {
				this.backfillActionFileId = null
			}
		},
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

	&__legacy-token-label {
		font-weight: 500;
	}

	&__legacy-token-mode {
		margin-inline-start: 14px;
	}

	&__backfill {
		margin-block-start: 10px;
	}

	&__actions {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
	}

	&__conflict-list {
		margin: 8px 0 0 16px;
	}

	&__conflict-actions {
		display: flex;
		gap: 8px;
		margin-top: 4px;
	}
}
</style>
