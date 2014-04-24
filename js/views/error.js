App = window.App || { Models: {}, Collections: {}, Views: {}, Router: {} };

App.Views.Error = Backbone.View.extend({

	template: _.template($('#tpl-error').html()),

	initialize: function(opts) {
		this.message = opts.message;
		this.router = opts.router;
		this.render();
	},

	render: function() {
		this.router.$el.html(this.template({message: this.message}));
		return this;
	}

});