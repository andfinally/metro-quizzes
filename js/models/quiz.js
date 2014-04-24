App = window.App || { Models: {}, Collections: {}, Views: {}, Router: {} };

App.Models.Quiz = Backbone.Model.extend({

	urlRoot: 'api/quizzes'

});