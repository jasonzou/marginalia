# Introduction #

Marginalia includes some instrumentation and special features for use by developers.  Not all of them are obvious in the user interface.

# Marginalia Direct #

**I have not found Marginalia Direct useful for some time, so I am not supporting it in newer releases.  If it again becomes useful it can be updated to work.  The following description applies to some older releases.**

Press Shift-Ctrl-M from within an annotated page to bring up the Marginalia Direct console.  This uses the same HTTP API as the more friendly user interface (with its highlighting, margin notes, and so on), but does not require the presence or correctness of the annotatiosn or the annotated document.  So if annotations disappear because of bad range calculations (for example) or the absence of a document, you can still get at them here.

The console allows annotations to be retrieved by URL and user name.  They can then be modified or deleted.  Any value is permitted in the fields, but all security and access restrictions implemented on the server apply.  Thus certain changes (e.g. invalid access values, unsafe URL protocols, etc.) may silently fail.

Implementations adding additional fields should be OK so long as the server can derive the correct value of those fields (e.g. in the Moodle version, the message ID field must be updated when the URL changes).