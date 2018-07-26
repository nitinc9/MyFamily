<!DOCTYPE html>
<html>
<head>
<title>Facebook Login JavaScript Example</title>
<meta charset="UTF-8">
</head>
<body>
<script>
  // This is called with the results from from FB.getLoginStatus().
  function statusChangeCallback(response) {
    console.log('statusChangeCallback');
    console.log(response);
    // The response object is returned with a status field that lets the
    // app know the current login status of the person.
    // Full docs on the response object can be found in the documentation
    // for FB.getLoginStatus().
    if (response.status === 'connected') {
      // Logged into your app and Facebook.
      testAPI();
    } else {
      // The person is not logged into your app or we are unable to tell.
      document.getElementById('status').innerHTML = 'Please log ' +
        'into this app.';
    }
  }

  // This function is called when someone finishes with the Login
  // Button.  See the onlogin handler attached to it in the sample
  // code below.
  function checkLoginState() {
    FB.getLoginStatus(function(response) {
      statusChangeCallback(response);
    });
  }

  window.fbAsyncInit = function() {
    FB.init({
      appId      : '233351993945655',
      cookie     : true,  // enable cookies to allow the server to access 
                          // the session
      xfbml      : true,  // parse social plugins on this page
      version    : 'v2.8' // use graph api version 2.8
    });

    // Now that we've initialized the JavaScript SDK, we call 
    // FB.getLoginStatus().  This function gets the state of the
    // person visiting this page and can return one of three states to
    // the callback you provide.  They can be:
    //
    // 1. Logged into your app ('connected')
    // 2. Logged into Facebook, but not your app ('not_authorized')
    // 3. Not logged into Facebook and can't tell if they are logged into
    //    your app or not.
    //
    // These three cases are handled in the callback function.

    FB.getLoginStatus(function(response) {
      statusChangeCallback(response);
    });

  };

  // Load the SDK asynchronously
  (function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "https://connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
  }(document, 'script', 'facebook-jssdk'));

  // Here we run a very simple test of the Graph API after login is
  // successful.  See statusChangeCallback() for when this call is made.
  function testAPI() {
    console.log('Welcome!  Fetching your information.... ');
    FB.api('/me', function(response) {
      console.log('Successful login for: ' + response.name);
      document.getElementById('status').innerHTML =
        'Thanks for logging in, ' + response.name + '!';
    });
  }
</script>

<!--
  Below we include the Login Button social plugin. This button uses
  the JavaScript SDK to present a graphical Login button that triggers
  the FB.login() function when clicked.
-->

<fb:login-button scope="public_profile,email" onlogin="checkLoginState();">
</fb:login-button>

<div id="status">
</div>
<?php
 require_once __DIR__ . '/3rdparty/Facebook/autoload.php';
 
 $fb = new Facebook\Facebook([
     'app_id' => '233351993945655',
     'app_secret' => 'e1883a5c94551f28749100e31a17f41f',
     'default_graph_version' => 'v2.2'
 ]);
 $helper = $fb->getCanvasHelper();
 try {
     $accessToken = $helper->getAccessToken();
 }
 catch(Facebook\Exceptions\FacebookResponseException $e) {
     echo 'Auth Error: ' . $e->getMessage();
 }
 catch(Facebook\Exceptions\FacebookSDKException $e) {
     echo 'FB SDK Error: ' . $e->getMessage();
 }
 if (isset($accessToken)) {
     echo "AccessToken found via CanvasHelper";
 }
 /*else if ($helper->getError()){
     echo "AccessToken NOT found via CanvasHelper";
     echo "Error: " . $helper->getError();
     echo "Code: " . $helper->getErrorCode();
     echo "Reason: " . $helper->getErrorReason();
     echo "Description: " . $helper->getErrorDescription();
 }*/
 $fb->setDefaultAccessToken($accessToken);
 $response = $fb->get('/me');
 $user = $response->getGraphUser();
 echo "Welcome! " . $user->getName() . "<br/>";
 echo "User: " . json_encode($user) . "<br/>";
?>
</body>
</html>