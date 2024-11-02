<?php

include_once("./geoPHP/geoPHP.inc");

set_time_limit(0);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

#export GOOGLE_MAPS_API_KEY=AIzaSyDAtdEB1fEAkpkTFHO-o0SHc1Sa2i_9hQM
#curl "https://maps.googleapis.com/maps/api/geocode/json?address=1600+Amphitheatre+Parkway,+Mountain+View,+CA&key=${GOOGLE_MAPS_API_KEY}"
#curl "https://maps.googleapis.com/maps/api/geocode/json?address=אבטליון&key=AIzaSyDAtdEB1fEAkpkTFHO-o0SHc1Sa2i_9hQM"
#wget https://api.tzevaadom.co.il/alerts-history/id/446 

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);




/*
function CalculatePolygonArea($coordinates_in) {
    $area_in = 0;
    $coordinatesCount = sizeof($coordinates_in);
//    echo "<hr>";
//    echo "size : ".$coordinatesCount."<br>";
//    var_export ($coordinates);
//    echo "<hr>";

    if ($coordinatesCount > 2) {
      for ($i = 0; $i < $coordinatesCount - 1; $i++) {
        $p1 = $coordinates_in[$i];
        $p2 = $coordinates_in[$i + 1];
        $p1Longitude = $p1[0];
        $p2Longitude = $p2[0];
        $p1Latitude = $p1[1];
        $p2Latitude = $p2[1];

//        echo ConvertToRadian($p2Longitude - $p1Longitude);
//        echo "<br>";
//        echo sin(ConvertToRadian($p1Latitude));
//        echo "<br>";
//        echo sin(ConvertToRadian($p2Latitude));
//        echo "<br>";
//        echo "P1lng : ".$p1Longitude."<br>";
//        echo "P2lng : ".$p2Longitude."<br>";
//        echo "P2lnt : ".$p1Latitude."<br>";
//        echo "P2lat : ".$p2Latitude."<br>";
        $area_in += ConvertToRadian($p2Longitude - $p1Longitude) * (2 + sin(ConvertToRadian($p1Latitude)) + sin(ConvertToRadian($p2Latitude)));
//        echo "area : ".$area."<br><hr>";

    }
    $area_in = $area_in * 6378137 * 6378137 / 2;
    }
    return abs(round(($area_in)));
}

function ConvertToRadian($input) {
    $output = $input * pi() / 180;
    return $output;
}
*/
















//$con = mysqli_connect("<DB SERVER IP>", "<DB USER>", "<DB PASSWORD>", "<DB NAME>");
mysqli_set_charset($con, "utf8mb4");

// Get information of last 24 hours
// open table for better tesult

$alerts_count = 0;
date_default_timezone_set('Asia/Jerusalem');

$__no_output = 0;
if (str_contains(strtoupper($_SERVER['QUERY_STRING']), "NO_OUTPUT")) {
    $__no_output = 1;
}

if ($__no_output == 0 ) echo "<html dir=RTL><body>";

$ch = curl_init("https://api.tzevaadom.co.il/alerts-history");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 0);
$response = curl_exec($ch);

$data = json_decode($response,true);

if ($__no_output == 0 ) echo "<pre><table border=1>";
if ($__no_output == 0 ) echo "<tr><th>מסד</th><th>תאריך</th><th>שעה</th><th>עיר</th></tr>";

$end_id = 0;

// finds last alert on db 
// in order to fetch later on alerts that are not on api history
// TASK - need to check if all alerts are in db an no skips - just rerive list of all ids 
$sql_quary = "SELECT max(id) idm FROM alerts";
$result = mysqli_query($con,$sql_quary);
if($result->num_rows == 0) {
    $start_id = 446;
} else {
    $start_id = $result->fetch_row()[0];
}



$stmt = "INSERT INTO alerts (id, timestamp, city) VALUES (";

