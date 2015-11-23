<?php

if (!isset($_GET['residence'])){
  header("location: admin.php");
  exit;
}
 
  $residence = $_GET['residence'];

  require_once( "template_class.php");       // css and headers
  $H = new template( "Administration" );
  $H->show_template( );

  if(($_SESSION['login_user']) != "admin"){
    header("location: home.php");
    exit();
  }

  $error = "";

    if (isset($_GET['error']) && $_GET['error'] == 'space') {
      $error = "<span style='color:red;'> Residence name cannot have a space in it. </span><br />";
    }
    if (isset($_GET['error']) && $_GET['error'] == 'exists') {
      $error = "<span style='color:red;'> Residence name already exists. </span><br />";
    }

  $P = new manage_db;
  $P->connect_db();


$sql_default_color = "DESCRIBE head_residents";
      $P->do_query($sql_default_color);
      $default_color_result = mysql_query($sql_default_color); 
      while ($row = mysql_fetch_assoc($default_color_result))
        {
          if ($row['Field'] == 'pin_color') { 
            $default_pin_color = $row['Default'];
          }
        }

    // Check connection
  $sql_get_residence_info = "SELECT * FROM residences WHERE residence_id='$residence'";
  $P->do_query($sql_get_residence_info);
  $residence_info_result = mysql_query($sql_get_residence_info);

      // Goes through the result of the query to get the id of the current user's residence 
  while ($row = mysql_fetch_assoc($residence_info_result))
  {
    $username = $row['username'];
    $address = $row['address'];
    $latitude = $row['latitude'];
    $longitude = $row['longitude'];
  }

    if ( ($username == "admin") ||  ($username == "guest") || $username == NULL){
    header("location: admin.php");
    exit();
  }

  $sqlResidences = "SELECT CONCAT(first_name, ' ', last_name) as 'head_full_name', head_resident_id, username, address, latitude, longitude, emergency_contact, phone_one, email_address, pin_color FROM residences LEFT JOIN head_residents ON head_residents.fk_residence_id = residences.residence_id WHERE address IS NOT NULL AND residence_id !='$residence' ORDER BY username DESC";
    $P->do_query($sqlResidences);
    $resultResidences = mysql_query($sqlResidences);

  ?>


  <!DOCTYPE html>
  <html>
  <head>
    <!-- Google API KEY for accessing a broader spectrum of Google APIs-->
    <script src="http://maps.googleapis.com/maps/api/js?key=AIzaSyCTUwndh9ZED3trNlGZqcCEjkAb5-bpoUw"></script>
    <!-- Load in classes and Libraries -->

  <!-- Add the dropdown files and color pins file-->
  <link rel="stylesheet" type="text/css" href="css/dropdown.css" />
  <script type="text/javascript" src="js/dropdown.js"></script>
  <script type="text/javascript" src="js/colorpins.js"></script>

  <!-- Load In Google Maps -->
  <script>
  //Begin the process of changing the image color
  pincolor = []; //Make pin colors global

  var map;
  var panorama;
  var iconbase = 'images/';
//Sets the default center of the Map
//Should change to Community Location (if set)
var latitude = <?php  if (isset($latitude)){echo $latitude ;}?>;
var longitude = <?php if (isset($longitude)){echo $longitude ;}?>;

var myCenter=new google.maps.LatLng(latitude, longitude);
var markers = [];
var additional_markers = [];

    addresses = [];
    head_full_names = [];
    residence_name = [];
    head_resident_ids = [];

    //this will be populated with the total lat and longitude for the average to be computed
    center_lat = 0;
    center_lon = 0;

  //holds parsed latlng location data
    latlng = [];
    //holds latitude and longitude location from database
    latitudes = [];
    longitudes = [];

    //Holds values for the dropdown menu
    divOptions = [];
    optionsDiv = [];
    options = [];

