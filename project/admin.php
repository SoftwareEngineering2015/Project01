<!DOCTYPE html>
<html>
<head>
<?php
  require_once( "template_class.php");       // css and headers
  $H = new template( "Administration" );
  $H->show_template( );


  if(($_SESSION['login_user']) != "admin"){
    header("location: home.php");
    exit();
  }

?>
</head>
<body>
  <div>
    <b id="welcome"> Welcome <?php echo $login_session; ?>!</b>
  </div>

  <div>
  <br/>
    <a href="communitymap.php" class="col-xs-8 col-xs-offset-2 btn btn-primary btn-lg" style="font-size: 100%; height: 20%;">Go to <br/> CommunIT Map</a>
  </div>

</body>
</html>