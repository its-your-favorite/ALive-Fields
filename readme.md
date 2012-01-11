#AcControls
Copyright Alex Rohde 2011. Released under GPL v2
* * *
AcControls is a PHP library for binding database fields to HTML input elements. This is done in a live way with AJAX and provides a level of immediate interactivity usually only seen in offline database applications like MS Access.

The library is designed to allow database applications to be made online with only a handful of code.

Live Example:
http://alexrohde.com/ALive%20Controls/example.php

Github wiki:
https://github.com/anfurny/ALive-Fields/wiki

Example snippet of code plz?
----------------------------------------------------------------
	$users_articles = new AcListSelect("title", "articles", "articleID", 1, 0);
	$users_articles->bind("articles");
	$users_articles->set_dependent_fields(array($article_content));

The above three lines will take a select element with the id "articles" and populate it with each row's "title" field from the table articles. The value on each row will be the corresponding articleID. The third line lets this control communicate with another control to request loading related values.

What problem do I solve?
----------------------------------------------------------------
The problem is that online our benchmark for Rapid Application Development for database-driven applications is leagues below what we are used to on the desktop environment. This library offers a solution, which can be extended to the particular needs to your project.

My standard for a well-developed library is that allows you to write source-code that isn't much longer than the description of the solution in plain english. Frequently one wants a page that simply "Lets you edit fields A, B, and C on row Y in table X," and we're realistically talking 20 lines and more than an hour if we utilize appropriate security, error handling for out-of-range values, and loading/saving functionality. Now if we need an additional page, that's another 20 lines. Other solutions like ColdFusion fix this problem, but have the caveats of not being open-source and aren't live and AJAX powered.

It takes 3 lines (one html, two php) to create and assign an html input element to a database field. One more line for a validation. 

That's a moderate improvement, and certainly convenient. But the real benefit is when you put these building blocks together. Suppose you want to make a little PHP program, what would it take to :

* Make a list of names of people in the company who are not administrators, when a user clicks a name, show a list of that employee's current projects, and when a project is clicked show that project's start date, and description (both of which are editable) all through ajax. 

Using this library, it can be done in twelve lines (3 html, 9 php) and probably twelve minutes. 

*Let's say your boss now decides after the fact that this application should not monitor administrators' projects, even with tampering?

That's one more line of php. Any custom validators you add will each be one line.



Technical Explanation
----------------------------------------------------------------
This library operates with a client-side javascript set of controls and a server-side set of handlers. However, there is no need to write javascript as the PHP code will generate default javascript. However, you're free to extend/enhance it with your own javascript.



COPYRIGHT
----------------------------------------------------------------
 * ALive Fields V.46
 * http://alexrohde.com/
 *
 * Includes jquery.js, jqueryUI.js[optional]
 * http://jquery.com/ , http://jqueryui.com
 * Copyright 2011, John Resig
 *
 * Copyright 2011, Alex Rohde
 * Licensed under the GPL Version 2 license.
 

