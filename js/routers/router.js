App = window.App || { Models: {}, Collections: {}, Views: {}, Router: {} };

App.Router = Backbone.Router.extend({

	id: "router",

	routes: {
		"": "index",
		"edit(/:id)": "edit"
	},

	$el: $('#app'),
	navItems: $('#nav').find('li'),

	initialize: function() {
		_.bindAll(this, "edit", "onModelFetchError", "showError");
		this.baseUrl = $('#baseURL').attr('href');
		this.collection = new App.Collections.Quizzes({router: this});
	},

	index: function () {
		this.listView = new App.Views.Quizzes({router: this, collection: this.collection});
		this.navItems.removeClass('active');
		$('#nav-simple-index').addClass('active');
	},

	edit: function (id) {
		new App.Views.EditQuiz({modelID: id, router: this});
		this.navItems.removeClass('active');
		$('#nav-add-quiz').addClass('active');
	},

	onModelFetchError: function (model, response, options) {
		this.showError(response.statusText);
	},

	showError: function(msg) {
		this.error = new App.Views.Error({router: this, message: msg});
	}

});

$.fn.exists = function () {
    return this.length !== 0;
}
