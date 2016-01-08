Moodle is a difficult platform, because it has no concept of a cross-cutting plug-in like Marginalia.  I considered the following plug-in hooks:

## As an Activity Module ##

An activity module seems to be the obvious choice:  it is a coherent package of functionality, including several custom pages.  This would capture the summary and tags pages quite nicely.  However, it has two fatal flaws.  First, the administrator would have to add an annotation instance to a course before students could use it.  Second, and more seriously, the module is expected to be associated with the course - to the point where the database table has a courseid column.  The documentation seems to imply, furthermore, that when the course is deleted that data would go with it.  This is just wrong.  Annotations belong to whoever creates them, not to a course:  it should be impossible for anyone other than the creator to delete an annotation.  It makes good sense to summarize a user's annotations for all courses, not just one.

Activity modules don't provide any way to insert content into other pages - but then there are no mechanisms that do, other than blocks, which turn out to be able only to insert isolated content into a handful of pages (not the forum posts I need).

(This points to a more general problem with Moodle:  the course is the central object in the system.  Everything is oriented around courses.  Moodle allows teachers to choose from an assortment of tools to provide lessons for students.  What it does not do is provide tools for students to manage their own learning across courses and over time.  Annotations are clearly a tool for doing just that:  the annotations and citations I collect build up over a period of years, and are oriented around my learning interests, not the structure of any formal system that may have provided them.  Moodle, I would say, is teacher-centered, not learner-centered, and this orientation is reflected at every level in the implementation code.)

## In the Local Directory ##

The local directory can be used to add arbitrary code.  However, it is essentially undocumented, and Marginalia would potentially conflict with anything else placed in local (they would have to share lang directories - worse, they would have to share the same install.xml file to create their databases).  While experimenting I was unable to get Moodle to create my database when Marginalia was installed under local.  And the system is incomplete:  it provides a hook for language strings, but not for help files, for example (go figure).

## As a Block ##

I decided to deploy Marginalia as a block.  Or rather, with a block, as Moodle does not provide the hooks necessary to do mots of what Marginalia needs.  The main benefit to the block is that it can easily create and update the database table, which is the main feature I am pursuing.  It can also display links to summary and tag pages, which is useful (though unfortunately the course orientation requires the administrator will have to add the block to courses for that feature to work).

Another problem with blocks is that they don't display in individual activity pages, such as in the forum.  Nearly as bad, even when a block does display there are no mechanisms provided for getting Javascript and CSS into the head section of the HTML, and they don't provide any way to access context other than the current course (such as the current discussion forum - which I suppose is obvious since there's no support for display there anyway).

So this solution too is quite inadequate, but it looks like the best for now.