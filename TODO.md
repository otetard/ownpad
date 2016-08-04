# Todo list

Before we can merge this `feature/etherpad-api` branch to `master`,
some issues need to be fixed.

- [x] Add basic support for Etherpad API (create & open pads).
- [ ] Check rights before opening the file (mainly, respect rights if
      share doesn’t allow modifications).
- [ ] Make configuration more user-friendly (maybe, automatically find
      the most correct cookie domain to use). Maybe also add a new
      configuration item to mark that Etherpad API is usable (after
      test).
- [ ] Try to find a way to work-around the cookie issue (that may
      require to develop a tiny plugin for Etherpad that would allow
      to set the cookie for us).
- [ ] Handle errors and exceptions.
- [ ] Handle session timeouts (we ask for 3600 seconds session to
      Etherpad, but we are using a session cookie).
- [ ] Handle ACL revocation (add some kind of hook on ACL changes, and
      re-validate / check all sessions for that specific pad(s))
- [ ] Manage multiple ownCloud instances for Etherpad (when calling
      `createAuthorIfNotExistsFor`).
