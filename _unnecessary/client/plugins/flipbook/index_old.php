<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title><?= $magazine[0][2]." ".$pub[0][10]."" ?></title>
	
	<script src="js/jquery.min.js"></script>
	
    <style type="text/css">
      body {
          margin: 0;
          padding: 0;
      }
      .solid-container {
        height: 100vh;
      }
    </style>
        
  </head>
  <body style="background: #CCC;">

  	<div class="flip-book-container solid-container" src="./handout/<?= $_GET["file"] ?>">

    </div>

    <script src="plugins/flipbook/js/jquery.min.js"></script>
    <script src="plugins/flipbook/js/html2canvas.min.js"></script>
    <script src="plugins/flipbook/js/three.min.js"></script>
    <script src="plugins/flipbook/js/pdf.js"></script>

    <script src="plugins/flipbook/js/3dflipbook.min.js"></script>

  </body>
</html>