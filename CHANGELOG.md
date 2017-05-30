Ownpad (0.5.10)
* **Bugfix**: Fix HTML code in template settings (thanks to KTim21).
* **Bugfix**: Change Etherpad/Ethercalc instances hints on the configuration page.
* **Bugfix**: Fix “multisheet support” for Ethercalc.
* **Bugfix**: Fix Ethercalc URL validation.
* **Bugfix**: Fix HTML code in the `noviewer.php` template.

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
