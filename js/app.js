// http://ozkatz.github.io/avoiding-common-backbonejs-pitfalls.html
// http://www.josscrowcroft.com/2012/code/htaccess-for-html5-history-pushstate-url-routing/

App = window.App || { Models: {}, Collections: {}, Views: {}, Router: {} };

/*
	API returns {action string, success boolean, ID num, data array}

	GET http://local5/books list
	GET http://local5/books/1 single item
	POST http://local5/books {title, author} add
	PUT http://local5/books/1 {title, author} update
	DELETE http://local5/books/1 delete
 */

Backbone.pubSub = _.extend({}, Backbone.Events);

var app = new App.Router();
Backbone.history.start({ pushState: true, root: "/metro-quizzes/" });
