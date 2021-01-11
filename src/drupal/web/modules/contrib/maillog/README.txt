Maillog
-------
The Maillog module provides a method of keeping archival copies of all emails
sent through the site.


Features
--------------------------------------------------------------------------------
* All emails being sent by the site may have a copy stored in the database for
  later review.

* All email delivery may be halted, preventing a site from sending out emails
  in situations where that might not be needed, e.g. for a local development
  copy of the site.

* If the MailSystem module [1] is installed it is possible to control which of
  the installed email modules will be used to send messages from Maillog's
  settings page, mirroring MailSystem's functionality.


Configuration
--------------------------------------------------------------------------------
 1. On the People Permissions administration page ("Administer >> People
    >> Permissions") there are three permissions to control:

    - The "Administer Maillog" permission allows users to access the settings
      page to control the module's options.

    - The "View Maillog" permission allows users to access the Maillog list page
      at admin/reports/maillog.

    - The "Delete Entries from the log" permission allows users to delete items
      from the log page.

 2. The main administrative page controls the module's settings page:
      admin/config/development/maillog


Troubleshooting / known issues
--------------------------------------------------------------------------------
If the email is not being logged then the site's default email system is not
configured correctly. It is recommended to use the MailSystem module [1] to help
with this, and it may also help to hardcode the use of Maillog by adding these
lines to the end of the site's settings.php file:

// Use Maillog for delivery system emails.
$config['mailsystem.settings']['defaults']['sender'] = 'maillog';
$config['mailsystem.settings']['defaults']['formatter'] = 'maillog';


Related modules
--------------------------------------------------------------------------------
Some similar modules that are available include:

* Reroute Email
  Reroutes outbound emails to a specific destination address.
  http://drupal.org/project/reroute_email


Credits / contact
--------------------------------------------------------------------------------
Maintained by Miro Dietiker [2], Sascha Grossenbacher [3] and Damien McKenna
[4]. Initial port to Drupal 8 by Antonio Sferlazza [5].

The best way to contact the authors is to submit an issue, be it a support
request, a feature request or a bug report, in the project issue queue:
  https://www.drupal.org/project/issues/maillog


References
--------------------------------------------------------------------------------
1: https://www.drupal.org/project/mailsystem
2: https://www.drupal.org/u/miro_dietiker
3: https://www.drupal.org/u/berdir
4: https://www.drupal.org/u/damiemckenna
5: https://www.drupal.org/u/tonnosf
