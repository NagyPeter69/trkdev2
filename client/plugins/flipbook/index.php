<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title><?= $magazine[0][2]." ".$pub[0][10]."" ?></title>
	
	<script src="plugins/flipbook/js/jquery.min.js"></script>
	
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

	<div class="sample-container-box">
	  <div class="sample-container solid-container">
	  </div>
	</div>

	<script src="plugins/flipbook/js/three.min.js"></script>
	<script src="plugins/flipbook/js/pdf.min.js"></script>

	<script type="text/javascript">
	  window.PDFJS_LOCALE = {
		pdfJsWorker: 'plugins/flipbook/js/pdf.worker.js',
		pdfJsCMapUrl: 'cmaps'
	  };
	</script>
	<script src="plugins/flipbook/js/3dflipbook.min.js"></script>

	<script type="text/javascript">
	  $('.sample-container').FlipBook({
		pdf: 'handout/<?= $_GET["file"] ?>',
		template: {"html":"plugins\/flipbook\/templates\/default-book-view.html","styles":["plugins\/flipbook\/css\/font-awesome.min.css","plugins\/flipbook\/css\/short-black-book-view.css"],"script":"plugins\/flipbook\/js\/default-book-view.js"}  });
	</script>

  </body>
</html>