foreach ($data as $info) {
    $id = $info['id'];
    if ($end_id == 0) {
        $end_id = $id;
    }
    foreach ($info['alerts'] as $alert) {
        $time = $alert['time'];
        $date_display = date("d/m/Y",$time);
        $time_display = date("H:i:s",$time);
        $timestamp = date("Y-m-d H:i:s",$time);
        foreach ($alert['cities'] as $city) {
            $bgcolor = "#52BE80";
            $sql_quary = "SELECT id,timestamp,city FROM alerts where id=".$id." and timestamp='".$timestamp."' and city=\"".$con->real_escape_string($city)."\"";
            $result = mysqli_query($con,$sql_quary);
            if($result->num_rows == 0) {
                $stmt = "INSERT INTO alerts (id, timestamp, city) VALUES (";
                $val1 = $id;
                $val2 = $timestamp;
                $val3 = $con->real_escape_string($city);
                $stmt .= $id." , \"".$val2."\" , \"".$val3."\")";
                $result = mysqli_query($con,$stmt);
                $bgcolor = "#EC7063";
//                echo "<font color=blue>sql query :".$sql_quary."</font><br>";
//                echo "sql insert :".$stmt."</font><br>";               
            }
            $alerts_count++;
            if ($__no_output == 0 ) echo "<tr><td bgcolor='".$bgcolor."'>".$id."</td><td bgcolor='".$bgcolor."'>".$date_display."</td><td bgcolor='".$bgcolor."'>".$time_display."</td><td align=right bgcolor='".$bgcolor."'>".$city."</td></tr>";
        }
    }
    $middle_id = $id;
}
curl_close($ch);
unset($ch);
unset($response);
unset($data);

$total_area = 0;
// retrive old alerts information that are not from last 24 hours
//same logic as previous part
//$middle_id = 0;
for ($i = ($middle_id-1) ; $i >= $start_id ; $i--) {
    $url = "https://api.tzevaadom.co.il/alerts-history/id/".$i;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $response = curl_exec($ch);

    $data = json_decode($response,true);
    $id = $data['id'];
    foreach ($data['alerts'] as $alert) {
        $time = $alert['time'];
        $date_display = date("d/m/Y",$time);
        $time_display = date("H:i:s",$time);
        $timestamp = date("Y-m-d H:i:s",$time);
        foreach ($alert['cities'] as $city) {
            $bgcolor = "#52BE80";
            $sql_quary = "SELECT id,timestamp,city FROM alerts where id=".$id." and timestamp='".$timestamp."' and city='".$con->real_escape_string($city)."'";
//            echo $sql_quary."<br>";
            $result = mysqli_query($con,$sql_quary);
            if($result->num_rows == 0) {
                $stmt = "INSERT INTO alerts (id, timestamp, city) VALUES (";
                $val1 = $id;
                $val2 = $timestamp;
                $val3 = $con->real_escape_string($city);
                $stmt .= $id." , \"".$val2."\" , \"".$val3."\")";
//                echo $stmt."<br>";
                $result = mysqli_query($con,$stmt);
                $bgcolor = "#EC7063";
            }
            $alerts_count++;
            if ($__no_output == 0 ) echo "<tr><td bgcolor='".$bgcolor."'>".$id."</td><td bgcolor='".$bgcolor."'>".$date_display."</td><td bgcolor='".$bgcolor."'>".$time_display."</td><td align=right bgcolor='".$bgcolor."'>".$city."</td></tr>";
        }
    }
    curl_close($ch);
    unset($ch);
    unset($response);
    unset($data);
}
if ($__no_output == 0 ) echo "</table></pre>";
if ($__no_output == 0 ) echo "Number or alerts : ".$alerts_count."<br>";
if ($__no_output == 0 ) echo "".$start_id." - ".$middle_id." - ".$end_id."<br>";
if ($__no_output == 0 ) echo "<br>";

