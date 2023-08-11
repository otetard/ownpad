# Changelog

All notable changes to this project will be documented in this file.

## 0.9.0-beta.1 - 2023-08-10

### Added

- Configure MIME type and add icon for Etherpad & Ethercalc documents. This is heavily inspired by [`drawio-nextcloud`][drawio] and [`files_mindmap`][mindmap] Nextcloud applications.
- Add support for shared pad/calc documents.
- Increase the size of random pad/calc names and make the size variable (from 32 to 64 characters).

[drawio]: https://github.com/jgraph/drawio-nextcloud
[mindmap]: https://github.com/ACTom/files_mindmap

### Fixed

- Add white background in pad/calc’s main iframe.

## 0.8.1 - 2023-08-08

### Fixed

- Reintroduce autoloading of EtherpadLiteClient third-party library (thanks to @e1mo for the feeback!).

## 0.8.0 - 2023-08-07

### Changed

- Rely on Files viewer to open pad and calc files.
- Add Nextcloud 26, 27 and 28 compatibility.
- Use Webpack to minify Javascript files.

## 0.7.1 - 2022-11-13

### Fixed

- Update the way we identify the current directory (don’t rely [on now removed `#dir`](https://github.com/nextcloud/server/pull/33373)).

## 0.7.0 - 2022-11-13

### Changed

- Add Nextcloud 25 compatibility.
- Improve style of the top bar when opening a pad (thanks to @fenglisch!)

## 0.6.18 - 2022-05-20

### Changed

- Add Nextcloud 24 compatibility.

## 0.6.17 - 2022-01-21

### Changed

- Add Nextcloud 23 compatibility.

## 0.6.16 - 2021-03-24

### Changed

- Add Nextcloud 21 compatibility.

## 0.6.15 - 2020-09-02

### Changed

- Add Nextcloud 19 compatibility (no changes; thanks @sim6).

## 0.6.14 - 2020-01-19

### Changed

- Add Nextcloud 17 & 18 compatibility (no changes)

## 0.6.13 - 2019-05-25

### Changed

- Add Nextcloud 16 compatibility (no changes)

## 0.6.12 - 2019-02-15

### Fixed

- Fix regexp used to enable protected pads by removing trailing slash from EPL hostname.

## 0.6.11 - 2018-12-20

### Fixed

- Fix public pad creation if Etherpad API is not used.

## 0.6.10 - 2018-12-19

### Changed

- Ownpad should work fine with Nextcloud 15.

## 0.6.9 - 2018-12-11

### Added

- Use API to create unprotected pads (thanks @m0urs).

### Changed

- Update `README.md` to make more explicit that this app is no more than doing links to Etherpad/Ethercalc.

### Fixed

- Remove deprecated method `getMediumStrengthGenerator` (thanks @rullzer).
- Only use lower case in pad and calc names to prevent an issue with Ethercalc (thanks @dtygel)

## 0.6.8 - 2018-08-12

### Fixed

- Update previous fix to let Ownpad be also compatible with NC 13.

## 0.6.7 - 2018-08-11

### Changed

- Remove deprecated code calls and let Ownpad be compatible with Nextcloud 14.

## 0.6.6 - 2018-03-19

### Added

- Add option to disable non-protected 'public' pads.

### Changed

- Use proper icons of etherpad-lite and ethercalc.

## 0.6.5 - 2018-02-12

### Changed

- Enhance settings page.

### Fixed

- Fix UI with Nextcloud 13 (thanks @frissdiegurke).

## 0.6.4 - 2018-01-31

### Changed

- Replace ownCloud by Nextcloud.

### Fixed

Fix protected pads (thanks @frissdiegurke).

## 0.6.3 - 2018-01-30

### Changed

- Move application to the `office` section on Nextcloud app store.

### Fixed

- Update message displayed when pad/calc URL doesn’t match configuration.
- When applying migration, don’t forget to check version…

## 0.6.2 - 2018-01-17

### Changed

- Make app compatible with Nextcloud 13 (and drop ownCloud support).
- Finish code refactoring (`app.php` moved to `Application` class).

### Fixed

Don’t forget to load JavaScript code on the settings page.

## 0.6.1 - 2018-01-15

### Changed

- Move internal logic to a new OwnpadService class.

### Fixed

- Fix regression introduced in previous version (bb3f3199c44d35b21a45d1ae6dd5524853f401cf).

## 0.6.0 - 2018-01-14

### Added

- Support for Etherpad API (experimental, incomplete and probably not totally secure) which allows to create private pads.

### Changed

- Refactor app to use AppFramework.

### Fixed

- Remove deprecated code (thanks @MorrisJobke!).

## 0.5.10 - 2017-05-30

### Fixed

- Fix HTML code in template settings (thanks to KTim21).
- Change Etherpad/Ethercalc instances hints on the configuration page.
- Fix “multisheet support” for Ethercalc.
- Fix Ethercalc URL validation.
- Fix HTML code in the `noviewer.php` template.

## 0.5.9 - 2017-05-23

### Added

- Check for valid URL in pads/calcs to prevent bad redirections (thanks to Stephan Wiefling).

### Changed

- Make Ownpad compatible with Nextcloud 12.
- Update documentation.

## 0.5.8 - 2017-05-19

### Added

- Enable multisheet support for new Ethercalc.

### Changed

- update Content-Security-Policy rules.

## 0.5.6 - 2016-11-19

### Changed

- make Ownpad compatible with Nextcloud 11.

### Fixed

- Don’t call for Ownpad configuration on public pages (fixes page reload on public pages)

## 0.5.4 - 2016-10-06

### Fixed

- Fix `info.xml` format for Nextcloud appstore.

## 0.5.3 - 2016-09-12

### Added

- Add icon for Etherpad in the “+” menu.

### Changed

- Some minor code enhacements.

### Fixed

- Fix upgrade code (issue was introduced in 6560a6adf1b5027dfb70c0df6eff527f4d2304f2).

## 0.5.2 - 2016-08-02

### Changed

- Don’t display pad/calc if no URL is configured for Ownpad.
- Some minor changes (typo, etc.)
- Minor changes to the configuration page.

## 0.5.1 - 2016-07-26

### Fixed

- Disable Ownpad’s mimetypes registration at application level, to prevent breaking all other mimetypes. This requires to manually add mimetypes to ownCloud’s configuration (see README.md).

## 0.5.0 - 2016-03-09

### Changed

- Fix portability to ownCloud 9.

## 0.4.0 - 2015-10-30

### Changed

- Port code to ownCloud 9

## 0.3.0 - 2015-07-07

### Changed

- Add Content-Security-Policy rules in order to allow the pad/calc iframe to be opened (required by ownCloud 8.1).

### Fixed

- Fix the way URL are encoded to make Etherpad happy.
- Fix the pad’s viewer size

## 0.2.0 - 2015-05-26

### Added

- Autosave items in configuration page, and add a confirmation message (inspired by the `news` app).

### Changed

- New pads/calcs are now handled by a specific AJAX script (`ajax/newpad.php`) that manage the file content.
