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
						<NcNoteCard v-if="backfillActionError" type="error">
							{{ backfillActionError }}
						</NcNoteCard>
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
						<NcNoteCard v-if="backfillResult.status === 'success' && conflictGroups.length > 0" type="warning">
							<strong>{{ t('ownpad', 'Detected conflicts') }}</strong>
							<p>
								{{ t('ownpad', 'A conflict means multiple .pad files point to the same Etherpad document. Only one file can be the canonical mapped file in the database.') }}
							</p>
							<p>
								{{ t('ownpad', 'Use the radio choice per group to select the file that should become the valid mapping, then apply all selections once.') }}
							</p>
							<div class="ownpad__conflict-groups">
								<div v-for="(group, gIdx) in conflictGroups" :key="`group-${gIdx}`" class="ownpad__conflict-group">
									<div class="ownpad__conflict-group-header">
										{{ t('ownpad', 'Pad {padId} on {baseUrl}', { padId: group.pad_id, baseUrl: group.base_url }) }}
									</div>
									<div class="ownpad__conflict-group-description">
										{{ t('ownpad', 'These files currently reference the same target pad:') }}
									</div>
									<ul class="ownpad__conflict-list">
										<li v-for="(conflict, idx) in group.items"
											:key="`${group.pad_id}-${conflict.file_id}-${idx}`">
											<div v-if="canSelectAsValid(conflict)" class="ownpad__conflict-radio">
												<input
													:id="`ownpad-conflict-${groupKey(group)}-${conflict.file_id}`"
													:name="`ownpad-conflict-group-${groupKey(group)}`"
													type="radio"
													:value="conflict.file_id"
													:checked="selectedConflictFileId(group) === conflict.file_id"
													:disabled="backfillRunning || backfillActionApplying"
													@change="selectConflictAsValid(group, conflict.file_id)">
												<label :for="`ownpad-conflict-${groupKey(group)}-${conflict.file_id}`">
													{{ t('ownpad', 'Select as valid mapping') }}
												</label>
											</div>
											<div>
												{{ t('ownpad', 'File {fileId} ({path}): {reason}{conflictFile}', {
													fileId: conflict.file_id,
													path: conflict.path,
													reason: conflictReasonLabel(conflict.reason),
													conflictFile: conflict.conflict_file_id ? ` with file ${conflict.conflict_file_id}` : '',
												}) }}
											</div>
											<div class="ownpad__conflict-actions">
												<a class="button-vue button-vue--size-normal button-vue--primary"
													:href="absoluteFileLink(conflict.file_link)"
													target="_blank"
													rel="noopener noreferrer">
													{{ t('ownpad', 'Show in files') }}
												</a>
												<button v-if="canCreateAlias(conflict)"
													type="button"
													class="button-vue button-vue--size-small button-vue--text-only ownpad__small-action"
													:disabled="backfillRunning || backfillActionFileId === conflict.file_id || backfillActionApplying"
													@click="createAliasNote(conflict)">
													{{ t('ownpad', 'Create alias note') }}
												</button>
											</div>
										</li>
									</ul>
								</div>
							</div>
							<div class="ownpad__actions ownpad__conflict-apply">
								<NcButton
									:disabled="backfillRunning || backfillActionApplying || selectedConflictCount === 0"
									type="primary"
									@click="applySelectedConflicts">
									{{ t('ownpad', 'Apply selected as valid') }}
								</NcButton>
							</div>
						</NcNoteCard>
						<NcNoteCard v-if="backfillResult.status === 'success' && skippedDetails.length > 0" type="info">
							<strong>{{ t('ownpad', 'Skipped files') }}</strong>
							<ul class="ownpad__conflict-list">
								<li v-for="(item, idx) in skippedDetails"
									:key="`skipped-${item.file_id}-${idx}`">
									<div>
										{{ t('ownpad', 'File {fileId} ({path}) skipped: {reason}', {
											fileId: item.file_id,
											path: item.path || 'n/a',
											reason: item.reason,
										}) }}
									</div>
									<div v-if="item.file_link" class="ownpad__conflict-actions">
										<a class="button-vue button-vue--size-small button-vue--text-only"
											:href="absoluteFileLink(item.file_link)"
											target="_blank"
											rel="noopener noreferrer">
											{{ t('ownpad', 'Show in files') }}
										</a>
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
			backfillActionApplying: false,
			backfillActionError: '',
			conflictSelections: {},
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
		conflictGroups() {
			if (this.backfillResult.status !== 'success') {
				return []
			}
			return this.backfillResult?.summary?.conflict_groups || []
		},
		skippedDetails() {
			if (this.backfillResult.status !== 'success') {
				return []
			}
			return this.backfillResult?.summary?.skipped_details || []
		},
		selectedConflictCount() {
			return Object.values(this.conflictSelections).filter((fileId) => Number(fileId) > 0).length
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
			this.backfillActionError = ''
			this.conflictSelections = {}
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
		canSelectAsValid(conflict) {
			return conflict && conflict.reason === 'duplicate_in_current_run'
		},
		groupKey(group) {
			return `${group?.base_url || ''}\n${group?.pad_id || ''}`
		},
		selectedConflictFileId(group) {
			const key = this.groupKey(group)
			return Number(this.conflictSelections[key] || 0)
		},
		selectConflictAsValid(group, fileId) {
			const key = this.groupKey(group)
			const normalizedFileId = Number(fileId)
			if (!key || normalizedFileId <= 0) {
				return
			}
			this.conflictSelections = {
				...this.conflictSelections,
				[key]: normalizedFileId,
			}
		},
		canCreateAlias(conflict) {
			return conflict
				&& conflict.reason === 'pad_already_bound_to_other_file'
				&& !!conflict.conflict_file_id
		},
		conflictReasonLabel(reason) {
			if (reason === 'duplicate_in_current_run') {
				return this.t('ownpad', 'duplicate found during this backfill run')
			}
			if (reason === 'pad_already_bound_to_other_file') {
				return this.t('ownpad', 'already mapped to another file')
			}
			return reason || this.t('ownpad', 'unknown reason')
		},
		async applySelectedConflicts() {
			const selectedFileIds = Object.values(this.conflictSelections)
				.map((fileId) => Number(fileId))
				.filter((fileId) => fileId > 0)
			if (selectedFileIds.length === 0) {
				return
			}

			this.backfillActionApplying = true
			this.backfillActionError = ''
			try {
				for (const fileId of selectedFileIds) {
					await axios.post(
						generateUrl('/apps/ownpad/ajax/v1.0/backfillmarkvalid'),
						{ fileId },
					)
				}
				await this.runBackfill(true)
			} catch (error) {
				this.backfillActionError = error?.response?.data?.data?.message || this.t('ownpad', 'Unexpected error while applying selected mappings')
			} finally {
				this.backfillActionApplying = false
			}
		},
		async createAliasNote(conflict) {
			if (!conflict || !conflict.file_id || !conflict.conflict_file_id) {
				return
			}
			this.backfillActionFileId = conflict.file_id
			this.backfillActionError = ''
			try {
				await axios.post(
					generateUrl('/apps/ownpad/ajax/v1.0/backfillcreatealias'),
					{ fileId: conflict.file_id, targetFileId: conflict.conflict_file_id },
				)
				await this.runBackfill(true)
			} catch (error) {
				this.backfillActionError = error?.response?.data?.data?.message || this.t('ownpad', 'Unexpected error')
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
		align-items: center;
	}

	&__conflict-radio {
		display: flex;
		align-items: center;
		gap: 6px;
		margin-bottom: 4px;
	}

	&__conflict-groups {
		display: flex;
		flex-direction: column;
		gap: 10px;
	}

	&__conflict-group {
		border: 1px solid var(--color-border);
		border-radius: 8px;
		padding: 8px;
	}

	&__conflict-group-header {
		font-weight: 600;
		margin-bottom: 6px;
	}

	&__conflict-group-description {
		margin-bottom: 6px;
		color: var(--color-text-maxcontrast);
	}

	&__small-action {
		opacity: 0.9;
	}

	&__conflict-apply {
		margin-top: 8px;
	}
}
</style>
