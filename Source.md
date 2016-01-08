# Repository Structure #

Marginalia consists of several sub-projects for Moodle, OJS, the demo, and so on.  Each project has its own code and also makes use of shared Marginalia code.  For example, to run the demo you will need to fetch `demo`, `marginalia-lib`, and `marginalia-php`.  The INSTALL.html file in the demo contains specific instructions.  See the Browse link under the tabs above to view the sub-projects in the repository.

The sub-projects are:

  * `marginalia-lib` - the core Javascript client-side code
  * `marginalia-php` - support code for PHP server-side implementations
  * `moodle` - the Moodle implementation
  * `demo` - the demo application
  * `ojs` - the Open Journal Systems plugin

The current versions of marginalia-lib, marginalia-php and moodle are for the Marginalia 2.x release series for Moodle 2.x.  Snapshots of Moodle M 1.0 for Moodle 1.9 can be found here:

  * `marginalia-lib/tags/release-1.0` - corresponds to Marginalia M 1.0 for Moodle 1.9
  * `marginalia-php/tags/release-1.0` - corresponds to Marginalia M 1.0 for Moodle 1.9
  * `moodle/tags/release-1.0` - Marginalia M 1.0 for Moodle 1.9

# Command-Line Access #

If you plan to make changes, check out the code as yourself using HTTPS.  For example, to check out the demo do the following:

> `svn checkout https://marginalia.googlecode.com/svn/demo/trunk/ demo --username` _**your-google-username-here**_

When prompted, enter your generated [googlecode.com password](http://code.google.com/hosting/settings).

Use this command to anonymously check out a read-only working copy of the latest project source code:

> `svn checkout http://marginalia.googlecode.com/svn/demo/trunk/ demo-read-only`

# GUI and IDE Access #

This project's Subversion repository may be accessed using many different
[client programs and plug-ins](http://subversion.tigris.org/links.html#clients).
See your client's documentation for more information.