function initialize(){

  var mapProp = {
    center:myCenter,
    zoom:18,
    mapTypeId:google.maps.MapTypeId.ROADMAP
  };


    //this creates our map
    map = new google.maps.Map(document.getElementById("googleMap"),mapProp);

    //Sets a Marker at the locations in the Geocoder search
  var marker = new google.maps.Marker({
    map: map,
    draggable: true,
    title: (<?php echo "'$username'" ?>),
    icon: iconbase + 'house_pin02.png',
    position: myCenter,
    animation: google.maps.Animation.DROP
  }); 
  markers.push(marker);


    //populates residence data from database
    <?php while ($row = mysql_fetch_assoc($resultResidences)) { ?>
      addresses.push(<?php echo '"'. $row['address'] .'"'?>);
      head_full_names.push(<?php echo '"'. $row['head_full_name'] .'"'?>);
      head_resident_ids.push(<?php echo '"'. $row['head_resident_id'] .'"'?>);
      residence_name.push(<?php echo '"'. $row['username'] .'"'?>);
    //populates the latlng array by creating an object based on the queryd data
    latitudes.push(<?php echo '"'. $row['latitude'] .'"'?>);
    longitudes.push(<?php echo '"'. $row['longitude'] .'"'?>); 
    latlng.push(new google.maps.LatLng((<?php echo '"'. $row['latitude'] .'"'?>), (<?php echo '"'. $row['longitude'] .'"'?>)));
    pincolor.push(<?php echo '"'. $row['pin_color'] .'"'?>);

    <?php } ?>
    //this loop will create all of the markers and infowindow content for those markers, then invoke the addlistener function
    for(i in addresses) {
        center_lat += parseFloat(latitudes[i]);
        center_lon += parseFloat(longitudes[i]);

        //Change the color of each image through this function
        if (pincolor[i] == "") {
          pincolor[i] = <?php echo "'" . $default_pin_color . "'"?>;
        }
        overalayColor(pincolor[i]);

        //creates a marker in the markers array
        additional_markers.push(new google.maps.Marker({
          map: map, 
          position: latlng[i],
          title: addresses[i],
          title: (head_full_names[i] + "\n" + addresses[i]),
          icon: fullimg,
          animation: google.maps.Animation.DROP
        }));

        //start process to set up custom drop down
        //create the options that respond to click
        if (head_resident_ids[i] == "") {
          divOptions.push({
              gmap: map,
              name: residence_name[i],
              title: residence_name[i],
              id: head_resident_ids[i],
              latlng: latlng[i],
              identifier: i
          });
        } else {
          divOptions.push({
              gmap: map,
              name: head_full_names[i],
              title: "Residence of " + head_full_names[i],
              id: head_resident_ids[i],
              latlng: latlng[i],
              identifier: i
          });
        }
        optionsDiv.push(new optionDiv(divOptions[i]));

        options.push(optionsDiv[i]);

    }
    
    options = options.reverse(); //Sort the array

    //put them all together to create the drop down       
        var ddDivOptions = {
          items: options,
          id: "myddOptsDiv"          
        }

        //alert(ddDivOptions.items[1]);
        var dropDownDiv = new dropDownOptionsDiv(ddDivOptions);               
                
        var dropDownOptions = {
            gmap: map,
            name: 'Find A Residence',
            id: 'ddControl',
            title: 'Find A Residence',
            position: google.maps.ControlPosition.TOP_CENTER,
            dropDown: dropDownDiv 
        }
        
        var dropDown1 = new dropDownControl(dropDownOptions);

    var geocoder = new google.maps.Geocoder();

//This puts a marker based on the string in submitG
document.getElementById('submitAddress').addEventListener('click', function() {
  geocodeAddress(geocoder, map);
});
google.maps.event.addListener(marker, 'dragend', function (event) {
  document.getElementById("latitude").value = this.getPosition().lat();
  document.getElementById("longitude").value = this.getPosition().lng();
});

      //these next four lines are for the centering button
      var centerControlDiv = document.createElement('div');
      var centerControl = new centerbutton(centerControlDiv, map);
      centerControlDiv.index = 1;
      //puts the centering button on the map
      map.controls[google.maps.ControlPosition.TOP_CENTER].push(centerControlDiv);

    var FindMyHouseControlDiv = document.createElement('div');
    var FindMyHouseControl = new findmyhouse(FindMyHouseControlDiv, map);
    FindMyHouseControlDiv.index = 1;
        //puts the centering button on the map
    map.controls[google.maps.ControlPosition.TOP_CENTER].push(FindMyHouseControlDiv);

}
//----------------------END OF INITIALIZE FUNCTION

//Turns the map on.
google.maps.event.addDomListener(window, 'load', initialize);

//this function centers the map on the community based on average latitude and longitude
function centermap(){
    var final_lat_center = (center_lat/latitudes.length);
    var final_lon_center = (center_lon/longitudes.length);
    //sets center position
    map.panTo(new google.maps.LatLng(final_lat_center, final_lon_center));
    //sets map zoom (zoom amount is up for debate)
    map.setZoom(17);
}

