# Ownpad — Etherpad and Ethercalc links in ownCloud

Ownpad is an ownCloud application that allows to create and open
Etherpad and Ethercalc documents. This application requires to have
access to an instance of Etherpad and/or Ethercalc to work properly.

This application works pretty much like
[`files_etherpad`](https://github.com/EELV-fr/Owncloud-Ether-Docs) but
is a complete rewrite.

## Configuration

In order to make Ownpad work, you need to go to the configuration
panel and then fill the “Collaborative documents” section. There is no
need to fill both Etherpad and Ethercalc hosts.

Afterwards, the “pad” and/or “calc” items will be available in the “+”
menu from the “File” app.

## Mimetype detection

Unfortunatly, apps can’t declare new mimetypes on the fly. To make
Ownpad work properly, you need to add two new mimetypes in the
`mimetypemapping.json` file (at ownCloud level).

To proceed, just copy `/resources/config/mimetypemapping.dist.json` to
`/config/mimetypemapping.json`, and then add the two following lines
just after the “_comment” lines.

    "pad": ["application/x-ownpad"],
    "calc": ["application/x-ownpad"],

If all other mimetypes are not working properly, just run the
following command:

    sudo -u www-data php occ files:scan --all

## Create access limited pads

That development branch of Ownpad (`features/etherpad-api`) supports
communication with Etherpad through its API. With that branch, it’s
now possible to create really private pads that won’t be accessible
without using Ownpad.

Be careful, this is a **work in progress**! [As you can see](TODO.md),
some features are still missing.

To work, you’ll need to give your API key to Ownpad (in the main
configuration page, in the « Collaborative documents » section). You
can find your API key in the `APIKEY.txt` file on your Etherpad
instance.

You’ll need to host your Etherpad instance under a the same
domain. For example, you can host your Etherpad in `pad.example.org`
and your ownCloud in `cloud.example.org`. In that case, you’ll have to
configure `example.org` as the domain cookie in Ownpad’s
configuration.

If you wan’t to create *really* private pads, you have to use a
dedicated Etherpad instance for ownCloud. You will then configure
Etherpad to prevent pads creation (by manipulating URL) and will
enforce API usage to create pad. To do so, you have to add the two
following lines in your Etherpad configuration file (`settings.json`):

    "requireSession" : true,
    "editOnly" : true,
