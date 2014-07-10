<?php
/*

    This page does Ajax request to WordPress authenticate endpoint,
    saves token in session and passes the encrypted payload
    returned by endpoint to main.php

*/

require '_config.php';

if ( !empty( $_SESSION[ 'logged_in_user' ] ) ) {
    header( 'Location: ' . BASE_URL );
    die();
}

// Hash the timestamp so we can expire requests
$timestamp = time();
$token = md5( $shared_secret . $timestamp );
$_SESSION[ 'quiz-token' ] = $token;

?>
<!DOCTYPE HTML>
<html lang="en-gb">
<head>
    <meta charset="UTF-8">
    <title></title>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/style.css"/>
</head>
<body>

<div class="container">

    <div class="row">

        <div id="content" class="col-md-8 col-md-offset-2">

            <form id="quiz-login" action="<?php echo BASE_URL; ?>" method="post" accept-charset="UTF-8">
                <input id="login-payload" name="login-payload" type="hidden"/>
            </form>

            <script src="http://code.jquery.com/jquery-latest.min.js"></script>

            <script>

                var sendData = {
                    'token':'<?php echo $token ?>',
                    'timestamp':'<?php echo $timestamp ?>'
                };

                $(document).ready(function () {

                    // Get login status from WordPress endpoint
                    $.ajax({
                        type:'POST',
                        url:'<?php echo WORDPRESS_DOMAIN . '/authenticate' ?>',
                        dataType:'jsonp',
                        data:sendData,
                        cache:true,
                        jsonpCallback:'metroAuthCallback'
                    }).done(function (data) {

                        }).fail(function (XHR, status, error) {
                            console.log('ajax error');
                            console.log(error);
                        });
                });

                function metroAuthCallback(data) {
                    console.log('CALLBACK CALLED');
                    console.log(data);
                    if (!data.status) {
                        if (data.status_code == 4) {
                            var html = '<div id="login-notice" class="custom-callout custom-callout-info">';
                            html += '<h3><span class="glyphicon glyphicon-user callout-user"></span>You\'re not logged in to WordPress</h3>';
                            html += '<h4>Please <a href="<?php echo WORDPRESS_DOMAIN . '/wp-admin' ?>" target="_blank">';
                            html += 'log in</a> and ';
                            html += '<a href="javascript:window.location.reload(true);">try again</a></h4></div>';
                            $('#content').append(html);
                        }
                    } else {
                        $('#login-payload').val(data.payload);
                        $('#quiz-login').submit();
                    }
                }

            </script>

        </div>

    </div>

</div>
</body>
</html>