// Needs to be developed - find max id, min id how many rows on result and check if numbers add up.
$sql_quary = "SELECT max(id), min(id) FROM alerts";
$result = mysqli_query($con,$sql_quary);
if($result->num_rows != 0) {
    $tmp = $result->fetch_row();
    $max_id = $tmp[0];
    $min_id = $tmp[1];
    $sql_quary = "SELECT distinct (id) FROM alerts order by id ";
    $result = mysqli_query($con,$sql_quary);
    echo $max_id." - ".$min_id." - ".$result->num_rows."<br>";
    if (($max_id-$min_id+1) == $result->num_rows) {
//        echo ("All alerts accounted for");
    } else {
        echo ("Some alerts are missing !!!\n<br>");
		echo ("rows:".$result->num_rows."\n<br>");
		echo ("max:".$max_id."\n<br>");
		echo ("min:".$min_id."\n<br>");
		echo ("delta:".($max_id-$min_id+1)."\n<br>");
		echo ("\n<br>");
		$sql_quary = "SELECT id,timestamp,city,alert_id FROM alerts where id BETWEEN ".$min_id." and ".$max_id;
        echo $sql_quary."<br>";
        $result = mysqli_query($con,$sql_quary);
            if($result->num_rows == 0) {
			}
    }
} else {
    // Error - need to check
}

//echo ("XXX");
$sql_quary = "SELECT max(`lastrun`) FROM execute_log;";
$result = mysqli_query($con,$sql_quary);
//echo ("YYY");
if($result->num_rows == 0) {
    echo ("There is an error !");
} else {
    $lastrun = $result->fetch_row()[0];
}

if ($__no_output == 0 ) echo "Last run : ".$lastrun."<br>";
//if ($__no_output == 0 ) echo "Last run : ".date("Y-m-d H:i:s",$lastrun)."<br>";
//echo ("XXX");
//$stmt = "INSERT INTO `execute_log` SET lastrun = \"".date("Y-m-d H:i:s")."\";";
$stmt = "INSERT INTO `execute_log` SET lastrun=now();";
//echo $stmt."<br>";

$result = mysqli_query($con,$stmt);
//echo ("XXX");

//echo ("yyy");


/*
$sql_quary = "SELECT `name`, `id`, `lat`, `lng` FROM `cities`;";
//$sql_quary = "SELECT `name`, `id`, `lat`, `lng` FROM `cities` where id in (4,9,802);";
$result = mysqli_query($con,$sql_quary);
$coord = array();
$coord_tmp = array();
foreach ($result as $row) {
    $i = 0;
    $area7 = 0;
    //    echo ("".$row["name"]." - ".$row["id"]." - ");
    $sql_quary2 = "SELECT `lat`, `lng`, `poly_num` FROM `cities_poly`where `city_id` = ".$row["id"].";";
    $result2 = mysqli_query($con,$sql_quary2);
    $polygon_str2 = "";
    $k = $result2->num_rows;
    empty($coord);
    $coord = array_fill(0, 0, NULL);
    empty($coord_tmp);
    foreach ($result2 as $row2) {
        $polygon_str2 .= "[".$row2["lat"].",".$row2["lng"]."]";
        $coord_tmp [0] = $row2["lng"];
        $coord_tmp [1] = $row2["lat"];
        $coord[$i] = $coord_tmp;
        $i++;
        if ($i < $k) $polygon_str2 .= ",";
    }
    if($result2->num_rows == 0) continue;
    $area7 = CalculatePolygonArea($coord);

  


    //    echo ("Area: ".number_format($area7/1000/1000,3)." Km² - ");
    empty($result2);
    empty($polygon_str);

    $tmp1 = "https://www.mapdevelopers.com/area_finder.php?polygons=";
    $tmp2 = "[[[";
    $tmp2 .= $polygon_str2;
    $tmp2 .= '],"#AAAAAA","#000000",0.4]]';
//    echo "<hr>".$polygon_str2."<br>";
    echo "<a href='".$tmp1.urlencode($tmp2)."'>".$row["name"]."</a> - ".$row["id"]." - ";
    echo "Area: ".number_format($area7/1000/1000,3)." Km² - ".$k."<br>";
    $total_area = $total_area + $area7;

}
*/

