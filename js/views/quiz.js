App = window.App || { Models: {}, Collections: {}, Views: {}, Router: {} };

App.Views.Quiz = Backbone.View.extend({

	tagName: 'tr',

	events: {
		"click .delete": "delete"
	},

	initialize: function (opts) {
		_.bindAll(this, "render");
		this.router = opts.router;
		this.listenTo(this.model, "destroy", this.remove);
	},

	render: function () {
		var html = '<td><a href="' + this.router.baseUrl + 'edit/' + this.model.get('id') + '">' + this.model.get('id') + ': ' + this.model.get('name') + '</a></td>';
		html += '<td><a href="#" class="delete btn btn-default btn-xs"><span class="glyphicon glyphicon-remove"></span></a><a href="edit/' + this.model.get('id') + '" class="btn btn-default btn-xs edit"><span class="glyphicon glyphicon-pencil"></span>Edit</a></td>';
		this.$el.html(html);
		return this;
	},

	delete: function (e) {
		e.preventDefault();
		if (confirm('Are you sure you want to delete this entire quiz and all its questions?')) this.model.destroy();
	},

	edit: function (e) {
		e.preventDefault();
		this.router.trigger("EditQuiz");
	}

});