What is AcControls? 
====================================================================================
AcControls is a php library for binding database fields to html input elements. This is done in a live way with ajax and provides a level of immediate interactivity usually only seen in offline database applications like access.

The library is designed to allow database applications to be made online with only a handful of code.

Live Example:
http://alexrohde.com/ALive%20Controls/example.php


Github wiki:
https://github.com/anfurny/ALive-Fields/wiki



What problem do I solve?
===================================================================================
The problem is that online our benchmark for Rapid Application Development is leagues below what we are used to on the desktop environment. Though I can solve that for the general case, I'm presenting an open-source solution that fixes this for database-driven applications. 

My benchmark for a great library is that the source-code that utilizes it isn't much longer than the definition of the problem in plain english. Frequently one wants a page that simply "Lets you edit fields A, B, and C on row 10 in table X," and though this can be done we're probably talking 20 lines if we utilize appropriate security, error handling for out-of-range values, and loading/saving functionality. Now if we need an additional page, that's another 20 lines. Solutions like ColdFusion fix this with a simple way to assign a database field to an html input element, but aren't open-source, aren't php, and aren't live and AJAX powered.

It takes 3 lines (one html, two php) to create and assign an input element to a database field. One more line for a validation. 

That's a moderate improvement, and certainly convenient. But the real benefit is when you put these building blocks together. Suppose you want to make this little PHP program, what would it take? :

* Make a list of names of people in the company who are not administrators, when a user clicks a name, it shows a list of that employee's current projects, and when a project is clicked it shows that project's start date, and description (both of which are editable) all through ajax. 

And now in twelve lines (3 html, 9 php) you can make a simple page. 

*Let's say your boss now decides after the fact that this application should not monitor administrators' projects, even with tampering?

That's one more line of php. Any custom validators you add will each be one line.



Technical Explanation
====================================================================================
This library operates with a client-side javascript set of controls and a server-side set of handlers. However, there is no need to write javascript as the PHP code will generate default javascript. However, you're free to extend/enhance it with your own javascript.