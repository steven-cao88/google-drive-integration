<?php
require_once 'Client.php';
$client = new Client();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
	<title>Google Drive Integration</title>
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

	<!-- Optional theme -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
	<div class="container-fluid">
		<div class="row">
			<?php if (!$client->isAuthorised() && 
			(!isset($_REQUEST['access_code']) || empty($_REQUEST['access_code']))) { ?>
				<div class="jumbotron">
					<h1>Welcome</h1>
					<p>Please follow the instruction to setup the app. 
					Click on the url below and allow the app to access your Google Drive.
					After that, please paste the access code in the field below and click Submit
					</p>
					<?php
						$url = $client->getUrl();
						echo '<p><a href="' . $url . '" target="_blank">Authorisation Link</a></p>';
					?>
					<form method="post">
						<div class="form-group">
							<label for="accessCode">Access Code</label>
							<input type="text" name="access_code" id="accessCode" class="form-control">
							<br>
							<input type="submit" class="btn btn-default">
						</div>
					</form>
				</div>
			<?php 
			} else {
				if (isset($_REQUEST['access_code']) && !empty($_REQUEST['access_code'])) {
					try {
						$client->authorise($_REQUEST['access_code']);
					} catch (Exception $e) {
						echo '<div class="alert alert-danger" role="alert">Exception: ' . $e->getMessage() . '</div>';
						exit;
					}
					echo '<div class="alert alert-success" role="alert">Authorisation completed</div>';
				}
				// Test connection
				$client->getService();
				$results = $client->ping();
				if (count($results->getFiles()) == 0) {
				  echo "<p>No files found.</p>";
				} else {
				  echo '<div class="jumbotron">';
				  echo "Files: <br>";
				  foreach ($results->getFiles() as $file) {
				    printf("%s (%s)<br>", $file->getName(), $file->getId());
				  }
				  echo '</div>';
				}

				// Upload file to selected folder
				if (isset($_REQUEST['folder']) && !empty($_REQUEST['folder'])) {
					$folderId = $client->getFolder($_REQUEST['folder']);
					if (!empty($_FILES['file']['name']) && !empty($_FILES['file']['tmp_name'])) {
						$filePath = $_FILES['file']['tmp_name'];
						$name = $_FILES['file']['name'];
						$type = $_FILES['file']['type'];
						$uploadResult = $client->createFile($folderId, $filePath, $name, $type);
						if (!$uploadResult) {
							echo '<div class="alert alert-danger" role="alert">Upload failed</div>';
						} else {
							echo '<div class="alert alert-success" role="alert">Upload successfully</div>';
						}
					}
					
				}
			?>
			<!-- select folder and upload file -->
			<div class="jumbotron">
				<form method="post" enctype="multipart/form-data">
					<div class="form-group">
						<label for="folder">Upload to which folder?</label>
						<input type="text" name="folder" id="folder" class="form-control" required>
					</div>
					<div class="form-group">
						<label for="file">File to upload</label>
						<input type="file" name="file" id="file" class="form-control" required accept="application/pdf">
					</div>
					<br>
					<input type="submit" class="btn btn-default">
				</form>
			</div>
			<?php
				} 
			?>
		</div>
	</div>
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
	<!-- Latest compiled and minified JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
</body>
</html>