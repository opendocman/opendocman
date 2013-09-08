opendocman
==========

OpenDocMan - Free PHP Document Management System DMS

OpenDocMan is a web based document management system (DMS) written in PHP designed to comply with ISO 17025 and OIE standard for document management. It features fine grained control of access to files, and automated install and upgrades.

Features

    * Upload files using web browser
    * Control access to files based on department or individual user permissions
    * Track revisions of documents
    * Option to send new and updated files through review process
    * Installs on most web servers with PHP
    * Set up a reviewal process for all new files

NOTE: This is a fork of the original opendocman project where the code has
been modified to provide the following functionality that is not present in
the original:
    * User Defined Fields are available for use in the main file listing (I use this to provide a document number field).
    * Document categories are available for use in the main file listing (I use this to allow the documents to be filtered easily by category.
    * When a file is checked in, it is converted into a PDF.   When a user views the file, they are given the PDF unless they specifically request the native document.
    * I include a 'catcote' theme which uses these features.

The reason for these differences is that I needed the above functionality for the document management system for the new Catcote Academy (http://catcotegb.co.uk/odm).   I will try to get these changes merged into the main opendocman code base when I hear back from the developer.

