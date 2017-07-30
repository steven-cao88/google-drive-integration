<?php
require_once __DIR__ . '/vendor/autoload.php';
class Client {
	private $applicationName = 'Drive API PHP Quickstart';
	private $credentialPath = __DIR__ . '/drive-php-quickstart.json';
	private $clientSecretPath = __DIR__ . '/client_secret.json';
	private $accessType = 'offline';
	private $scope = null;
	private $accessToken = null;
	private $service = null;

	public $client = null;

	public function __construct() {
		$this->client = new Google_Client();
		$this->scope = implode(' ', array(Google_Service_Drive::DRIVE));
  		$this->client->setApplicationName($this->applicationName);
  		$this->client->setScopes($this->scope);
  		$this->client->setAuthConfig($this->clientSecretPath);
  		$this->client->setAccessType($this->accessType);
	}

	public function isAuthorised() {
		return file_exists($this->credentialPath);
	}

	public function getUrl() {
		return $this->client->createAuthUrl();
	}

	public function authorise($authCode = '') {
		if (empty($authCode)) {
			throw new Exception("Empty authorisation code");
		}

		// Exchange authorization code for an access token.
    	$accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);
    	// Store the credentials to disk.
    	if (!file_exists(dirname($this->credentialPath))) {
      		mkdir(dirname($this->credentialPath), 0700, true);
    	}
    	file_put_contents($this->credentialPath, json_encode($accessToken));
    	$this->client->setAccessToken($accessToken);
	}

	public function isTokenExpired() {
		return $this->client->isAccessTokenExpired();
	}

	public function refeshToken() {
		$this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
		if (!file_exists(dirname($this->credentialsPath))) {
      		mkdir(dirname($this->credentialsPath), 0700, true);
    	}
    	file_put_contents($this->credentialsPath, json_encode($this->client->getAccessToken()));
	}

	public function setToken() {
		if (!file_exists(dirname($this->credentialPath))) {
			throw new Exception("Authorisation code does not exist");
		}
		$accessToken = json_decode(file_get_contents($this->credentialPath), true);
		$this->client->setAccessToken($accessToken);
		// Refresh the token if it's expired.
		if ($this->isTokenExpired()) {
			$this->refeshToken();
		}
	}

	public function getService() {
		if (is_null($this->service)) {
			$this->setToken();
			$this->service = new Google_Service_Drive($this->client);
		}
	}

	public function ping() {
		// Print the names and IDs for up to 10 files.
		$optParams = array(
		  'pageSize' => 10,
		  'fields' => 'nextPageToken, files(id, name)'
		);
		$service = $this->service;
		$results = $service->files->listFiles($optParams);
		return $results;
	}

	public function search($query = '') {
		$pageToken = null;
		$result = array();
		do {
		  $response = $this->service->files->listFiles(array(
		    'q' => $query,
		    'spaces' => 'drive',
		    'pageToken' => $pageToken,
		    'pageSize' => 10,
		    'fields' => 'nextPageToken, files(id, name)',
		  ));
		  foreach ($response->files as $file) {
		  	$result[] = array('id' => $file->id, 'name' => $file->name);
		  }
		} while ($pageToken != null);
		return $result;
	}

	// search folder, create if not exist
	public function getFolder($name = '') {
		if (empty($name)) {
			throw new Exception("Invalid folder name");
		}
		$query = "mimeType='application/vnd.google-apps.folder' and name = '" . $name . "' and trashed = false";
		$result = $this->search($query);
		if (empty($result)) {
			$folderId = $this->createFolder($name);
		} else {
			$folderId = $result[0]['id'];
		}
		return $folderId;
	}

	public function createFolder($name) {
		$fileMetadata = new Google_Service_Drive_DriveFile(array(
		  'name' => $name,
		  'mimeType' => 'application/vnd.google-apps.folder'));
		$file = $this->service->files->create($fileMetadata, array(
		  'fields' => 'id'));
		return $file->id;
	}

	public function createFile($folderId, $filePath, $name, $type) {
		// Upload a file - Resumable upload
		$file = new Google_Service_Drive_DriveFile(array(
  			'name' => $name,
  			'parents' => array($folderId)
		));
		$chunkSizeBytes = 1 * 1024 * 1024;

		// Call the API with the media upload, defer so it doesn't immediately return.
		$client = $this->client;
		$service = $this->service;
		$client->setDefer(true);
		$request = $service->files->create($file);

		// Create a media file upload to represent our upload process.
		$media = new Google_Http_MediaFileUpload(
			$client,
		  	$request,
		  	$type,
		  	null,
		  	true,
		  	$chunkSizeBytes
		);
		$media->setFileSize(filesize($filePath));

		// Upload the various chunks. $status will be false until the process is
		// complete.
		$status = false;
		$handle = fopen($filePath, "rb");
		while (!$status && !feof($handle)) {
		  $chunk = fread($handle, $chunkSizeBytes);
		  $status = $media->nextChunk($chunk);
		 }

		// The final value of $status will be the data from the API for the object
		// that has been uploaded.
		$result = false;
		if($status != false) {
		  $result = $status;
		}

		fclose($handle);
		// Reset to the client to execute requests immediately in the future.
		$client->setDefer(false);
		// Remove tmp file
		unlink($filePath);

		return $result;
	}



}