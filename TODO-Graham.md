Graham Jones' ToDo List for OpenDocMan
======================================

I like the simplicity of OpenDocMan compared to other open source offerings,
so intend to use it for the document management system for the school where
I am a governor.

The current (July 2013) version does not do everything that I want for
that application, so will make some changes to it, which the original author
may like to incorporate into the main software.

The changes I intend to make are:

1.  ~~Make the document revision number available to the main document list template (out.tpl) - DONE (07/07/2013)~~
2.  ~~Make the document category available to the main document list template (out.tpl) - DONE (11/07/2013)~~
3.  ~~Make User Defined Fields (UDFs) available to the main document list template (out.tpl) - DONE (11/07/2013)~~
4.  ~~Make it possible to view issued documents if not logged in - default screen when not logged in should be a file list, with a link to the login screen.~~ Not actually done - using guest login to view files instead.
5.  ~~Store the issued version of files as PDFs and serve these when the 'view' action is used.   The native file should only be provided on check-out.~~ Done 30 Aug 2013.
6.  ~~Make it possible to apply filters to the file list screen as part of the URL - out.php?filterStr=xxx - DONE (16/07/2013)~~
7.  Incorporate the PDF generator configuration into the ODM settings system.
8.  Link authorisation to specific revisions of a file, so that view_file.php can return the latest approved version (I think this is a fairly major change to the database structure, so would like to agree how to do this with the original author if possible).

The original author has not picked up on my pull request for these changes, so I will have to decide what to do if he does not want them - I guess I will have to think fo a different name for this fork of the project?


Graham Jones (grahamjones139@gmail.com), August 2013.  
