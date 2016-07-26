OwnPad (0.5.1)
* **Bugfix**: disable OwnPad’s mimetypes registration at application level, to prevent breaking all other mimetypes. This requires to manually add mimetypes to ownCloud’s configuration (see README.md).

OwnPad (0.5.0)
* **Bugfix**: fix portability to ownCloud 9.

OwnPad (0.4.0)
* **Enhancement**: port code to ownCloud 9

OwnPad (0.3.0)
**Bugfix**: Add Content-Security-Policy rules in order to allow the pad/calc iframe to be opened (required by ownCloud 8.1).
**Bugfix**: Fix the way URL are encoded to make Etherpad happy.
**Bugfix**: Fix the pad’s viewer size

OwnPad (0.2.0)
* **Enhancement**: New pads/calcs are now handled by a specific AJAX script (`ajax/newpad.php`) that manage the file content.
* **Enhancement**: Autosave items in configuration page, and add a confirmation message (inspired by the `news` app).

OwnPad (0.1.0)
* First release
