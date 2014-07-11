<?php

/*

    This page decrypts the payload passed from index.php to see if the user's logged in to WordPress

*/

// To avoid "headers already sent" if we try to redirect
ob_start();

require '_config.php';

?><!DOCTYPE HTML>
<html lang="en-gb">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base id="baseURL" href="<?php echo BASE_URL; ?>"/>
    <title></title>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

    <link rel="stylesheet" type="text/css" href="css/style.css"/>
</head>
<body><?php

if ( empty( $_SESSION[ 'logged_in_user' ] ) ) {

    // User isn't logged in - check if we've come here from index.php form

    $payload = $_POST[ 'login-payload' ];

    if ( empty( $payload ) ) {
        echo 'DIE';
        header( 'Location: login.php' );
        die();
    }

    ob_flush();

    $payload = base64_decode( $payload );
    $payload = trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $key, $payload, MCRYPT_MODE_ECB ) );
    $payload = json_decode( $payload, true );

    function bounce( $error_code ) {
        $html = "\t<script>\n";
        $html .= "\t\tif (console) console.log('" . $error_code . "');\n";
        $html .= "\t\twindow.location='login.php';\n";
        $html .= "\t</script>\n";
        $html .= '</body>';
        $html .= '</html>';
        echo $html;
    }

    // Error codes
    // 1 - request didn't send a token or timestamp
    // 2 - request is too old
    // 3 - request didn't send a valid token
    // 4 - user isn't logged in
    // 5 - no user roles
    // 6 - no user email

    if ( empty( $payload[ 'token' ] ) ) {
        bounce( 1 );
    }

    if ( empty( $payload[ 'timestamp' ] ) ) {
        bounce( 1 );
    }

    // Check authentication request hasn't taken too long
    if ( time() - $payload[ 'timestamp' ] > 120 ) {
        bounce( 2 );
    }

    // Check token sent in encrypted payload matches the one we saved in session
    if ( $payload[ 'token' ] !== $_SESSION[ 'quiz-token' ] ) {
        bounce( 3 );
    }

    // Check user has roles so logged in
    if ( empty( $payload[ 'user_roles' ] ) ) {
        bounce( 5 );
    }

    // Check we have user email
    if ( empty( $payload[ 'user_email' ] ) ) {
        bounce( 6 );
    }

    $_SESSION[ 'logged_in_user' ] = $payload[ 'user_email' ];

}

