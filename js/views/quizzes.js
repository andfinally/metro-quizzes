App = window.App || { Models: {}, Collections: {}, Views: {}, Router: {} };

App.Views.Quizzes = Backbone.View.extend({

	tagName: 'table',
	id: 'quiz-list-container',
	className: 'quiz-list table table-striped table-condensed',

	initialize: function (opts) {

		// Tells these methods that "this" should be this view
		_.bindAll(this, "render", "appendToApp", "appendItem");
		var self = this;
		this.router = opts.router;

		this.collection.fetch({
			reset: true,
			success: function(collection, response){
				self.collection.fetchSuccess(collection, response);
			},
			error: this.collection.fetchError
		});

		this.collection.on("add", this.appendItem);
		this.collection.on("reset", this.appendToApp);

	},

	render: function () {
		_(this.collection.models).each(function (item) {
			this.$el.append(this.appendItem(item));
		}, this);
		return this;
	},

	// Append this view to the router's el
	appendToApp: function() {
		this.router.$el.html(this.render().el);
	},

	appendItem: function (model) {
		var quiz = new App.Views.Quiz({model: model, router: this.router});
		return (quiz.render().el);
	}

});