//this function styles and sets up the button
function centerbutton(controlDiv, map) {
    // Set CSS for the control border.
    var controlUI = document.createElement('div');
    controlUI.style.backgroundColor = '#3399FF';
    controlUI.style.border = '2px solid #00000';
    controlUI.style.borderRadius = '3px';
    controlUI.style.boxShadow = '0 2px 6px rgba(0,0,0,.3)';
    controlUI.style.cursor = 'pointer';
    controlUI.style.marginBottom = '22px';
    controlUI.style.textAlign = 'right';
    controlUI.title = 'Click to recenter the map on your community';
    controlDiv.appendChild(controlUI);

    // Set CSS for the control interior.
    var controlText = document.createElement('div');
    controlText.style.color = 'rgb(250,250,250)';
    controlText.style.fontFamily = 'Roboto,Arial,sans-serif';
    controlText.style.fontSize = '16px';
    controlText.style.lineHeight = '38px';
    controlText.style.paddingLeft = '5px';
    controlText.style.paddingRight = '5px';
    controlText.innerHTML = 'Center on Your Community';
    controlUI.appendChild(controlText);

    // Setup the click event listeners: calls the centermap function
    controlUI.addEventListener('click', function() {
        centermap();
    });
}
//this function styles and sets up the button
function findmyhouse(controlDiv, map) {
    // Set CSS for the control border.
    var controlUI = document.createElement('div');
    controlUI.style.backgroundColor = '#3399FF';
    controlUI.style.border = '2px solid #00000';
    controlUI.style.borderRadius = '3px';
    controlUI.style.boxShadow = '0 2px 6px rgba(0,0,0,.3)';
    controlUI.style.cursor = 'pointer';
    controlUI.style.marginBottom = '22px';
    controlUI.style.textAlign = 'right';
    controlUI.title = 'Click to find the house you are editing.';
    controlDiv.appendChild(controlUI);

    // Set CSS for the control interior.
    var controlText = document.createElement('div');
    controlText.style.color = 'rgb(250,250,250)';
    controlText.style.fontFamily = 'Roboto,Arial,sans-serif';
    controlText.style.fontSize = '16px';
    controlText.style.lineHeight = '38px';
    controlText.style.paddingLeft = '5px';
    controlText.style.paddingRight = '5px';
    controlText.innerHTML = 'Find Editing House';
    controlUI.appendChild(controlText);

    // Setup the click event listeners: calls the centermap function
    controlUI.addEventListener('click', function() {
      var houseLatitude = document.getElementById('latitude').value;
      var houseLongitude = document.getElementById('longitude').value;
      
        //sets center position
        map.panTo(new google.maps.LatLng(houseLatitude, houseLongitude));
        //sets map zoom (zoom amount is up for debate)
        map.setZoom(18);
    });
}

    function optionDiv(options){
      var control = document.createElement('DIV');
      control.className = "dropDownItemDiv";
      control.title = options.title;
      control.id = options.id;
      control.innerHTML = options.name;
      control.action = function() { 
          map.panTo(options.latlng);
          /*infowindow.setContent(infowindows[options.identifier]);
          infowindow.open(map,markers[options.identifier]); 
          populatetable(options.identifier);
          panorama = new google.maps.StreetViewPanorama(
          document.getElementById('street-view'),
          {
            position: options.latlng,
            pov: {heading: 0, pitch: 0},
            zoom: 1,
            linksControl: false,
            addressControl: false
          });*/
        };
      google.maps.event.addDomListener(control,'click', control.action);
      return control;
     }

function geocodeAddress(geocoder, resultsMap) {

  clearMarkers();
  var address = document.getElementById('address').value;
  geocoder.geocode({'address': address}, function(results, status) {
    if (status === google.maps.GeocoderStatus.OK) {
      resultsMap.panTo(results[0].geometry.location);
      resultsMap.setZoom(18);
//Sets a Marker at the locations in the Geocoder search
var marker = new google.maps.Marker({
  map: resultsMap,
  draggable: true,
  icon: iconbase + 'house_pin.png',
  position: results[0].geometry.location
});
markers.push(marker);

  document.getElementById("latitude").value = marker.getPosition().lat();
  document.getElementById("longitude").value = marker.getPosition().lng();

// Zoom to 15 when clicking on marker and opens the infow window if its closed
google.maps.event.addListener(marker,'click',function() {
  map.setZoom(18);
  map.panTo(marker.getPosition());
});

} else {
  alert('Geocode was not successful for the following reason: ' + status);
}

google.maps.event.addListener(marker, 'dragend', function (event) {
  document.getElementById("latitude").value = this.getPosition().lat();
  document.getElementById("longitude").value = this.getPosition().lng();
});
});
}

