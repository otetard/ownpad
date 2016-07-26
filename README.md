# OwnPad — Etherpad and Ethercalc links in ownCloud

OwnPad is an ownCloud application that allows to create and open
Etherpad and Ethercalc documents. This application requires to have
access to an instance of Etherpad and/or Ethercalc to work properly.

This application works pretty much like
[`files_etherpad`](https://github.com/EELV-fr/Owncloud-Ether-Docs) but
is a complete rewrite.

## Mimetype detection

Unfortunatly, apps can’t declare new mimetypes on the fly. To make
OwnPad work properly, you need to add two new mimetypes in the
`mimetypemapping.json` file (at ownCloud level).

To proceed, just copy `/resources/config/mimetypemapping.dist.json` to
`/config/mimetypemapping.json`, and then add the two following lines
just after the “_comment” lines.

    "pad": ["application/x-ownpad"],
    "calc": ["application/x-ownpad"],

If all other mimetypes are not working properly, just run the
following command:

    sudo -u www-data php occ files:scan --all