/*
https://www.mapdevelopers.com/area_finder.php?polygons
=[[[
    [31.589540349131198,34.494953427823766],
    [31.540395786444186,34.577350888761266],[31.50059313227296,34.533405576261266],[31.37404878490274,34.368610654386266],[31.336521372473225,34.379596982511266],[31.294285137702303,34.376850400480016],[31.219151755395828,34.269733701261266],[31.3224447322828,34.214802060636266],[31.44671550266246,34.368610654386266],
    [31.589540349131198,34.494953427823766]]
    ,"#AAAAAA","#000000",0.4]]
*/
/*
$coord = array();
$coord_tmp = array();
$i = 0;
$area7 = 0;
$sql_quary2 = "SELECT `lat`, `lng` FROM `israel`;";
$result2 = mysqli_query($con,$sql_quary2);
$k = $result2->num_rows;
$coord = array_fill(0, 0, NULL);
foreach ($result2 as $row2) {
    $coord_tmp [0] = $row2["lng"];
    $coord_tmp [1] = $row2["lat"];
    $coord[$i] = $coord_tmp;
    $i++;
    if ($i < $k) $polygon_str2 .= ",";
}
$area7 = CalculatePolygonArea($coord);

$tmp1 = "https://www.mapdevelopers.com/area_finder.php?polygons=";
$tmp2 = "[[[";
$tmp2 .= $polygon_str2;
$tmp2 .= '],"#AAAAAA","#000000",0.4]]';
echo "<a href='".$tmp1.urlencode($tmp2)."'>ישראל</a> - ";
echo "Area: ".number_format($area7/1000/1000,3)." Km² - ".$k."<br>";
//$total_area = $total_area + $area7;


echo "<br>Total Areas' area: ".number_format($total_area/1000/1000,3)." Km² - ".number_format($total_area/$area7*100,2)."%<br>";

*/









