/**
 * Nextcloud - Ownpad
 *
 * This file is licensed under the Affero General Public License
 * version 3 or later. See the COPYING file.
 *
 * @author Olivier Tétard <olivier.tetard@miskin.fr>
 * @copyright Olivier Tétard <olivier.tetard@miskin.fr>, 2023
 */

import { generateUrl } from '@nextcloud/router'

import isPublicPage from './utils/isPublicPage.js'
import isOwnpad from './utils/isOwnpad.js'

window.addEventListener('DOMContentLoaded', function() {
    // If we display a folder, we don't have anything more to do here
    if (isPublicPage() && !isOwnpad()) {
        return
    }

    const contentElmt = document.getElementById('files-public-content')
    const sharingTokenElmt = document.getElementById('sharingToken')
    const footerElmt = document.querySelector('body > footer') || document.querySelector('#app-content > footer')
    const mainContent = document.querySelector('#content')

    const sharingToken = sharingTokenElmt.value
    const viewerUrl = generateUrl('/apps/ownpad/public/{token}', {token: sharingToken});

    // Create viewer frame
    const viewerNode = document.createElement('iframe')
    viewerNode.style.height = '100%'
    viewerNode.style.width = '100%'
    viewerNode.style.position = 'absolute'

    // Inject viewer
    if (contentElmt) {
        contentElmt.innerHTML = ''
        contentElmt.appendChild(viewerNode)
        viewerNode.src = viewerUrl
        footerElmt.style.display = 'none'
        mainContent.style.minHeight = 'calc(100% - var(--header-height))' // Make the viewer take the whole height as the footer is now hidden.
        // overwrite style in order to fix the viewer on public pages
        mainContent.style.marginLeft = '0'
        mainContent.style.marginRight = '0'
        mainContent.style.width = '100%'
        mainContent.style.borderRadius = 'unset'
    }
});
