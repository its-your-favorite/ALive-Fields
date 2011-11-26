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
The problem is that online our benchmark for Rapid Application Development for database-driven applications is leagues below what we are used to on the desktop environment. 

My benchmark for a great library is that allows you to write source-code that isn't much longer than the description of the solution in plain english. Frequently one wants a page that simply "Lets you edit fields A, B, and C on row 10 in table X," and we're realistically talking 20 lines and more than an hour if we utilize appropriate security, error handling for out-of-range values, and loading/saving functionalit+y. Now if we need an additional page, that's another 20 lines. Other solutions like ColdFusion fix this problem, but have the caveats of not being open-source and aren't live and AJAX powered.

It takes 3 lines (one html, two php) to create and assign an input element to a database field. One more line for a validation. 

That's a moderate improvement, and certainly convenient. But the real benefit is when you put these building blocks together. Suppose you want to make a little PHP program, what would it take to :

* Make a list of names of people in the company who are not administrators, when a user clicks a name, it shows a list of that employee's current projects, and when a project is clicked it shows that project's start date, and description (both of which are editable) all through ajax. 

Using this library, it can be done in twelve lines (3 html, 9 php) and probably twelve minutes. 

*Let's say your boss now decides after the fact that this application should not monitor administrators' projects, even with tampering?

That's one more line of php. Any custom validators you add will each be one line.



Technical Explanation
====================================================================================
This library operates with a client-side javascript set of controls and a server-side set of handlers. However, there is no need to write javascript as the PHP code will generate default javascript. However, you're free to extend/enhance it with your own javascript.