/*
// get coordination for all unique cities
if ($__no_output == 0 ) echo "<pre><table border=1>";
if ($__no_output == 0 ) echo "<tr><th>id</th><th>city</th><th>name</th><th>lat</th><th>lng</th><th>place_id</th></tr>";



$sql_quary = "SELECT distinct city FROM alerts order by city";
$result = mysqli_query($con,$sql_quary);

while ($row = mysqli_fetch_row($result)) {
    $city = $row[0];
//    echo $city."<br>";
}



sort($cities_list);
$cities_not_fount_list = array();
$cities_to_query_list = array();

foreach ($cities_list as $city) {
    $sql_quary = "SELECT city FROM cities where city=\"".$con->real_escape_string($city)."\"";
    $result = mysqli_query($con,$sql_quary);
    if($result->num_rows == 0) {
        array_push($cities_to_query_list, $city);
    }
}

$i = 0;
$j = 0;
foreach ($cities_to_query_list as $city) {

    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($city)."&key=AIzaSyDAtdEB1fEAkpkTFHO-o0SHc1Sa2i_9hQM";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $response = curl_exec($ch);
//    echo ("X");

    $data = json_decode($response,true);
    $result = $data["results"];
    $is_ok = $data["status"];


//  remark 2 lines in case of google maps api is used
//  and remove remarks block above.
//  saving api usage.
//
    $ch = curl_init("");
    $is_ok = "";
//
    if ($is_ok == "OK") {
        $name = $result[0]['formatted_address'];
        $location_lat = $data['results'][0]['geometry']['location']['lat'];
        $location_lng = $data['results'][0]['geometry']['location']['lng'];
        $place_id = $data['results'][0]['place_id'];
        $i++;

        $bgcolor = "#52BE80";
        $sql_quary = "SELECT city,name,lat,lng,place_id FROM cities where city=\"".$con->real_escape_string($city)."\" and name=\"".$con->real_escape_string($name)."\" and place_id=\"".$place_id."\"";
        $result = mysqli_query($con,$sql_quary);
        if($result->num_rows == 0) {
            $stmt = "INSERT INTO cities (city,name,lat,lng,place_id) VALUES (";
            $val1 = $con->real_escape_string($city);
            $val2 = $con->real_escape_string($name);
            $val3 = $location_lat;
            $val4 = $location_lng;
            $val5 = $place_id;
            $stmt .= "\"".$val1."\" , \"".$val2."\" , ".$val3." , ".$val4." , \"".$val5."\")";

            try {
                $result = mysqli_query($con,$stmt);
            } catch (Exception $e) {
                if ($__no_output == 0 ) echo "sql : ".$sql_quary."<br>";
                if ($__no_output == 0 ) echo "inert : ".$stmt."<br>";
                if ($__no_output == 0 ) echo 'Caught exception: ',  $e->getMessage(), "\n";
            }       
            $bgcolor = "#EC7063";
        }
    } else {
        $new_city = "";
        if ($city == "אזור תעשייה אלון התבור") $new_city = "תחנת הכוח אלון תבור MRC";
        if ($city == "אזור תעשייה ברוש") $new_city = "אזור תעשייה ברוש מערב";
        if ($city == "אזור תעשייה כנות") $new_city = "פארק תעשיות כנות";
        if ($city == "אזור תעשייה צ.ח.ר") $new_city = "אזור תעשיה צח\"ר";
        if ($city == "אזור תעשייה צמח") $new_city = "מפעלים אזוריים צמח";
        if ($city == "אל פורעה") $new_city = "אלפורעה";
        if ($city == "ברוש") $new_city = "ישוב ברוש";
        if ($city == "גבעתי") $new_city = "ישוב גבעתי";
        if ($city == "הראל") $new_city = "ישוב הראל";
        if ($city == "חצב") $new_city = "ישוב חצב";
        if ($city == "טפחות") $new_city = "ישוב טפחות";
        if ($city == "יד מרדכי") $new_city = "ישוב יד מרדכי";
        if ($city == "כלנית") $new_city = "ישוב כלנית";
        if ($city == "לוטם וחמדון") $new_city = "ישוב לוטם";
        if ($city == "לפיד") $new_city = "ישוב לפיד";
        if ($city == "מגדל") $new_city = "ישוב מגדל";
        if ($city == "מסד") $new_city = "ישוב מסד";
        if ($city == "מסעדה") $new_city = "ישוב מסעדה";
        if ($city == "נמרוד") $new_city = "ישוב נמרוד";
        if ($city == "סולם") $new_city = "ישוב סולם";
        if ($city == "עגור") $new_city = "ישוב עגור";
        if ($city == "עינבר") $new_city = "ישוב עינבר";
        if ($city == "עמינדב") $new_city = "ישוב עמינדב";
        if ($city == "עשאהל") $new_city = "ישוב עשאהל";
        if ($city == "קשת") $new_city = "ישוב קשת";
        if ($city == "רווחה") $new_city = "ישוב רווחה";
        if ($city == "רקפת") $new_city = "ישוב רקפת";
        if ($city == "שאנטי במדבר") $new_city = "חאן השיירות";
        if ($city == "שורש") $new_city = "ישוב שורש";
        if ($city == "שחר") $new_city = "ישוב שחר";
        if ($city == "שרשרת") $new_city = "ישוב שרשרת";

        if ($city == "דניאל") $new_city = "כפר דניאל";
        if ($city == "הילה") $new_city = "מצפה הילה";
        if ($city == "כנות") $new_city = "כפר הנוער החקלאי כנות";
        if ($city == "לטרון") $new_city = "מנזר לטרון";
        if ($city == "מתחם צומת שוקת") $new_city = "דלק תל שוקת";
        if ($city == "ניצן") $new_city = "ניצן ב'";
        if ($city == "עבדת") $new_city = "עבדת/עתיקות";
        

        if ($new_city == "") {
            array_push($cities_not_fount_list, $city);
            $name = "";
            $location_lat = "";
            $location_lng = "";
            $place_id = "";
            $j++;
            $bgcolor = "#AAAAFF";
        } else {

            $url2 = "https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($new_city)."&key=AIzaSyDAtdEB1fEAkpkTFHO-o0SHc1Sa2i_9hQM";
            $ch2 = curl_init($url2);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_HEADER, 0);
            $response = curl_exec($ch2);
        
            $data = json_decode($response,true);
            $result = $data["results"];
            $is_ok = $data["status"];

            if ($is_ok == "OK") {
                $name = $result[0]['formatted_address'];
                $location_lat = $data['results'][0]['geometry']['location']['lat'];
                $location_lng = $data['results'][0]['geometry']['location']['lng'];
                $place_id = $data['results'][0]['place_id'];
                $i++;
        
                $sql_quary = "SELECT city,name,lat,lng,place_id FROM cities where city=\"".$con->real_escape_string($city)."\" and name=\"".$con->real_escape_string($name)."\" and place_id=\"".$place_id."\"";
                $result = mysqli_query($con,$sql_quary);
                if($result->num_rows == 0) {
                    $stmt = "INSERT INTO cities (city,name,lat,lng,place_id) VALUES (";
                    $val1 = $con->real_escape_string($city);
                    $val2 = $con->real_escape_string($name);
                    $val3 = $location_lat;
                    $val4 = $location_lng;
                    $val5 = $place_id;
                    $stmt .= "\"".$val1."\" , \"".$val2."\" , ".$val3." , ".$val4." , \"".$val5."\")";
        
                    try {
                        $result = mysqli_query($con,$stmt);
                    } catch (Exception $e) {
                        if ($__no_output == 0 ) echo "sql : ".$sql_quary."<br>";
                        if ($__no_output == 0 ) echo "inert : ".$stmt."<br>";
                        if ($__no_output == 0 ) echo 'Caught exception: ',  $e->getMessage(), "\n";
                    }       
                    $bgcolor = "#777777";
                }
            $bgcolor = "#FFAAFF";

            } else {
                array_push($cities_not_fount_list, $city);
                $name = "";
                $location_lat = "";
                $location_lng = "";
                $place_id = "";
                $j++;
                $bgcolor = "#AAAAFF";  
            }

        }
    }


//    echo "<font color=blue>sql query :".$sql_quary."</font><br>";
//    echo "sql insert :".$stmt."</font><br>";               

    if ($__no_output == 0 ) echo "<tr><td bgcolor='".$bgcolor."'>".$i."</td><td bgcolor='".$bgcolor."'>".$city."</td><td bgcolor='".$bgcolor."'>".$name."</td><td bgcolor='".$bgcolor."'>".$location_lat."</td><td bgcolor='".$bgcolor."'>".$location_lng."</td><td bgcolor='".$bgcolor."'>".$place_id."</td></tr>";
    curl_close($ch);
//    if ($i == 1) break;
}


if ($__no_output == 0 ) echo "</table></pre>";
if ($__no_output == 0 ) echo "Number of cities : ".count($cities_list)."<br>";
if ($__no_output == 0 ) echo "Number of cities in alerts (SQL) : ".count($cities_to_query_list)."<br>";
if ($__no_output == 0 ) echo "Number of new cities inserted to SQL : ".$i."<br>";
if ($__no_output == 0 ) echo "Number of cities not found in google : ".$j." - ".count($cities_not_fount_list)."<br>";
*/

if ($__no_output == 0 ) echo "</body></html>";

function CalculatePolygonArea($coordinates_in) {
    $area_in = 0;
    $coordinatesCount = sizeof($coordinates_in);

    if ($coordinatesCount > 2) {
        for ($i = 0; $i < $coordinatesCount - 1; $i++) {
            $p1 = $coordinates_in[$i];
            $p2 = $coordinates_in[$i + 1];
            $p1Longitude = $p1[0];
            $p2Longitude = $p2[0];
            $p1Latitude = $p1[1];
            $p2Latitude = $p2[1];
            $area_in += ConvertToRadian($p2Longitude - $p1Longitude) * (2 + sin(ConvertToRadian($p1Latitude)) + sin(ConvertToRadian($p2Latitude)));
        }
        $area_in = $area_in * 6378137 * 6378137 / 2;
    }
    return abs(round(($area_in)));
}

function ConvertToRadian($input) {
    $output = $input * pi() / 180;
    return $output;
}


?>
