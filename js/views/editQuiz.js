App = window.App || { Models: {}, Collections: {}, Views: {}, Router: {} };

App.Views.EditQuiz = Backbone.View.extend({

	id: "form-edit-quiz",
	tagName: "form",
	questions: [],
	optionIDs: [],
	questionIDs: [],
	resultIDs: [],
	formData: {},
	spinner: null,
	scoreCorrectAnswers: false,

	events: {
		"click #input-save": 			"save",
		"click #add-question": 			"addQuestion",
		"click .add-option": 			"addOption",
		"click .question-remove": 		"deleteQuestion",
		"click .option-remove": 		"deleteOption",
		"click .result-remove": 		"deleteResult",
		"click #add-result":			"addResult",
		"click #input-score-correct":	"onClickScoredQuiz"
	},

	template: 				_.template($('#tpl-edit-quiz').html()),
	questionTemplate: 		_.template($('#tpl-question').html()),
	optionTemplate: 		_.template($('#tpl-option').html()),
	resultTemplate: 		_.template($('#tpl-result').html()),
	addOptionBtnTemplate: 	_.template($('#tpl-add-option-btn').html()),

	initialize: function (opts) {
		_.bindAll(
				this, "save", "render", "getFormData", "renderQuestion", "addQuestion", "addOption", "addResult", "getNewQuestionID", "getNewOptionID", "getNewResultID", "addNewModel", "updateModel", "onAddNew", "onSave", "onError", "hideSpinner", "onClickScoredQuiz");
		this.router = opts.router;
		this.self = this;
		if (opts.modelID) {
			this.model = new App.Models.Quiz({id: opts.modelID});
			this.model.fetch({ success: this.render });
		} else {
			this.render();
		}
	},

	render: function (model, response) {

		// Add the basic view containers to page
		this.$el.html(this.template);
		this.router.$el.html(this.$el);

		// Has this function been called after fetch?
		if (model) {
			$('#input-quiz-id').val(this.model.get('id'));
			$('#input-quiz-name').val(this.model.get('name'));
			$('#input-download')
				.attr('href', this.router.baseUrl + 'api/data/' + this.model.get('id') + '.json')
				.removeClass('hide');
			if (this.model.get('scoreCorrectAnswers') == 1) {
				$('#input-score-correct').prop('checked', true);
				$('#input-quiz-type').val(1);
				this.$el.addClass('scored-quiz');
			}
			var questions = this.model.get('questions');
			if (questions) {
				var questionsHTML = '';
				for (var i = 0; i < questions.length; i++) {
					questionsHTML += this.renderQuestion(questions[i]);
				}
				$('#questions').html(questionsHTML);
			}
			var results = this.model.get('results');
			if (results) {
				var resultsHTML = '';
				for (var i = 0; i < results.length; i++) {
					results[i].beforeTitle = results[i].beforeTitle || '';
					results[i].image = results[i].image || '';
					results[i].imageCredits = results[i].imageCredits || '';
					results[i].shareImage = results[i].shareImage || '';
					results[i].threshold = results[i].threshold || 0;
					resultsHTML += this.resultTemplate(results[i]);
				}
				$('#results').html(resultsHTML);
			}			
		}

		this.delegateEvents();
		return this;
	},

	renderQuestion: function (question) {

		question.image = question.image || '';
		question.imageCredits = question.imageCredits || '';

		var html = '<div class="question-container form-group panel panel-default" id="quest-cont-' + question.id + '">';
		html += this.questionTemplate(question);

		if (question.options) {
			html += '<div class="options" id="option-container-' + question.id + '">';
			_.each(question.options, function(option) {
				html += this.optionTemplate({
					'scoreCorrectAnswers':	this.scoreCorrectAnswers,
					'questionID': 			question.id,
					'option': 				option,
					'isAnswer':				option.isAnswer || false
				})
			}, this)
			html += '</div>';
		}

		html += this.addOptionBtnTemplate({'questionID': question.id});
		html += '</div>';

		return html;
	},

	addQuestion: function (e) {
		var q = {
			id: 			this.getNewQuestionID(),
			title: 			'',
			image: 			'',
			imageCredits: 	''
		};
		if (q.id === -1) return false;
		this.$el.find('#questions').append(this.renderQuestion(q));
		$('#quest-' + q.id).focus();
	},

	addOption: function (e) {
		var newOption = {
			id: this.getNewOptionID()
		};
		var questionID = $(e.target).data('question');
		var optionContainer = $('#option-container-' + questionID);
		var optionObj = {
			'scoreCorrectAnswers': 	this.scoreCorrectAnswers,
			'questionID': 			questionID,
			'option': 				newOption,
			'isAnswer': 			false
		};
		if (optionContainer.exists()) {
			var html = this.optionTemplate(optionObj);
			optionContainer.append(html);
		} else {
			var html = '<div class="options" id="option-container-' + questionID + '">';
			html += this.optionTemplate(optionObj);
			html += '</div>';
			$('#option-btn-' + questionID).before(html);
		}
		$('#opt-' + newOption.id).focus();
	},

	addResult: function(e) {
		var resultID = this.getNewResultID();
		if (resultID < 0) return;
		var newResult = {
			id: resultID,
			beforeTitle: '',
			title: '',
			text: '',
			facebookName: '',
			facebookDescription: '',
			twitterText: '',
			image: '',
			imageCredits: '',
			shareImage: '',
			threshold: 0
		};
		$('#results').append(this.resultTemplate(newResult));
		$('#result-' + resultID + '-title').focus();
	},

	deleteQuestion: function (e) {
		e.preventDefault();
		$(e.target).closest('.question-container').remove();
	},

	deleteOption: function (e) {
		e.preventDefault();
		$(e.target).closest('.option-container').remove();
	},

	deleteResult: function (e) {
		e.preventDefault();
		$(e.target).closest('.result-container').remove();
	},

	getNewQuestionID: function () {

		var newQuestionID;

		if (!this.model) {
			alert('Please enter the quiz name and save first.');
			return -1;
		}

		if (this.questionIDs.length === 0) {
			var questions = this.model.get('questions');

			// No question ids in the model yet
			if (!questions || questions.length === 0) {
				this.questionIDs.push(1);
				return 1;
			}

			for (var i = 0; i < questions.length; i++) {
				this.questionIDs.push(questions[i].id);
			}
		}

		newQuestionID = parseInt(_.max(this.questionIDs)) + 1;
		this.questionIDs.push(newQuestionID);
		return newQuestionID;

	},

	getNewOptionID: function () {

		var newOptionID;

		if (this.optionIDs.length === 0) {
			var questions = this.model.get('questions');
			_.each(questions, function (question) {
				if (question.options) {
					_.each(question.options, function (option) {
						this.optionIDs.push(option.id);
					}, this);
				}
			}, this);

			// No option ids in the model yet
			if (this.optionIDs.length === 0) {
				this.optionIDs.push(1);
				return 1;
			}
		}

		newOptionID = parseInt(_.max(this.optionIDs)) + 1;
		this.optionIDs.push(newOptionID);
		return newOptionID;

	},

	getNewResultID: function() {

		if (!this.model) {
			alert('Please enter the quiz name and save first.');
			return -1;
		}

		if (this.resultIDs.length === 0) {
			var results = this.model.get('results');

			// No result ids in the model yet
			if (!results || results.length === 0) {
				this.resultIDs.push(1);
				return 1;
			}

			for (var i = 0; i < results.length; i++) {
				this.resultIDs.push(results[i].id);
			}
		}

		var newResultID = parseInt(_.max(this.resultIDs)) + 1;
		this.resultIDs.push(newResultID);
		return newResultID;
	},

	getFormData: function () {
		var self = this;
		this.formData.id = this.$el.find('#input-quiz-id').val();
		this.formData.name = this.$el.find('#input-quiz-name').val();
		var scoreCorrectAnswers = this.formData.scoreCorrectAnswers = this.$el.find('#input-score-correct').is(':checked');
		var questionContainers = this.$el.find('.question-container');
		if (questionContainers) {
			this.formData.questions = [];
			$.each(questionContainers, function(index, el) {
				var questionContainer = $(el),
					question = questionContainer.find('.question'),
					questionObj = {id: question.data('id'), title: question.val()},
					image = questionContainer.find('.image').val(),
					imageCredits = questionContainer.find('.image-credits').val();
				if (image) {
					questionObj.image = image;
				}
				if (imageCredits) {
					questionObj.imageCredits = imageCredits;
				}
				var options = questionContainer.find('.option');
				if (options.exists()) {
					questionObj.options = [];
					var isAnswer = -1;
					if (scoreCorrectAnswers) {
						isAnswer = parseInt(questionContainer.find('input[name=is-answer-' + question.data('id') + ']:checked').val());
					}
					$.each(options, function(index, el){
						var optionInput = $(el);
						var optionObj = {
							id: optionInput.data('id'),
							title: optionInput.val()
						};
						if (isAnswer == optionObj.id) {
							optionObj.isAnswer = true;
						}
						questionObj.options.push(optionObj);
					});
				}
				self.formData.questions.push(questionObj);
			});
		}
		var resultContainers = this.$el.find('.result-container');
		if (resultContainers) {
			this.formData.results = [];
			$.each(resultContainers, function (index, el) {
				var resultContainer = $(el),
					id = resultContainer.data('result'),
					resultObj = {
						id: 					id,
						title: 					resultContainer.find('#result-' + id + '-title').val(),
						text: 					resultContainer.find('#result-' + id + '-text').val(),
						image: 					resultContainer.find('#result-' + id + '-img').val(),
						facebookName: 			resultContainer.find('#result-' + id + '-fb-title').val(),
						facebookDescription: 	resultContainer.find('#result-' + id + '-fb-text').val(),
						twitterText: 			resultContainer.find('#result-' + id + '-twitter-text').val()
					};
				var beforeTitle = resultContainer.find('#result-' + id + '-before-title').val();
				if (beforeTitle) resultObj.beforeTitle = beforeTitle;
				var shareImage = resultContainer.find('#result-' + id + '-share-img').val();
				if (shareImage) resultObj.shareImage = shareImage;
				var imageCredits = resultContainer.find('#result-' + id + '-img-credits').val();
				if (imageCredits) resultObj.imageCredits = imageCredits;
				if (scoreCorrectAnswers) {
					var threshold = parseInt(resultContainer.find('#result-' + id + '-threshold').val());
					resultObj.threshold = threshold;
				}
				self.formData.results.push(resultObj);
			});
		}
		console.log(self.formData);
	},

	addNewModel: function () {
		this.model = this.router.collection.create({name: this.formData.name, scoreCorrectAnswers: this.formData.scoreCorrectAnswers}, {
			wait: true,
			error: this.onError,
			success: this.onAddNew
		});
	},

	updateModel: function () {
		if (!this.model) {
			this.model = new App.Models.Quiz({id: this.formData.id});
			console.log('Created new model', this.model);
		}
		this.model.save(this.formData, {
			error: this.onError,
			success: this.onSave
		});
	},

	save: function (e) {
		$('#spinner').removeClass('is-hidden');
		e.preventDefault();
		if (!$('#input-quiz-name').val()) {
			alert('Please enter a name before saving.');
			this.hideSpinner();
			return false;
		}
		this.getFormData();
		if (!this.formData.id) {
			this.addNewModel();
		} else this.updateModel();
		return true;
	},

	onAddNew: function (model, response, options) {
		this.hideSpinner();
		console.log('EditQuiz new added');
		this.router.navigate('edit/' + this.model.get('id'), {trigger: false, replace: true});
		this.$el.find('#input-quiz-id').attr('value', this.model.get('id'));
	},

	onSave: function (model, response, options) {
		this.hideSpinner();
		console.log('EditQuiz save success');
	},

	onError: function (model, response, options) {
		this.hideSpinner();
		console.log('EditQuiz save error', response);
	},

	hideSpinner: function() {
		setTimeout(function () {
			$('#spinner').addClass('is-hidden')
		}, 750);
	},

	onClickScoredQuiz: function(e) {
		if (e.target.checked) {
			this.scoreCorrectAnswers = true;
			this.$el.addClass('scored-quiz');
		} else {
			this.scoreCorrectAnswers = false;
			this.$el.removeClass('scored-quiz');
		}
	}

});