// Sets the map on all markers in the array.
function setMapOnAll(map) {
  for (var i = 0; i < markers.length; i++) {
    markers[i].setMap(map);
  }
}

// Removes the markers from the map, but keeps them in the array.
function clearMarkers() {
  setMapOnAll(null);
}
function show_confirm(residence_id){

          // shows the modal on button press
          $('#confirm_modal').modal('show');
          document.getElementById("submit_residence_name").innerHTML = document.getElementById("residence_name").value;
          document.getElementById("submit_address").innerHTML = document.getElementById("address").value;
          document.getElementById("submit_latitude").innerHTML = document.getElementById("latitude").value;
          document.getElementById("submit_longitude").innerHTML = document.getElementById("longitude").value;
          document.getElementById("update_residence").value = <?php echo $residence; ?>;
      }


      </script>

    </head>

  <body>

   <!-- Form for the update of head resident -->
   <form action="updateresidence.php" method="POST">
    <div class="container-fluid">
     <div class="row">
      <div class="col-md-5">
       <h3> Residence Information </h3>
       <?php echo $error; ?>
       <table class="table table-striped table-hover ">
        <tr>
         <th> Residence Name </th>
         <!-- Head resident first name -->
         <td> <input id="residence_name" name="residence_name" type="text" placeholder="<?php  if (isset($username)){echo $username ;} ?>" value="<?php if (isset($username)){echo $username ;}?>" class="form-control input-md"> </td>
       </tr>
       <tr>
         <th> Address </th>
         <!-- Head resident Emergency Contact -->
         <td> <input id="address" name="address" type="text" placeholder="<?php if (isset($address)){echo  $address ;}?>" value="<?php if (isset($address)){echo $address ;}?>" class="form-control input-md" > </td>
       </tr>
       <tr>
        <th> </th> 
        <td>
          <button type="button"  name="submitAddress" id="submitAddress"  value="Reverse Geocode" class="btn btn-info btn-lg" style="  width: 100%;"> Drop New Pin</button>
        </td>
      </tr>
    </table> <br/>
    <h3> Location </h3>
    <table class="table table-striped table-hover ">
      <tr>
        <th> Latitude &nbsp &nbsp  &nbsp  &nbsp  &nbsp  &nbsp</th>
        <!-- Head resident first name -->
        <td> <input id="latitude" name="latitude" type="text" value=<?php echo "'". $latitude . "'"?> class="form-control input-md" readonly> </td>
      </tr>
      <tr>
        <th> Longitude </th>
        <!-- Head resident Emergency Contact -->
        <td> <input id="longitude" name="longitude" type="text" value=<?php echo "'". $longitude . "'"?> class="form-control input-md" readonly> </td>
      </tr>
      <tr>
        <th> </th> 
        <td>
          <button type="button" onclick="show_confirm()" class="btn btn-primary btn-lg" style="  width: 100%;"> Update Residence </button>
        </td>
      </tr>
    </table>
  </div>
  <!--Google Map Div-->
  <div class="col-xs-12 col-sm-12 col-md-7  col-md-offset-5" id="googleMap" style="position: absolute; height:100%;" ></div>
  <!-- Modal -->
  <div class="modal fade" id="confirm_modal" role="dialog">
    <div class="modal-dialog">

      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal"></button>
          <h4 class="modal-title" style="text-align: center; font-size: 200%;"><b>Update Residence</b></h4>
        </div>
        <div class="modal-body">
          <b><p  style="font-size: 120%;">
            Are you sure you want to update this residence with this information? </p></b> <br/>
            <table class="table table-striped table-hover ">
              <tr>
                <th> Residence Name </th>
                <th> Address </th>
                <th> Latitude </th>
                <th> Longitude </th>
              </tr>
              <tr>
                <td id="submit_residence_name"> </td>
                <td id="submit_address"> </td>
                <td id="submit_latitude"> </td>
                <td id="submit_longitude"> </td>
              </tr>
            </table>
            <div class="modal-footer">
              <button type="submit" class="btn btn-success btn-lg" name="update_residence" id="update_residence" value="">Yes</button>
              <button type="button" class="btn btn-danger btn-lg" data-dismiss="modal">No</button>
            </div>
          </div>

        </div>
      </div>
    </form> 

  </body>
  </html>