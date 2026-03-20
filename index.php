<?php

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Generate barcode</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <style>
      html {
    background: rgb(40 91 212 / 73%);
    background-image: linear-gradient( 58.2deg,
      rgba(40,91,212,0.73) -3%,
      rgba(171,53,163,0.45) 49.3%,
      rgba(255,204,112,0.37) 97.7% );
}
html, body, * {
    font-family: "Poppins", sans-serif;
    box-sizing:border-box;
}

#app {
    display:flex;
    justify-content:center;
    align-items:center;
    width:100%;
    height:100vh;
    height:100dvh;
    margin:0;
    padding:0;
}

form#barcode {
    display:flex;
    flex-direction:column;
    background:white;
    width:400px;
    height:500px;
    padding:40px;
    border-radius:8px;
}
#barcode label {
    display:flex;
    flex-direction:column;
    padding:20px 0px;
}
#barcode select,
#barcode input[type="time"] {
    padding:5px;
    background:#eee;
    border:none;
    width:250px;
}

#barcode input[type="submit"] {
    background:#4566d3;
    width:250px;
    border:none;
    outline: none;
    color: white !important;
    padding:15px 30px;
    margin-top:40px;
    cursor:pointer;
}
  #app {
    flex-direction:column;
}
.result {
    padding:40px;
    flex:0 1 100%;
    background:white;
    max-height:200px;
    overflow-y:hidden;
    border-radius:8px;
    margin-top:40px;
  	width:400px;
}  
.result * {
    max-width:100%;
}
.result h3 {
    margin:0 0 10px 0;
}
.result h3:first-of-type {
    font-weight:400;
    color:#ababab;
    font-size:18px;
}
    </style>
  </head>
  <body>
 
    <div id="app">
    
    
    	<form id="barcode" method="POST" v-on:submit.prevent="sarcazzo">
          <h3>Generate barcode</h3>
          	<label for="room">Room color
            	<select name="room" v-model="form.roomcolor">
                  <option value="0">Entrance</option>
                  <option value="1">Red room</option>
                  <option value="2">Blue room</option>
                  <option value="3">Purple room</option>
                  <option value="4">Green room</option>              
              	</select>
            </label>
          	<label for="time">Session start time
            	<input type="time" name="time" v-model="form.roomtime">  
              
            </label>
          <input type="submit" value="Generate">
      	</form>
    
      <div class="result" v-if="barcodeResult" v-html="barcodeResult"></div>


    </div>

    <script defer>

    var app = new Vue({
      el: '#app',
      data: {
        form: {
        	roomcolor: "0",
        	roomtime: "09:00",
        },
        barcodeResult: null,
      },
      mounted() {
        //
      },
      methods: {
        sarcazzo: async function() {
        fetch('/barcode_generate.php', {
              method: "POST", // or 'PUT',
               headers: {
                "Content-Type": "application/x-www-form-urlencoded",
              },
              body: new URLSearchParams(this.form),
            })
          .then(response => response.json())
          .then(data => {
            console.log(data);
            this.barcodeResult = `<h3>Result</h3> ${data}`;
          })
        },
      }
    });



    </script>

  </body>
</html>







