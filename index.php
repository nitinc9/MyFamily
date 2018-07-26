<?php
/**
 * Description: Main file for the MyFamily app.
 * 
 * @author Nitin Patil
 */
?>
<?php
    require_once __DIR__ . '/core/MyFamily.php';
  
    $app = new MyFamily();
?>
<!DOCTYPE html>
<html>
  <head>
    <title>My Family</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="css/style.css"/>
    <link rel="stylesheet" type="text/css" href="css/Treant/Treant.css"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="js/script.js"></script>
    <script src="js/Treant/raphael.js"></script>
    <script src="js/Treant/Treant.js"></script>
  </head>
  <body>
    <script type="text/javascript">
      window.fbAsyncInit = function() {
        FB.init({
          appId      : '233351993945655',
          cookie     : true, 
          xfbml      : true,
          version    : 'v2.8'
        });

        FB.getLoginStatus(function(response) {
          var content = document.getElementById('content');
          if (response.status == 'connected') {
            // User logged into the app, proceed as normal
          }
          else if (response.status == 'not_authorized') {
            content.innerHTML = '<p>The app needs certain permissions to proceed. Please login again and you will be provided with the exact list of permissions.</p>\n'
                              + '<fb:login-button scope="public_profile,user_friends,user_location,user_gender,user_birthday"></fb:login-button>';
          }
          else {
            content.innerHTML = '<p>Please login to facebook to proceed.</p>\n' + '<fb:login-button scope="public_profile,user_friends,user_location,user_gender,user_birthday"></fb:login-button>';
          }
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
    </script>
    <div id="menu" class="menu">
      <?php $app->showMenu(); ?>
    </div>
    <div id="messageArea" class="messageArea"></div>
    <div id="processing" style="display: none; position: absolute; top: 30%; left: 50%"><img src="images/processing.gif" width="50" height="50"/></div>
    <div id="content" class="content">
      <?php $app->process(); ?>
    </div>
    <script type="text/javascript">
      setupSubMenuDisplayHandlers();
    </script>
  </body>
</html>