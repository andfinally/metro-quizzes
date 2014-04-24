App = window.App || { Models: {}, Collections: {}, Views: {}, Router: {} };

App.Collections.Quizzes = Backbone.Collection.extend({

	model: App.Models.Quiz,

	initialize: function (opts) {
		_.bindAll(this, "fetchSuccess", "fetchError");
		this.url = opts.router.baseUrl + 'api/quizzes';
	},

	fetchSuccess: function (collection, response) {
		console.log(">>>>> Fetch success. Collection models: ", collection);
	},

	fetchError: function (collection, response) {
		throw new Error(">>>>> Fetch error. Didn't get collection from API");
	}

});