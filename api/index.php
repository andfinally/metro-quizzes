<?php
// http://cacodaemon.de/index.php?id=48
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

// http://stackoverflow.com/questions/20320525/cant-get-json-post-data-submitted-via-slim

// Select all
// curl -i -X GET http://local5/backbone-json/api/quizzes

// Select one
// curl -i -X GET http://local5/backbone-json/api/quizzes/10

// Insert
// curl -X POST -d "title=Bring%20Up%20The%20Bodies" -d "author=Michael%20Withering" http://local5/backbone-json/api/quizzes
// curl -X POST -d "name=Irriating" http://local5/backbone-json/api/quizzes

// Update
// curl -X PUT -d "title=The%20Bourne%20Ultimatum" -d "author=Arthur%20Koestler" http://local5/backbone-json/api/quizzes/1
// curl -X PUT -H "Content-Type: application/json" -d '{"name":"zzz","id":1}' http://local5/backbone-json/api/quizzes/1

// Delete
// curl -X DELETE http://local5/backbone-json/api/quizzes/2

// Install
// curl -X http://local5/backbone-json/api/quizzes/install

require '../_config.php';

if ( empty( $_SESSION[ 'logged_in_user' ] ) ) {
    header('Location: ../login.php');
    die();
}

require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

$app->contentType('application/json');

function getRecord($id){
	try {
		$record = file_get_contents( 'data/' . $id . '.json' );
		if (!empty($record)) {
			return $record;
		} else {
			return '{}';
		}
	} catch (Exception $e) {
	 	return '{}';
	}
}

function setRecord($id, $data){
	$handle = fopen( 'data/' . $id . '.json', 'w+' );
	fwrite( $handle, json_encode( $data ) );
	if ( fclose( $handle ) ) {
		return array('id' => $id, 'data' => $data);
	} else return false;
}

// Increments highest ID in quizzes list
function getnewID(){
	$list = json_decode(getRecord('quizzes'));

	$ids = array_map(
		function ($element){
			return $element->id;
		},
		$list
	);

	return max($ids) + 1;
}

function addToList($id, $name){
	$list = json_decode(getRecord('quizzes'), true);
	$list[] = array('id' => $id, 'name' => $name);
	setRecord('quizzes', $list );
}

function deleteFromList($id){
	// Without second 'true' arg, returns array of objects
	$list = json_decode(getRecord('quizzes'));
	$index = findIndexInList($list, $id);
	if (!empty($index)) {
		unset($list[$index]);
	}
	setRecord('quizzes', $list );
	unlink('data/' . $id . '.json');
}

// Finds the member of the index array that has a given ID property
// Assumes each $value member is a stdClass object
function findIndexInList($list, $id){
	foreach ($list as $index => $value) {
		if ($value->id == $id) {
			return $index;
		}
	}
	return false;
}

function updateList($id, $name){
	$list = json_decode(getRecord('quizzes'), true);
	$key = findInList($list, $id);
	if ($key) {
		$list[$key] = array('id' => $id, 'name' => $name);
		setRecord('quizzes', $list );
	}
}

function findInList($list, $id)
{
   foreach($list as $key => $value)
   {
	  if ( $value['id'] == $id )
		 return $key;
   }
   return false;
}

function returnResult($action, $success = true, $id = 0, $data = array())
{
	echo json_encode( array(
		'action' => $action,
		'success' => $success,
		'id' => intval($id),
		'data' => $data
    ));
}



// Select

$app->get('/', function(){
	updateList(1, 'Name');
	echo "Index page, shoo, be off with you.";
});

$app->get('/quizzes/', function () use ($app) {
	echo getRecord('quizzes');
});

$app->get('/quizzes/:id', function ($id) use ($app) {
	echo getRecord($id);
});



// Insert

$app->post('/quizzes/', function () use ($app) {
	$id = getnewID();
	$data = json_decode( $app->request()->getBody(), true );
	$data['id'] = $id;
	ksort($data);
	$r = setRecord($id, $data);
	if (!empty($r)) {
		addToList($id, $data['name']);
		echo json_encode(array('id'=>$id, 'name'=>$data['name']));
	}
});



// Update

$app->put('/quizzes/:id', function ($id) use ($app) {
	$data = json_decode( $app->request()->getBody(), true );
	$data['id'] = $id;
	ksort($data);
	$r = setRecord($id, $data);
	if (!empty($r)) {
		updateList($id, $data['name']);
		echo json_encode($data);
	}
});



// Delete

$app->delete('/quizzes/:id', function ($id) {
	deleteFromList($id);
});

$app->run();