if  ( !empty( $_SESSION[ 'logged_in_user' ] ) ) {

    // User is logged in

    ob_flush();

    ?>
<div id="nav" class="container">
    <ul class="nav nav-pills">
        <li id="nav-simple-index"><a href="">Quizzes</a></li>
        <li id="nav-add-quiz"><a href="edit">Add quiz</a></li>
    </ul>
</div>

<div id="app" class="container"></div>

<script id="tpl-edit-quiz" type="text/template">
    <input id="input-quiz-id" type="hidden"/>

    <div class="bs-callout bs-callout-info top-notes">
        <h4>Tips</h4>
        <ul>
            <li>You need the same number of possible answers for each question</li>
            <li>For a standard quiz, you need the same number of results as options.</li>
            <li>For a quiz with correct answers, tick the "Scored quiz" checkbox and click the radio button for the
                correct answer to each question.
            </li>
            <li>Enter the results in the same order as the answers they correspond to.</li>
            <li>To use images from WordPress, search for each image, copy the image's full URL from the picture details
                page in WordPress and paste it in the form. Alternatively you can enter the image filename in the form
                and send your image files to us.
            </li>
        </ul>
    </div>
    <h3>General</h3>

    <div id="main" class="form-group">
        <label for="input-quiz-name">Name <span class="required">*</span></label>
        <input id="input-quiz-name" type="text" class="form-control input-sm" placeholder="Name"/>

        <div class="checkbox">
            <label>
                <input id="input-score-correct" type="checkbox"> Scored quiz
            </label>
        </div>
    </div>
    <h3>Questions</h3>
    <section id="questions"></section>
    <div class="form-group spacer">
        <button id="add-question" class="btn btn-default btn-sm" type="button">+ Question</button>
    </div>
    <h3 class="separator">Results</h3>
    <section id="results"></section>
    <div class="form-group">
        <button id="add-result" class="btn btn-default btn-sm" type="button">+ Result</button>
    </div>
    <div class="save-cancel form-group">
        <a id="input-download" download target="_blank" class="hide btn btn-download btn-default btn-sm" type="button">Download</a>

        <div id="spinner" class="is-hidden"></div>
        <button id="input-save" class="btn btn-success input-save btn-sm" type="button">Save</button>
    </div>
</script>

<script id="tpl-error" type="text/template">
    <div class="view-error">
        <h1>Error</h1>

        <p>Sorry, there was an error:</p>

        <p class="error"><%= message %></p>
        <a href="/backbone-json">Home</a>
    </div>
</script>

<script id="tpl-question" type="text/template">
    <div class="question-group input-del-group">
        <label class="full-label" for="quest-<%= id %>">Question <span class="required">*</span></label>
        <input id="quest-<%= id %>" type="text" class="question form-control input-sm" placeholder="Question"
               value="<%= title %>" data-id="<%= id %>"/>
        <button class="btn btn-default glyphicon glyphicon-remove question-remove btn-xs" data-question="<%= id %>"
                tabindex="-1"></button>
    </div>
    <div class="image-group form-input-group">
        <label class="full-label" for="img-<%= id %>">Image</label>
        <input id="img-<%= id %>" type="text" class="image form-control input-sm" placeholder="Image"
               value="<%= image %>" data-questionID="<%= id %>"/>
        <span class="help-block">Example <code>1.jpg</code>. Ideally a 650 x 390 jpeg, but in any case no more than 500 high.</span>
        <label class="full-label" for="img-credits-<%= id %>">Image credits</label>
        <input id="img-credits-<%= id %>" type="text" class="image-credits form-control input-sm"
               placeholder="Image credits" value="<%= imageCredits %>"/>
    </div>
</script>

<script id="tpl-option" type="text/template">
    <div class="option-container">
        <label class="full-label" for="opt-<%= option . id %>">Answer (text)<span class="required">*</span></label>

        <div class="option-group input-group input-del-group" id="option-group-<%= option . id %>">
			<span class="is-answer-group input-group-addon">
				<% if ( isAnswer ) { %>
                <input type="radio" name="is-answer-<%= questionID %>" value="<%= option . id %>" checked="checked">
                <% } else { %>
                <input type="radio" name="is-answer-<%= questionID %>" value="<%= option . id %>">
                <% } %>
			</span>
            <input id="opt-<%= option . id %>" type="text" class="option input-del form-control input-sm"
                   placeholder="Answer (text)" value="<%= option . title %>" data-id="<%= option . id %>"/>
			<span class="input-group-btn">
				<button class="btn btn-default glyphicon glyphicon-remove option-remove btn-sm"
                        data-question="<%= questionID %>" tabindex="-1"></button>
			</span>
        </div>
    </div>
</script>

<script id="tpl-img-option" type="text/template">
    <div class="option-container">
        <label class="full-label" for="opt-<%= option . id %>">Answer (image)<span class="required">*</span></label>

        <div class="option-group input-group input-del-group" id="option-group-<%= option . id %>">
			<span class="is-answer-group input-group-addon">
				<% if ( isAnswer ) { %>
                <input type="radio" name="is-answer-<%= questionID %>" value="<%= option . id %>" checked="checked">
                <% } else { %>
                <input type="radio" name="is-answer-<%= questionID %>" value="<%= option . id %>">
                <% } %>
			</span>
            <input id="opt-<%= option . id %>" type="text" class="option img-option input-del form-control input-sm"
                   placeholder="Answer (image URL)" value="<%= option . image %>" data-id="<%= option . id %>"/>
			<span class="input-group-btn">
				<button class="btn btn-default glyphicon glyphicon-remove option-remove btn-sm"
                        data-question="<%= questionID %>" tabindex="-1"></button>
			</span>
        </div>
        <span class="help-block">200x200 if you're using 6, otherwise 275x275.</span>

        <div class="image-group form-input-group">
            <label class="full-label" for="opt-<%= option . id %>-img-credits">Image Credits</label>
            <input id="opt-<%= option . id %>-img-credits" type="text" class="img-option-credits form-control input-sm"
                   placeholder="Answer Image Credits" value="<%= option . imageCredits %>"/>
        </div>
    </div>
</script>

<script id="tpl-add-option-btn" type="text/template">
    <div class="option-btn-container form-group" id="option-btn-<%= questionID %>">
        <button class="add-option btn btn-default btn-sm" type="button" data-question="<%= questionID %>">+ Answer
            (text)
        </button>
        <button class="add-img-option btn btn-default btn-sm" type="button" data-question="<%= questionID %>">+ Answer
            (image)
        </button>
    </div>
</script>

<script id="tpl-result" type="text/template">
    <div class="result-container form-group" data-result="<%= id %>">
        <div class="result-group input-del-group">
            <label class="full-label" for="result-<%= id %>-title">Title <span class="required">*</span></label>
            <input id="result-<%= id %>-title" type="text" class="result-title input-del form-control input-sm"
                   placeholder="Title" value="<%= title %>"/>
            <button class="btn btn-default glyphicon glyphicon-remove result-remove btn-xs" data-result="<%= id %>"
                    tabindex="-1"></button>
        </div>
        <div class="result-group form-input-group">
            <label class="full-label" for="result-<%= id %>-before-title">Before Title</label>
            <input id="result-<%= id %>-before-title" type="text" class="form-control input-sm"
                   placeholder="Before Title" value="<%= beforeTitle %>"/>
            <span class="help-block">If you want to start the result with something other than "You're..."</span>
        </div>
        <div class="result-threshold-group result-group form-input-group">
            <label class="full-label" for="result-<%= id %>-threshold">Threshold <span class="required">*</span></label>
            <input id="result-<%= id %>-threshold" type="text" class="form-control input-sm" placeholder="Threshold"
                   value="<%= threshold %>"/>
            <span class="help-block">User has to score at least this to get this result</span>
        </div>
        <div class="result-group form-input-group">
            <label class="full-label" for="result-<%= id %>-text">Text <span class="required">*</span></label>
            <input id="result-<%= id %>-text" type="text" class="form-control input-sm" placeholder="Text"
                   value="<%= text %>"/>
        </div>
        <div class="result-group form-input-group">
            <label class="full-label" for="result-<%= id %>-fb-title">Facebook Title <span
                class="required">*</span></label>
            <input id="result-<%= id %>-fb-title" type="text" class="form-control input-sm" placeholder="Facebook Title"
                   value="<%= facebookName %>"/>
        </div>
        <div class="result-group form-input-group">
            <label class="full-label" for="result-<%= id %>-fb-text">Facebook Text</label>
            <input id="result-<%= id %>-fb-text" type="text" class="form-control input-sm" placeholder="Facebook Text"
                   value="<%= facebookDescription %>"/>
        </div>
        <div class="result-group form-input-group">
            <label class="full-label" for="result-<%= id %>-twitter-text">Twitter Text <span
                class="required">*</span></label>
            <input id="result-<%= id %>-twitter-text" type="text" class="form-control input-sm"
                   placeholder="Twitter Text" value="<%= twitterText %>"/>
        </div>
        <div class="image-group form-input-group">
            <label class="full-label" for="result-<%= id %>-img">Image <span class="required">*</span></label>
            <input id="result-<%= id %>-img" type="text" class="form-control input-sm" placeholder="Image"
                   value="<%= image %>"/>
            <span class="help-block">Example <code>result-1.jpg</code>. Ideally 450 x 450 square jpeg</span>
        </div>
        <div class="image-group form-input-group">
            <label class="full-label" for="result-<%= id %>-share-img">Image for Sharing</label>
            <input id="result-<%= id %>-share-img" type="text" class="form-control input-sm"
                   placeholder="Image for Sharing" value="<%= shareImage %>"/>
            <span class="help-block">Example <code>result-1-share.jpg</code>. Jpeg at least 1200 pixels wide. If you don't specify one we'll use the main image.</span>
        </div>
        <div class="image-group form-input-group">
            <label class="full-label" for="result-<%= id %>-img-credits">Image Credits</label>
            <input id="result-<%= id %>-img-credits" type="text" class="form-control input-sm"
                   placeholder="Image Credits" value="<%= imageCredits %>"/>
        </div>
    </div>
</script>

<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.5.2/underscore-min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/backbone.js/1.0.0/backbone-min.js"></script>

<script src="js/routers/router.js"></script>
<script src="js/models/quiz.js"></script>
<script src="js/collections/quizzes.js"></script>
<script src="js/views/quiz.js"></script>
<script src="js/views/quizzes.js"></script>
<script src="js/views/editQuiz.js"></script>
<script src="js/views/error.js"></script>
<script src="js/app.js"></script>
</body>
</html><?php

} // End of logged in bit

?>
