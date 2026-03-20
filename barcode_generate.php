<?php

require_once 'vendor/autoload.php';



$raw = $_POST;
//echo json_encode($raw);

new BadgeGen($raw);




exit;


class BadgeGen {

  
  function __construct($raw) {
  
  	$room = htmlspecialchars($raw["roomcolor"]);
    $time = htmlspecialchars($raw["roomtime"]);
    
    $string = $room . "-" . $time;
    $pic = $this->create_barcode($string);
    
    $room_col = null;
    switch ($room) {
      case "0":
        $room_col = "Entrance";
        break;
      case "1":
        $room_col = "Red room";
        break;
      case "2":
        $room_col = "Blue room";
        break;
      case "3":
        $room_col = "Purple room";
        break;
      case "4":
        $room_col = "Green room";
        break;
      default:
        $romm_col = "Red room";
    }
    
    
    $res = "<h3>" . $room_col;
    $res .= " | " . $time;
    $res .= "</h3>";
    $res .= $pic;
    
    echo json_encode($res);
    
    exit;
    
  }
  
  private function create_barcode($string) {
	// instantiate the barcode class
	$barcode = new \Com\Tecnick\Barcode\Barcode($string);

	// generate a barcode
	$bobj = $barcode->getBarcodeObj(
    'C128A',                     // barcode type and additional comma-separated parameters
    $string,				        // data string to encode
    -3,                             // bar width (use absolute or negative value as multiplication factor)
    30,                             // bar height (use absolute or negative value as multiplication factor)
    'black',                        // foreground color
    array(0, 0, 0, 0)           // padding (use absolute or negative values as multiplication factors)
    )->setBackgroundColor('white'); // background color

    
     //$this->barcode = <p><img alt=\"Embedded Image\" src=\"data:image/png;base64," . base64_encode($result->getPngData()) . "\" /></p>
     //$this->barcode = base64_encode($result->getPngData());
     //$this->barcode = $result->getPngData();
	$code = '<img src="data:image/png;base64,' . base64_encode($bobj->getPngData()) . '"\ />';
    return $code;
  
  }


}





