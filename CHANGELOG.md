Upcoming:
* **Enhancement**: use proper icons of etherpad-lite and ethercalc.

Ownpad (0.6.5):
* **Bugfix**: fix UI with Nextcloud 13 (thanks @frissdiegurke).
* **Enhancement**: enhance settings page.

Ownpad (0.6.4):
* **Bugfix**: fix protected pads (thanks @frissdiegurke).
* **Enhancement**: replace ownCloud by Nextcloud.

Ownpad (0.6.3):
* **Enhancement**: move application to the `office` section on Nextcloud app store.
* **Bugfix**: update message displayed when pad/calc URL doesn’t match configuration.
* **Bugfix**: when applying migration, don’t forget to check version…

Ownpad (0.6.2):
* **Enhancement**: make app compatible with Nextcloud 13 (and drop ownCloud support).
* **Enhancement**: finish code refactoring (`app.php` moved to `Application` class).
* **Bugfix**: don’t forget to load JavaScript code on the settings page.

Ownpad (0.6.1):
* **Enhancement**: move internal logic to a new OwnpadService class.
* **Bugfix**: fix regression introduced in previous version (bb3f3199c44d35b21a45d1ae6dd5524853f401cf).

Ownpad (0.6.0):
* **Enhancement**: support for Etherpad API (experimental, incomplete and probably not totally secure) which allows to create private pads.
* **Enhancement**: refactor app to use AppFramework.
* **Bugfix**: remove deprecated code (thanks @MorrisJobke!).

Ownpad (0.5.10)
* **Bugfix**: fix HTML code in template settings (thanks to KTim21).
* **Bugfix**: change Etherpad/Ethercalc instances hints on the configuration page.
* **Bugfix**: fix “multisheet support” for Ethercalc.
* **Bugfix**: fix Ethercalc URL validation.
* **Bugfix**: fix HTML code in the `noviewer.php` template.

Ownpad (0.5.9)
* **Enhancement**: check for valid URL in pads/calcs to prevent bad redirections (thanks to Stephan Wiefling).
* **Enhancement**: make Ownpad compatible with Nextcloud 12.
* **Enhancement**: update documentation.

Ownpad (0.5.8)
* **Enhancement**: enable multisheet support for new Ethercalc.
* **Enhancement**: update Content-Security-Policy rules.

Ownpad (0.5.6)
* **Enhancement**: make Ownpad compatible with Nextcloud 11.
* **Bugfix**: don’t call for Ownpad configuration on public pages (fixes page reload on public pages)

Ownpad (0.5.4)
* **Bugfix**: fix info.xml format for Nextcloud appstore.

Ownpad (0.5.3)
* **Enhancement**: add icon for Etherpad in the “+” menu.
* **Enhancement**: some minor code enhacements.
* **Bugfix**: fix upgrade code (issue was introduced in 6560a6adf1b5027dfb70c0df6eff527f4d2304f2).

Ownpad (0.5.2)
* **Enhancement**: don’t display pad/calc if no URL is configured for Ownpad.
* **Enhancement**: some minor changes (typo, etc.)
* **Enhancement**: minor changes to the configuration page.

Ownpad (0.5.1)
* **Bugfix**: disable Ownpad’s mimetypes registration at application level, to prevent breaking all other mimetypes. This requires to manually add mimetypes to ownCloud’s configuration (see README.md).

Ownpad (0.5.0)
* **Bugfix**: fix portability to ownCloud 9.

Ownpad (0.4.0)
* **Enhancement**: port code to ownCloud 9

Ownpad (0.3.0)
**Bugfix**: Add Content-Security-Policy rules in order to allow the pad/calc iframe to be opened (required by ownCloud 8.1).
**Bugfix**: Fix the way URL are encoded to make Etherpad happy.
**Bugfix**: Fix the pad’s viewer size

Ownpad (0.2.0)
* **Enhancement**: New pads/calcs are now handled by a specific AJAX script (`ajax/newpad.php`) that manage the file content.
* **Enhancement**: Autosave items in configuration page, and add a confirmation message (inspired by the `news` app).

Ownpad (0.1.0)
* First release
